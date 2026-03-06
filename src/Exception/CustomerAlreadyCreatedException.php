<?php

namespace CashierBundle\Exception;

use Exception;

/** Thrown when attempting to create a Stripe customer that already exists. */
class CustomerAlreadyCreatedException extends Exception
{
    public static function create(string $stripeId): self
    {
        return new self("Customer with Stripe ID {$stripeId} already exists.");
    }
}
