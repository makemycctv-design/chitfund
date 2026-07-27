<?php

namespace Database\Seeders;

use App\Enums\ChittyStatus;
use App\Enums\KycStatus;
use App\Enums\MembershipStatus;
use App\Enums\RegistrationStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Chitty;
use App\Models\ChittyMembership;
use App\Models\ChittyScheme;
use App\Models\Company;
use App\Models\CustomerProfile;
use App\Models\StaffProfile;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds demo staff, customers, schemes, chitties and memberships for local
 * development ONLY. Idempotent so it can be re-run. All demo accounts use the
 * password "password".
 */
class DemoDataSeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        $company = Company::query()->orderBy('id')->firstOrFail();
        $kochi = Branch::where('company_id', $company->id)->where('code', 'KOCHI')->first();
        $tvm = Branch::where('company_id', $company->id)->where('code', 'TVM')->first();

        $this->seedStaff($company, $kochi);
        $customers = $this->seedCustomers($company, $kochi, $tvm);
        $schemes = $this->seedSchemes($company);
        $chitties = $this->seedChitties($company, $kochi, $tvm, $schemes);
        $this->seedMemberships($company, $chitties, $customers);
        $this->seedSchedulesAndPayments($company, $chitties);
        $this->seedLiveAuction($company, $customers);
    }

    /**
     * Creates a dedicated chitty that starts in the future (so members are
     * dues-current and eligible), then schedules + starts a LIVE auction with a
     * couple of opening bids, so the auction room has real data to explore.
     */
    private function seedLiveAuction(Company $company, array $customers): void
    {
        $kochi = Branch::where('company_id', $company->id)->where('code', 'KOCHI')->first();
        if (! $kochi) {
            return;
        }

        $chitty = Chitty::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'CH-LIVE-DEMO'],
            [
                'branch_id' => $kochi->id,
                'name' => 'Live Auction Demo — 1 Lakh / 20',
                'chit_value' => 100000,
                'duration_months' => 20,
                'total_subscribers' => 20,
                'installment_amount' => 5000,
                'foreman_commission_percent' => 5,
                'auction_frequency' => 'monthly',
                'min_bid_percent' => 0,
                'max_bid_percent' => 40,
                'grace_period_days' => 5,
                'late_fee_type' => 'percent',
                'late_fee_value' => 2,
                'required_kyc_level' => 1,
                'start_date' => now()->addMonth()->startOfMonth(),
                'maturity_date' => now()->addMonths(21)->startOfMonth(),
                'auction_day' => 10,
                'auction_time' => '11:00:00',
                'status' => \App\Enums\ChittyStatus::Active->value,
            ]
        );

        if ($chitty->auctions()->exists()) {
            return; // already seeded
        }

        $verified = array_values(array_filter(
            $customers,
            fn (User $u) => $u->customerProfile?->kyc_status === KycStatus::Verified,
        ));

        $ticket = 1;
        foreach (array_slice($verified, 0, 6) as $customer) {
            \App\Models\ChittyMembership::firstOrCreate(
                ['chitty_id' => $chitty->id, 'customer_id' => $customer->id],
                [
                    'company_id' => $company->id,
                    'ticket_number' => $ticket++,
                    'status' => MembershipStatus::Active->value,
                    'joined_on' => now(),
                ]
            );
        }

        app(\App\Domain\Installments\GenerateInstallmentSchedule::class)->handle($chitty);

        $officer = User::where('email', 'auction@chittyfund.test')->first()
            ?? User::where('type', 'staff')->first();

        $auction = app(\App\Domain\Auctions\ScheduleAuction::class)
            ->handle($chitty, 1, now(), $officer);

        app(\App\Domain\Auctions\AuctionLifecycle::class)->start($auction, $officer, 3600);

        // A couple of opening bids from eligible participants.
        $placeBid = app(\App\Domain\Auctions\PlaceBid::class);
        $bidders = array_slice($verified, 0, 2);
        $amount = 5000;
        foreach ($bidders as $bidder) {
            try {
                $placeBid->handle($auction->fresh(), $bidder, (string) $amount);
                $amount += 1500;
            } catch (\Throwable $e) {
                // skip ineligible in demo
            }
        }
    }

    /**
     * Generate installment schedules for active chitties and record a few
     * manual payments so collections, receipts and reports have real data.
     */
    private function seedSchedulesAndPayments(Company $company, array $chitties): void
    {
        $generator = app(\App\Domain\Installments\GenerateInstallmentSchedule::class);
        $recorder = app(\App\Domain\Payments\RecordManualPayment::class);

        $collector = User::where('email', 'collector@chittyfund.test')->first();

        foreach ($chitties as $chitty) {
            if ($chitty->status->value !== \App\Enums\ChittyStatus::Active->value) {
                continue;
            }
            if ($chitty->hasSchedule() || $chitty->memberships()->count() === 0) {
                continue;
            }

            $generator->handle($chitty);

            // Pay the first period for ~70% of members to create collections history.
            $firstPeriod = \App\Models\InstallmentPayment::where('chitty_id', $chitty->id)
                ->where('period_no', 1)
                ->get();

            $lateFees = app(\App\Domain\Installments\LateFeeCalculator::class);

            foreach ($firstPeriod as $index => $installment) {
                if ($index % 10 < 7) { // ~70%
                    // Pay the full amount owed (base + any accrued late fee) so
                    // the obligation settles cleanly for the demo.
                    $lateFee = $lateFees->calculate(
                        $chitty,
                        (string) $installment->amount_due,
                        $installment->due_date,
                        now(),
                    );
                    $amount = \App\Support\Money::add((string) $installment->amount_due, $lateFee);

                    $recorder->handle(
                        staff: $collector ?? User::where('type', 'staff')->first(),
                        installment: $installment,
                        method: \App\Enums\PaymentMethod::Cash,
                        amount: $amount,
                        note: 'Seed collection',
                    );
                }
            }
        }
    }

    private function seedStaff(Company $company, ?Branch $kochi): void
    {
        // Super Admin is global (no company scope) so it can administer everything.
        $this->makeStaff('Super Admin', 'superadmin@chittyfund.test', null, null, Rbac::SUPER_ADMIN, 'System Administrator');

        $owner = $this->makeStaff('Company Owner', 'owner@chittyfund.test', $company, $kochi, Rbac::COMPANY_OWNER, 'Managing Director');
        $manager = $this->makeStaff('Branch Manager', 'manager@chittyfund.test', $company, $kochi, Rbac::BRANCH_MANAGER, 'Branch Manager');

        $this->makeStaff('Anil Accountant', 'accountant@chittyfund.test', $company, $kochi, Rbac::ACCOUNTANT, 'Accountant');
        $this->makeStaff('Reema Collector', 'collector@chittyfund.test', $company, $kochi, Rbac::COLLECTION_STAFF, 'Collection Officer');
        $this->makeStaff('Auction Officer', 'auction@chittyfund.test', $company, $kochi, Rbac::AUCTION_OFFICER, 'Auction Officer');

        // Assign the branch manager to the Kochi branch.
        if ($kochi && ! $kochi->manager_id) {
            $kochi->update(['manager_id' => $manager->id]);
        }
        unset($owner);
    }

    private function makeStaff(string $name, string $email, ?Company $company, ?Branch $branch, string $role, string $designation): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'type' => UserType::Staff->value,
                'company_id' => $company?->id,
                'branch_id' => $branch?->id,
                'email_verified_at' => now(),
                'is_active' => true,
                'phone' => '9847'.random_int(100000, 999999),
            ]
        );

        $user->syncRoles([$role]);

        if ($company) {
            StaffProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'company_id' => $company->id,
                    'branch_id' => $branch?->id,
                    'employee_code' => 'EMP-'.strtoupper(Str::random(6)),
                    'designation' => $designation,
                    'joined_on' => now()->subMonths(random_int(1, 36)),
                    'status' => 'active',
                ]
            );
        }

        return $user;
    }

    /** @return User[] */
    private function seedCustomers(Company $company, ?Branch $kochi, ?Branch $tvm): array
    {
        $customers = [];

        // Two named demo logins for predictable testing.
        $customers[] = $this->makeCustomer('Priya Nair', 'priya@chittyfund.test', $company, $kochi, true);
        $customers[] = $this->makeCustomer('Rahul Menon', 'rahul@chittyfund.test', $company, $tvm, false);

        // A batch of generated customers (mostly verified, a few pending).
        for ($i = 1; $i <= 14; $i++) {
            $branch = $i % 2 === 0 ? $kochi : $tvm;
            $verified = $i > 3; // first three left pending to exercise the approval queue
            $customers[] = $this->makeCustomer(
                fake()->name(),
                "customer{$i}@chittyfund.test",
                $company,
                $branch,
                $verified,
            );
        }

        return $customers;
    }

    private function makeCustomer(string $name, string $email, Company $company, ?Branch $branch, bool $verified): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'type' => UserType::Customer->value,
                'company_id' => $company->id,
                'branch_id' => $branch?->id,
                'email_verified_at' => now(),
                'is_active' => true,
                'phone' => '9846'.random_int(100000, 999999),
            ]
        );

        $user->syncRoles([Rbac::CUSTOMER]);

        CustomerProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'company_id' => $company->id,
                'branch_id' => $branch?->id,
                'customer_code' => 'CUST-'.strtoupper(Str::random(6)),
                'date_of_birth' => now()->subYears(random_int(25, 55)),
                'gender' => fake()->randomElement(['male', 'female']),
                'occupation' => fake()->jobTitle(),
                'annual_income' => random_int(3, 20) * 100000,
                'city' => $branch?->city,
                'state' => 'Kerala',
                'pincode' => $branch?->pincode,
                'registration_status' => $verified ? RegistrationStatus::Approved->value : RegistrationStatus::Pending->value,
                'approved_at' => $verified ? now() : null,
                'kyc_status' => $verified ? KycStatus::Verified->value : KycStatus::Pending->value,
                'kyc_level' => $verified ? 1 : 0,
                'kyc_verified_at' => $verified ? now() : null,
            ]
        );

        return $user;
    }

    /** @return ChittyScheme[] */
    private function seedSchemes(Company $company): array
    {
        $defs = [
            ['SCH-1L20', '1 Lakh / 20 months', 100000, 20],
            ['SCH-5L25', '5 Lakh / 25 months', 500000, 25],
            ['SCH-10L40', '10 Lakh / 40 months', 1000000, 40],
        ];

        $schemes = [];
        foreach ($defs as [$code, $name, $value, $months]) {
            $schemes[] = ChittyScheme::firstOrCreate(
                ['company_id' => $company->id, 'code' => $code],
                [
                    'name' => $name,
                    'description' => "Standard scheme: {$name}",
                    'chit_value' => $value,
                    'duration_months' => $months,
                    'total_subscribers' => $months,
                    'installment_amount' => round($value / $months, 2),
                    'foreman_commission_percent' => 5,
                    'auction_frequency' => 'monthly',
                    'min_bid_percent' => 0,
                    'max_bid_percent' => 40,
                    'grace_period_days' => 5,
                    'late_fee_type' => 'percent',
                    'late_fee_value' => 2,
                    'required_kyc_level' => 1,
                    'is_active' => true,
                ]
            );
        }

        return $schemes;
    }

    /** @param ChittyScheme[] $schemes @return Chitty[] */
    private function seedChitties(Company $company, ?Branch $kochi, ?Branch $tvm, array $schemes): array
    {
        $plan = [
            ['CH-KOCHI-01', $kochi, $schemes[0], ChittyStatus::Active],
            ['CH-KOCHI-02', $kochi, $schemes[1], ChittyStatus::Active],
            ['CH-KOCHI-03', $kochi, $schemes[2], ChittyStatus::AuctionScheduled],
            ['CH-TVM-01', $tvm, $schemes[0], ChittyStatus::OpenForEnrollment],
            ['CH-TVM-02', $tvm, $schemes[1], ChittyStatus::Draft],
        ];

        $chitties = [];
        foreach ($plan as [$code, $branch, $scheme, $status]) {
            if (! $branch) {
                continue;
            }

            $start = now()->subMonths(random_int(0, 6))->startOfMonth();

            $chitties[] = Chitty::firstOrCreate(
                ['company_id' => $company->id, 'code' => $code],
                [
                    'branch_id' => $branch->id,
                    'chitty_scheme_id' => $scheme->id,
                    'name' => $scheme->name.' — '.$branch->code,
                    'chit_value' => $scheme->chit_value,
                    'duration_months' => $scheme->duration_months,
                    'total_subscribers' => $scheme->total_subscribers,
                    'installment_amount' => $scheme->installment_amount,
                    'foreman_commission_percent' => $scheme->foreman_commission_percent,
                    'auction_frequency' => $scheme->auction_frequency,
                    'min_bid_percent' => $scheme->min_bid_percent,
                    'max_bid_percent' => $scheme->max_bid_percent,
                    'grace_period_days' => $scheme->grace_period_days,
                    'late_fee_type' => $scheme->late_fee_type,
                    'late_fee_value' => $scheme->late_fee_value,
                    'required_kyc_level' => $scheme->required_kyc_level,
                    'start_date' => $start,
                    'maturity_date' => $start->copy()->addMonthsNoOverflow($scheme->duration_months),
                    'auction_day' => random_int(5, 25),
                    'auction_time' => '11:00:00',
                    'status' => $status->value,
                ]
            );
        }

        return $chitties;
    }

    /** @param Chitty[] $chitties @param User[] $customers */
    private function seedMemberships(Company $company, array $chitties, array $customers): void
    {
        $verifiedCustomers = array_values(array_filter(
            $customers,
            fn (User $u) => $u->customerProfile?->kyc_status === KycStatus::Verified,
        ));

        foreach ($chitties as $chitty) {
            if (! in_array($chitty->status->value, ChittyStatus::liveStatuses(), true)) {
                continue; // only enroll into live chitties
            }

            // Enroll up to ~60% of the slots so dashboards show partial fill.
            $slots = (int) ceil($chitty->total_subscribers * 0.6);
            $ticket = 1;

            foreach (array_slice($verifiedCustomers, 0, $slots) as $customer) {
                ChittyMembership::firstOrCreate(
                    ['chitty_id' => $chitty->id, 'customer_id' => $customer->id],
                    [
                        'company_id' => $company->id,
                        'ticket_number' => $ticket++,
                        'status' => MembershipStatus::Active->value,
                        'joined_on' => $chitty->start_date ?? now(),
                        'is_prized' => false,
                    ]
                );
            }
        }
    }
}
