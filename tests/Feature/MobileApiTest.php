<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\NotificationLog;
use App\Models\PaymentTransaction;
use App\Support\Rbac;
use Database\Seeders\NotificationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsTenantData;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use BuildsTenantData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(NotificationTemplateSeeder::class);
        // A default company so registration can attach the customer.
        Company::factory()->create();
    }

    public function test_customer_can_register_via_api(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Mobile User',
            'email' => 'mobile@test.com',
            'phone' => '9800000001',
            'password' => 'password123',
            'device_name' => 'iphone-test',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email', 'type']]]);

        $user = \App\Models\User::where('email', 'mobile@test.com')->firstOrFail();
        $this->assertTrue($user->hasRole(Rbac::CUSTOMER));
        $this->assertSame('customer', $user->type->value);
    }

    public function test_device_token_registration_and_push_routing(): void
    {
        $company = Company::query()->orderBy('id')->first();
        $customer = $this->makeCustomer($company);

        Sanctum::actingAs($customer, ['*']);

        $this->postJson('/api/v1/devices', ['token' => 'ExponentPushToken[abc123]', 'platform' => 'android'])
            ->assertOk();

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $customer->id,
            'token' => 'ExponentPushToken[abc123]',
        ]);

        // With a device token + push template, a notification now sends on push too.
        $txn = PaymentTransaction::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'amount' => '5000.00',
        ]);
        $customer->fresh()->notify(new \App\Notifications\PaymentReceived($txn));

        $this->assertTrue(
            NotificationLog::where('user_id', $customer->id)->where('channel', 'push')->where('status', 'sent')->exists(),
        );
    }

    public function test_push_channel_skipped_without_device_token(): void
    {
        $company = Company::query()->orderBy('id')->first();
        $customer = $this->makeCustomer($company); // no device token

        $txn = PaymentTransaction::factory()->create([
            'company_id' => $company->id, 'customer_id' => $customer->id, 'amount' => '5000.00',
        ]);
        $customer->notify(new \App\Notifications\PaymentReceived($txn));

        // No push log should be created when there is no registered device.
        $this->assertFalse(
            NotificationLog::where('user_id', $customer->id)->where('channel', 'push')->exists(),
        );
    }
}
