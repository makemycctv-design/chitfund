<?php

namespace Database\Factories;

use App\Enums\KycDocumentStatus;
use App\Enums\KycDocumentType;
use App\Models\Company;
use App\Models\CustomerProfile;
use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KycDocument>
 */
class KycDocumentFactory extends Factory
{
    protected $model = KycDocument::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'customer_id' => User::factory()->customer(),
            'customer_profile_id' => CustomerProfile::factory(),
            'type' => KycDocumentType::Aadhaar->value,
            'disk' => 'local',
            'file_path' => 'kyc/'.Str::random(40).'.pdf',
            'original_name' => 'aadhaar.pdf',
            'mime' => 'application/pdf',
            'size' => $this->faker->numberBetween(50_000, 500_000),
            'status' => KycDocumentStatus::Pending->value,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => KycDocumentStatus::Verified->value,
            'reviewed_at' => now(),
        ]);
    }
}
