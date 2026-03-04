<?php

namespace CashierBundle\Exception;

use Exception;

class InvalidPaymentMethodException extends Exception
{
    public static function invalid(string $paymentMethodId): self
    {
        return new self("Invalid payment method: {$paymentMethodId}");
    }
}
