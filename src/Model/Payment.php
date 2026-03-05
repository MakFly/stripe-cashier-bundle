<?php

declare(strict_types=1);

namespace CashierBundle\Model;

use Stripe\PaymentIntent;

final class Payment
{
    public function __construct(
        private readonly object $paymentIntent
    ) {}

    public function id(): string
    {
        return $this->paymentIntent->id;
    }

    public function amount(): string
    {
        return Cashier::formatAmount($this->rawAmount(), $this->currency());
    }

    public function rawAmount(): int
    {
        return $this->paymentIntent->amount;
    }

    public function currency(): string
    {
        return $this->paymentIntent->currency;
    }

    public function clientSecret(): ?string
    {
        return $this->paymentIntent->client_secret;
    }

    public function status(): string
    {
        return $this->paymentIntent->status;
    }

    public function capture(): self
    {
        $this->paymentIntent->capture();
        return $this;
    }

    public function cancel(): self
    {
        $this->paymentIntent->cancel();
        return $this;
    }

    public function requiresPaymentMethod(): bool
    {
        return $this->status() === 'requires_payment_method';
    }

    public function requiresAction(): bool
    {
        return in_array($this->status(), ['requires_action', 'requires_source_action'], true);
    }

    public function requiresConfirmation(): bool
    {
        return $this->status() === 'requires_confirmation';
    }

    public function requiresCapture(): bool
    {
        return $this->status() === 'requires_capture';
    }

    public function isCanceled(): bool
    {
        return $this->status() === 'canceled';
    }

    public function isSucceeded(): bool
    {
        return $this->status() === 'succeeded';
    }

    public function isProcessing(): bool
    {
        return $this->status() === 'processing';
    }

    public function asStripePaymentIntent(): object
    {
        return $this->paymentIntent;
    }
}
