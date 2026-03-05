<?php

declare(strict_types=1);

namespace CashierBundle\Model;

final class Coupon
{
    public function __construct(
        private readonly object $coupon,
    ) {
    }

    public function id(): string
    {
        return $this->coupon->id;
    }

    public function name(): ?string
    {
        return $this->coupon->name;
    }

    public function percentOff(): ?float
    {
        return $this->coupon->percent_off;
    }

    public function amountOff(): ?int
    {
        return $this->coupon->amount_off;
    }

    public function currency(): ?string
    {
        return $this->coupon->currency;
    }

    public function duration(): string
    {
        return $this->coupon->duration;
    }

    public function durationInMonths(): ?int
    {
        return $this->coupon->duration_in_months;
    }

    public function valid(): bool
    {
        return $this->coupon->valid ?? true;
    }

    public function isPercentage(): bool
    {
        return $this->percentOff() !== null;
    }

    public function isFixedAmount(): bool
    {
        return $this->amountOff() !== null;
    }
}
