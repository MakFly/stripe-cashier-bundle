<?php

declare(strict_types=1);

namespace CashierBundle\Model;

use Stripe\CustomerBalanceTransaction as StripeCustomerBalanceTransaction;

final class CustomerBalanceTransaction
{
    public function __construct(
        private readonly StripeCustomerBalanceTransaction $transaction
    ) {}

    public function id(): string
    {
        return $this->transaction->id;
    }

    public function amount(): string
    {
        return Cashier::formatAmount(abs($this->rawAmount()));
    }

    public function rawAmount(): int
    {
        return $this->transaction->amount;
    }

    public function currency(): string
    {
        return $this->transaction->currency;
    }

    public function type(): string
    {
        return $this->transaction->type;
    }

    public function description(): ?string
    {
        return $this->transaction->description;
    }

    public function isCredit(): bool
    {
        return $this->rawAmount() < 0;
    }

    public function isDebit(): bool
    {
        return $this->rawAmount() > 0;
    }
}
