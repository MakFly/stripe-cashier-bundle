<?php

declare(strict_types=1);

namespace CashierBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class PaymentSucceededEvent extends Event
{
    public function __construct(
        public readonly string $customerId,
        public readonly ?string $paymentIntentId,
        public readonly int $amount,
        public readonly string $currency,
    ) {
    }

    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function getPaymentIntentId(): ?string
    {
        return $this->paymentIntentId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getAmountInDecimal(): float
    {
        return $this->amount / 100;
    }
}
