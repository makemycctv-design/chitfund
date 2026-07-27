<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Creates the default single-installation company, its branches, and the
 * configurable (state/company-specific) compliance + operational settings.
 * Idempotent via firstOrCreate.
 */
class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['registration_number' => 'CF-DEMO-0001'],
            [
                'name' => 'ChittyFund Demo',
                'legal_name' => 'ChittyFund Demo Chit Funds Pvt. Ltd.',
                'gstin' => '32ABCDE1234F1Z5',
                'email' => 'hello@chittyfund.test',
                'phone' => '9847000000',
                'address_line1' => 'MG Road',
                'city' => 'Kochi',
                'state' => 'Kerala',
                'pincode' => '682011',
                'country' => 'IN',
                'currency' => 'INR',
                'timezone' => 'Asia/Kolkata',
                'locale' => 'en',
                'is_active' => true,
            ]
        );

        $branches = [
            ['code' => 'KOCHI', 'name' => 'Kochi (HQ)', 'city' => 'Kochi', 'pincode' => '682011'],
            ['code' => 'TVM', 'name' => 'Trivandrum', 'city' => 'Thiruvananthapuram', 'pincode' => '695001'],
        ];

        foreach ($branches as $b) {
            Branch::firstOrCreate(
                ['company_id' => $company->id, 'code' => $b['code']],
                [
                    'name' => $b['name'],
                    'email' => strtolower($b['code']).'@chittyfund.test',
                    'phone' => '9847'.random_int(100000, 999999),
                    'city' => $b['city'],
                    'state' => 'Kerala',
                    'pincode' => $b['pincode'],
                    'is_active' => true,
                ]
            );
        }

        // Configurable settings — every compliance knob lives here so rules can
        // vary by state/company without code changes.
        $settings = [
            ['compliance', 'max_discount_percent', '40', 'decimal', true],
            ['compliance', 'default_foreman_commission_percent', '5', 'decimal', true],
            ['compliance', 'default_grace_period_days', '5', 'int', true],
            ['compliance', 'allow_partial_payments', 'false', 'bool', true],
            ['payment', 'default_gateway', 'razorpay', 'string', false],
            ['payment', 'gateway_enabled', 'false', 'bool', false],
            ['notification', 'whatsapp_enabled', 'false', 'bool', false],
            ['notification', 'email_enabled', 'true', 'bool', false],
            ['general', 'default_locale', 'en', 'string', true],
        ];

        foreach ($settings as [$group, $key, $value, $type, $isPublic]) {
            Setting::firstOrCreate(
                ['company_id' => $company->id, 'group' => $group, 'key' => $key],
                ['value' => $value, 'type' => $type, 'is_public' => $isPublic]
            );
        }
    }
}
