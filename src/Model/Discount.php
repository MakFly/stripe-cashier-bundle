<?php

declare(strict_types=1);

namespace CashierBundle\Model;

use Stripe\Discount as StripeDiscount;

final class Discount
{
    public function __construct(
        private readonly StripeDiscount $discount
    ) {}

    public function coupon(): Coupon
    {
        return new Coupon($this->discount->coupon);
    }

    public function start(): ?int
    {
        return $this->discount->start;
    }

    public function end(): ?int
    {
        return $this->discount->end;
    }
}
