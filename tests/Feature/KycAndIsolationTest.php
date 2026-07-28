<?php

namespace Tests\Feature;

use App\Enums\KycStatus;
use App\Support\Rbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenantData;
use Tests\TestCase;

class KycAndIsolationTest extends TestCase
{
    use BuildsTenantData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_staff_with_permission_can_verify_customer_kyc(): void
    {
        $company = $this->makeCompany();
        $branch = \App\Models\Branch::factory()->create(['company_id' => $company->id]);
        // Branch manager handles KYC for customers of their own branch.
        $staff = $this->makeStaff($company, Rbac::BRANCH_MANAGER, $branch); // has kyc.verify
        $customer = $this->makeCustomer($company, verified: false, branch: $branch);

        $this->actingAs($staff)
            ->post("/admin/customers/{$customer->ulid}/verify-kyc")
            ->assertRedirect();

        $this->assertSame(KycStatus::Verified, $customer->customerProfile->fresh()->kyc_status);
    }

    public function test_branch_manager_cannot_verify_kyc_for_another_branch(): void
    {
        $company = $this->makeCompany();
        $branchA = \App\Models\Branch::factory()->create(['company_id' => $company->id]);
        $branchB = \App\Models\Branch::factory()->create(['company_id' => $company->id]);

        $staff = $this->makeStaff($company, Rbac::BRANCH_MANAGER, $branchA);
        $customer = $this->makeCustomer($company, verified: false, branch: $branchB);

        $this->actingAs($staff)
            ->post("/admin/customers/{$customer->ulid}/verify-kyc")
            ->assertForbidden();
    }

    public function test_collection_staff_cannot_verify_kyc(): void
    {
        $company = $this->makeCompany();
        $staff = $this->makeStaff($company, Rbac::COLLECTION_STAFF); // no kyc.verify
        $customer = $this->makeCustomer($company, verified: false);

        $this->actingAs($staff)
            ->post("/admin/customers/{$customer->ulid}/verify-kyc")
            ->assertForbidden();
    }

    public function test_customer_cannot_view_another_customers_chitty(): void
    {
        $company = $this->makeCompany();
        $chitty = $this->makeChitty($company);

        $owner = $this->makeCustomer($company);
        $this->enroll($chitty, $owner, 1);

        $intruder = $this->makeCustomer($company);

        // The intruder is not enrolled, so resolving via their own membership 404s.
        $this->actingAs($intruder)
            ->get("/portal/chitties/{$chitty->ulid}")
            ->assertNotFound();

        // The enrolled owner can view it.
        $this->actingAs($owner)
            ->get("/portal/chitties/{$chitty->ulid}")
            ->assertOk();
    }

    public function test_staff_cannot_manage_chitty_in_another_company(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $staffA = $this->makeStaff($companyA);
        $chittyB = $this->makeChitty($companyB);

        // Chitty B belongs to another company → policy denies (404 from binding scope or 403).
        $this->actingAs($staffA)
            ->get("/admin/chitties/{$chittyB->ulid}")
            ->assertForbidden();
    }
}
