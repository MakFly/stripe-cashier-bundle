<?php

declare(strict_types=1);

namespace CashierBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/** Dispatched when a Stripe payment attempt fails. */
final class PaymentFailedEvent extends Event
{
    /**
     * @param int $amount Amount in cents
     */
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

    /** Returns the payment amount as a decimal value (e.g. 10.99 for 1099 cents). */
    public function getAmountInDecimal(): float
    {
        return $this->amount / 100;
    }
}
