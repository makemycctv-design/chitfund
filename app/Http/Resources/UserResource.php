<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ulid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'type' => $this->type?->value,
            'locale' => $this->locale,
            'emailVerified' => $this->email_verified_at !== null,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'customerProfile' => $this->whenLoaded('customerProfile', fn () => [
                'registrationStatus' => $this->customerProfile?->registration_status?->value,
                'kycStatus' => $this->customerProfile?->kyc_status?->value,
                'kycLevel' => $this->customerProfile?->kyc_level,
            ]),
        ];
    }
}
