<?php

namespace CashierBundle\Exception;

use Exception;

class SubscriptionUpdateFailureException extends Exception
{
    public static function create(string $reason): self
    {
        return new self("Subscription update failed: {$reason}");
    }
}
