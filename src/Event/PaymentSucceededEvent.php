<?php

declare(strict_types=1);

namespace CashierBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/** Dispatched when a Stripe payment succeeds. */
final class PaymentSucceededEvent extends Event
{
    /**
     * @param int         $amount            Amount in cents
     * @param string|null $invoiceId         Stripe invoice ID, if payment is linked to an invoice
     * @param string|null $checkoutSessionId Stripe checkout session ID, if payment originated from a session
     */
    public function __construct(
        public readonly string $customerId,
        public readonly ?string $paymentIntentId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly ?string $invoiceId = null,
        public readonly ?string $checkoutSessionId = null,
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

    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    public function getCheckoutSessionId(): ?string
    {
        return $this->checkoutSessionId;
    }

    /** Returns the payment amount as a decimal value (e.g. 10.99 for 1099 cents). */
    public function getAmountInDecimal(): float
    {
        return $this->amount / 100;
    }
}
