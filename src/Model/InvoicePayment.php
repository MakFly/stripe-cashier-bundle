<?php

declare(strict_types=1);

namespace CashierBundle\Model;

final class InvoicePayment
{
    public function __construct(
        private readonly string $paymentIntentId,
        private readonly string $amount,
        private readonly int $rawAmount,
        private readonly string $currency,
        private readonly string $status,
    ) {
    }

    public function paymentIntentId(): string
    {
        return $this->paymentIntentId;
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function rawAmount(): int
    {
        return $this->rawAmount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }
}
