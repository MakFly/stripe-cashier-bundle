<?php

namespace CashierBundle\Exception;

use Exception;

/** Thrown when a subscription update operation fails on the Stripe side. */
class SubscriptionUpdateFailureException extends Exception
{
    public static function create(string $reason): self
    {
        return new self("Subscription update failed: {$reason}");
    }
}
