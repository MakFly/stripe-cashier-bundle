<?php

declare(strict_types=1);

namespace CashierBundle\Model;

use Stripe\TaxRate as StripeTaxRate;

/** Wraps a Stripe TaxRate and exposes its percentage, country, and activity status. */
final class TaxRate
{
    public function __construct(
        private readonly StripeTaxRate $taxRate,
    ) {
    }

    public function id(): string
    {
        return $this->taxRate->id;
    }

    public function displayName(): string
    {
        return $this->taxRate->display_name;
    }

    public function percentage(): float
    {
        return $this->taxRate->percentage;
    }

    public function inclusive(): bool
    {
        return $this->taxRate->inclusive;
    }

    public function active(): bool
    {
        return $this->taxRate->active;
    }

    public function country(): ?string
    {
        return $this->taxRate->country;
    }

    public function state(): ?string
    {
        return $this->taxRate->state;
    }

    public function description(): ?string
    {
        return $this->taxRate->description;
    }
}
