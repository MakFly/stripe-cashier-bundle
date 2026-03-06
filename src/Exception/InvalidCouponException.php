<?php

namespace CashierBundle\Exception;

use Exception;

/** Thrown when a coupon code is not recognized or cannot be applied. */
class InvalidCouponException extends Exception
{
    public static function invalid(string $couponId): self
    {
        return new self("Invalid coupon: {$couponId}");
    }
}
