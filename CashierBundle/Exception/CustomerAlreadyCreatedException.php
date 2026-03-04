<?php

namespace CashierBundle\Exception;

use Exception;

class CustomerAlreadyCreatedException extends Exception
{
    public static function create(string $stripeId): self
    {
        return new self("Customer with Stripe ID {$stripeId} already exists.");
    }
}
