<?php

declare(strict_types=1);

namespace CashierBundle\Model;

use Stripe\SetupIntent as StripeSetupIntent;

/** Wraps a Stripe SetupIntent and exposes its status and payment method. */
final class SetupIntent
{
    public function __construct(
        private readonly StripeSetupIntent $setupIntent,
    ) {
    }

    public function id(): string
    {
        return $this->setupIntent->id;
    }

    public function clientSecret(): ?string
    {
        return $this->setupIntent->client_secret;
    }

    public function status(): string
    {
        return $this->setupIntent->status;
    }

    public function paymentMethodId(): ?string
    {
        return $this->setupIntent->payment_method;
    }

    public function isSucceeded(): bool
    {
        return $this->status() === 'succeeded';
    }

    public function requiresAction(): bool
    {
        return $this->status() === 'requires_action';
    }

    public function requiresConfirmation(): bool
    {
        return $this->status() === 'requires_confirmation';
    }

    public function cancel(): self
    {
        $this->setupIntent->cancel();
        return $this;
    }

    public function asStripeSetupIntent(): StripeSetupIntent
    {
        return $this->setupIntent;
    }
}
