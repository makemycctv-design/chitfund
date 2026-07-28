<?php

namespace Tests\Feature;

use App\Domain\Installments\GenerateInstallmentSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenantData;
use Tests\TestCase;

/**
 * Renders every Phase 2 page end-to-end (full HTML via the built Vite manifest)
 * to catch runtime prop/type mismatches that a type-check alone would miss.
 */
class Phase2SmokeTest extends TestCase
{
    use BuildsTenantData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_admin_pages_render(): void
    {
        $company = $this->makeCompany();
        $staff = $this->makeStaff($company); // company-owner
        $customer = $this->makeCustomer($company);
        $chitty = $this->makeChitty($company);
        $this->enroll($chitty, $customer, 1);
        app(GenerateInstallmentSchedule::class)->handle($chitty);

        $get = fn (string $url) => $this->actingAs($staff)->get($url)->assertOk();

        $get('/admin/dashboard');
        $get('/admin/chitties');
        $get('/admin/chitties/create');
        $get("/admin/chitties/{$chitty->ulid}");
        $get("/admin/chitties/{$chitty->ulid}/edit");
        $get('/admin/customers');
        $get("/admin/customers/{$customer->ulid}");
        $get('/admin/collections');
        $get('/admin/reconciliation');
        $get('/admin/reports/collections');
        $get('/admin/reports/overdue');

        // Scheme management is Super Admin only.
        $superAdmin = $this->makeStaff($company, \App\Support\Rbac::SUPER_ADMIN);
        $this->actingAs($superAdmin)->get('/admin/schemes')->assertOk();
        $this->actingAs($staff)->get('/admin/schemes')->assertForbidden();
    }

    public function test_portal_pages_render(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeCustomer($company);
        $chitty = $this->makeChitty($company);
        $this->enroll($chitty, $customer, 1);
        app(GenerateInstallmentSchedule::class)->handle($chitty);

        $get = fn (string $url) => $this->actingAs($customer)->get($url)->assertOk();

        $get('/portal/dashboard');
        $get('/portal/chitties');
        $get("/portal/chitties/{$chitty->ulid}");
        $get('/portal/payments');
        $get('/portal/profile');
    }

    public function test_excel_exports_download(): void
    {
        $company = $this->makeCompany();
        $staff = $this->makeStaff($company);

        $this->actingAs($staff)->get('/admin/reports/collections/export')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($staff)->get('/admin/reports/overdue/export')->assertOk();
    }
}
