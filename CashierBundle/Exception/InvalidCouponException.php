<?php

namespace CashierBundle\Exception;

use Exception;

class InvalidCouponException extends Exception
{
    public static function invalid(string $couponId): self
    {
        return new self("Invalid coupon: {$couponId}");
    }
}
