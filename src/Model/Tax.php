<?php

declare(strict_types=1);

namespace CashierBundle\Model;

/** Value object representing a tax amount applied to an invoice. */
final class Tax
{
    public function __construct(
        private readonly string $name,
        private readonly float $percent,
        private readonly int $amount,
        private readonly bool $inclusive,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function percent(): float
    {
        return $this->percent;
    }

    public function amount(): string
    {
        return Cashier::formatAmount($this->amount);
    }

    public function rawAmount(): int
    {
        return $this->amount;
    }

    public function inclusive(): bool
    {
        return $this->inclusive;
    }
}
