<?php

namespace CashierBundle\Exception;

use Exception;

/** Thrown when a payment method ID is not recognized or cannot be used. */
class InvalidPaymentMethodException extends Exception
{
    public static function invalid(string $paymentMethodId): self
    {
        return new self("Invalid payment method: {$paymentMethodId}");
    }
}
