<?php

declare(strict_types=1);

namespace CashierBundle\Model;

final class Checkout
{
    public function __construct(
        private readonly object $session
    ) {}

    public function id(): string
    {
        return $this->session->id;
    }

    public function url(): ?string
    {
        return $this->session->url;
    }

    public function paymentIntentId(): ?string
    {
        return $this->session->payment_intent;
    }

    public function setupIntentId(): ?string
    {
        return $this->session->setup_intent;
    }

    public function customerId(): ?string
    {
        return $this->session->customer;
    }

    public function subscriptionId(): ?string
    {
        return $this->session->subscription;
    }

    public function status(): string
    {
        return $this->session->status;
    }

    public function isComplete(): bool
    {
        return $this->status() === 'complete';
    }

    public function isExpired(): bool
    {
        return $this->status() === 'expired';
    }

    public function isOpen(): bool
    {
        return $this->status() === 'open';
    }

    public function asStripeCheckoutSession(): object
    {
        return $this->session;
    }
}
