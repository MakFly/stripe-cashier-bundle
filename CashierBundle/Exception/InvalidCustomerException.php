<?php

namespace CashierBundle\Exception;

use Exception;

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
