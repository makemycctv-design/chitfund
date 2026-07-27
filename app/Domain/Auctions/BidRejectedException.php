<?php

namespace App\Domain\Auctions;

use RuntimeException;

/**
 * Thrown when a bid fails server-side validation. Carries a safe, user-facing
 * message that controllers surface as a 422 / flash error.
 */
class BidRejectedException extends RuntimeException
{
}
