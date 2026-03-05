<?php

declare(strict_types=1);

namespace CashierBundle\Model;

use Stripe\InvoiceLineItem as StripeInvoiceLineItem;

final class InvoiceLineItem
{
    public function __construct(
        private readonly StripeInvoiceLineItem $lineItem
    ) {}

    public function id(): string
    {
        return $this->lineItem->id;
    }

    public function description(): string
    {
        return $this->lineItem->description ?? '';
    }

    public function amount(): string
    {
        return Cashier::formatAmount($this->rawAmount(), $this->currency());
    }

    public function rawAmount(): int
    {
        return $this->lineItem->amount;
    }

    public function currency(): string
    {
        return $this->lineItem->currency;
    }

    public function quantity(): ?int
    {
        return $this->lineItem->quantity;
    }

    public function priceId(): ?string
    {
        return $this->lineItem->price?->id;
    }
}
