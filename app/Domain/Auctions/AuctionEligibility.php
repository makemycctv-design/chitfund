<?php

namespace App\Domain\Auctions;

use App\Enums\KycStatus;
use App\Enums\MembershipStatus;
use App\Models\ChittyMembership;
use App\Models\InstallmentPayment;

/**
 * Server-side eligibility check for participating in an auction. Rules reflect
 * common chit-fund policy and are evaluated fresh on every entry/bid so a
 * client can never assert its own eligibility.
 */
class AuctionEligibility
{
    /**
     * @return array{eligible: bool, reason: ?string}
     */
    public function check(ChittyMembership $membership): array
    {
        if ($membership->status !== MembershipStatus::Active) {
            return $this->deny('Membership is not active.');
        }

        if ($membership->is_prized) {
            return $this->deny('This ticket has already won a prize.');
        }

        $kyc = $membership->customer?->customerProfile?->kyc_status;
        if ($kyc !== KycStatus::Verified) {
            return $this->deny('KYC is not verified.');
        }

        // Dues must be current: no overdue installments for this membership.
        $hasOverdue = InstallmentPayment::where('chitty_membership_id', $membership->id)
            ->overdue()
            ->exists();

        if ($hasOverdue) {
            return $this->deny('There are overdue installments on this ticket.');
        }

        return ['eligible' => true, 'reason' => null];
    }

    private function deny(string $reason): array
    {
        return ['eligible' => false, 'reason' => $reason];
    }
}
