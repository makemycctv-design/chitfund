<?php

namespace App\Http\Controllers\Portal;

use App\Enums\KycDocumentType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Customer onboarding hub: profile details, KYC documents, and bank accounts.
 * (Account credentials/password live under the existing Settings module.)
 */
class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->customerProfile()->firstOrCreate(['company_id' => $user->company_id]);

        $user->load([
            'kycDocuments' => fn ($q) => $q->latest(),
            'bankAccounts' => fn ($q) => $q->latest(),
        ]);

        return Inertia::render('portal/profile', [
            'profile' => [
                'registrationStatus' => $profile->registration_status->value,
                'kycStatus' => $profile->kyc_status->value,
                'kycLevel' => $profile->kyc_level,
                'dateOfBirth' => $profile->date_of_birth?->toDateString(),
                'gender' => $profile->gender,
                'occupation' => $profile->occupation,
                'addressLine1' => $profile->address_line1,
                'addressLine2' => $profile->address_line2,
                'city' => $profile->city,
                'state' => $profile->state,
                'pincode' => $profile->pincode,
                'rejectionReason' => $profile->rejection_reason,
            ],
            'documents' => $user->kycDocuments->map(fn ($d) => [
                'id' => $d->ulid,
                'type' => $d->type->value,
                'typeLabel' => $d->type->label(),
                'originalName' => $d->original_name,
                'status' => $d->status->value,
                'rejectionReason' => $d->rejection_reason,
                'uploadedAt' => $d->created_at?->toIso8601String(),
            ]),
            'bankAccounts' => $user->bankAccounts->map(fn ($b) => [
                'id' => $b->ulid,
                'accountHolderName' => $b->account_holder_name,
                'last4' => $b->account_number_last4,
                'ifsc' => $b->ifsc,
                'bankName' => $b->bank_name,
                'isPrimary' => $b->is_primary,
                'isVerified' => $b->is_verified,
            ]),
            'documentTypes' => array_map(
                fn (KycDocumentType $t) => ['value' => $t->value, 'label' => $t->label()],
                KycDocumentType::cases(),
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->customerProfile()->firstOrCreate(['company_id' => $user->company_id]);

        $validated = $request->validate([
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'pincode' => ['nullable', 'string', 'max:10'],
        ]);

        $profile->update($validated);

        return back()->with('success', 'Profile updated.');
    }
}
