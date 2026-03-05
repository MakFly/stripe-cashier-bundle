<?php

declare(strict_types=1);

namespace CashierBundle\Model;

use Stripe\PaymentMethod as StripePaymentMethod;

final class PaymentMethod
{
    public function __construct(
        private readonly StripePaymentMethod $paymentMethod
    ) {}

    public function id(): string
    {
        return $this->paymentMethod->id;
    }

    public function type(): string
    {
        return $this->paymentMethod->type;
    }

    public function isDefault(): bool
    {
        return true;
    }

    public function brand(): ?string
    {
        return $this->paymentMethod->card?->brand;
    }

    public function lastFour(): ?string
    {
        return $this->paymentMethod->card?->last4;
    }

    public function expiryMonth(): ?int
    {
        return $this->paymentMethod->card?->exp_month;
    }

    public function expiryYear(): ?int
    {
        return $this->paymentMethod->card?->exp_year;
    }

    public function bank(): ?string
    {
        return $this->paymentMethod->sepa_debit?->bank;
    }

    public function asStripePaymentMethod(): StripePaymentMethod
    {
        return $this->paymentMethod;
    }
}
