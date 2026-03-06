<?php

namespace CashierBundle\Exception;

use Exception;

/** Thrown when a Stripe customer is not yet created or has an invalid ID. */
class InvalidCustomerException extends Exception
{
    public static function notYetCreated(): self
    {
        return new self('Customer has not been created in Stripe yet.');
    }

    public static function invalidId(string $stripeId): self
    {
        return new self("Invalid customer ID: {$stripeId}");
    }
}
