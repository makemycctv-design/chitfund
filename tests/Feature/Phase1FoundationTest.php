<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 guardrails: audience separation (staff vs customer surfaces) and the
 * versioned mobile API. Authorization is enforced server-side, so these assert
 * the middleware/controllers actually block cross-audience access.
 */
class Phase1FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function staffUser(): User
    {
        $company = Company::factory()->create();
        $user = User::factory()->staff()->create(['company_id' => $company->id]);
        $user->assignRole(Rbac::COMPANY_OWNER);

        return $user;
    }

    private function customerUser(): User
    {
        $company = Company::factory()->create();
        $user = User::factory()->customer()->create(['company_id' => $company->id]);
        $user->assignRole(Rbac::CUSTOMER);

        return $user;
    }

    public function test_staff_can_view_the_admin_dashboard(): void
    {
        $this->actingAs($this->staffUser())
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_customers_cannot_reach_the_admin_dashboard(): void
    {
        $this->actingAs($this->customerUser())
            ->get('/admin/dashboard')
            ->assertRedirect(route('portal.dashboard', absolute: false));
    }

    public function test_customers_can_view_the_portal_dashboard(): void
    {
        $this->actingAs($this->customerUser())
            ->get('/portal/dashboard')
            ->assertOk();
    }

    public function test_staff_cannot_reach_the_customer_portal(): void
    {
        $this->actingAs($this->staffUser())
            ->get('/portal/dashboard')
            ->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_customer_can_obtain_an_api_token(): void
    {
        $customer = $this->customerUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $customer->email,
            'password' => 'password',
            'device_name' => 'pixel-test',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email', 'type']]]);
    }

    public function test_staff_cannot_use_the_mobile_api(): void
    {
        $staff = $this->staffUser();

        $this->postJson('/api/v1/auth/login', [
            'email' => $staff->email,
            'password' => 'password',
            'device_name' => 'pixel-test',
        ])->assertStatus(403);
    }

    public function test_profile_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/profile')->assertStatus(401);
    }

    public function test_super_admin_bypasses_permission_checks(): void
    {
        $user = User::factory()->staff()->create();
        $user->assignRole(Rbac::SUPER_ADMIN);

        $this->assertTrue($user->can('chitties.create'));
        $this->assertTrue($user->can('companies.manage'));
    }
}
