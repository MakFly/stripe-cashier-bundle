<?php

declare(strict_types=1);

namespace CashierBundle\Model;

use Stripe\PromotionCode as StripePromotionCode;

/** Wraps a Stripe PromotionCode and exposes its coupon, redemption count, and activity. */
final class PromotionCode
{
    public function __construct(
        private readonly StripePromotionCode $promotionCode,
    ) {
    }

    public function id(): string
    {
        return $this->promotionCode->id;
    }

    public function code(): string
    {
        return $this->promotionCode->code;
    }

    public function coupon(): Coupon
    {
        return new Coupon($this->promotionCode->coupon);
    }

    public function active(): bool
    {
        return $this->promotionCode->active;
    }

    public function maxRedemptions(): ?int
    {
        return $this->promotionCode->max_redemptions;
    }

    public function timesRedeemed(): int
    {
        return $this->promotionCode->times_redeemed;
    }
}
