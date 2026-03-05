<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Support;

use CashierBundle\Contract\BillableInterface;
use CashierBundle\Entity\Subscription;
use CashierBundle\Model\Checkout;
use CashierBundle\Model\CustomerBalanceTransaction;
use CashierBundle\Model\Invoice;
use CashierBundle\Model\Payment;
use CashierBundle\Model\PaymentMethod;
use CashierBundle\Service\SubscriptionBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Stripe\Customer as StripeCustomer;
use Stripe\Refund;

final class FakeBillable implements BillableInterface
{
    public function __construct(private ?string $stripeId = null)
    {
    }

    public function stripeId(): ?string
    {
        return $this->stripeId;
    }

    public function hasStripeId(): bool
    {
        return $this->stripeId !== null;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createAsStripeCustomer(array $options = []): string
    {
        return $this->stripeId ?? 'cus_test';
    }

    /**
     * @param array<string, mixed> $options
     */
    public function updateStripeCustomer(array $options = []): void
    {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createOrGetStripeCustomer(array $options = []): string
    {
        return $this->stripeId ?? 'cus_test';
    }

    public function asStripeCustomer(): ?StripeCustomer
    {
        return null;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function subscriptions(): Collection
    {
        return new ArrayCollection();
    }

    public function subscription(string $type = 'default'): ?Subscription
    {
        return null;
    }

    public function subscribed(string $type = 'default', ?string $price = null): bool
    {
        return false;
    }

    public function onTrial(string $type = 'default', ?string $price = null): bool
    {
        return false;
    }

    public function onGenericTrial(): bool
    {
        return false;
    }

    /**
     * @param array<int, string>|string $prices
     */
    public function newSubscription(string $type, string|array $prices = []): SubscriptionBuilder
    {
        throw new \BadMethodCallException('Not implemented in FakeBillable.');
    }

    public function hasDefaultPaymentMethod(): bool
    {
        return false;
    }

    public function defaultPaymentMethod(): ?PaymentMethod
    {
        return null;
    }

    public function addPaymentMethod(string $paymentMethod): PaymentMethod
    {
        throw new \BadMethodCallException('Not implemented in FakeBillable.');
    }

    public function updateDefaultPaymentMethod(string $paymentMethod): PaymentMethod
    {
        throw new \BadMethodCallException('Not implemented in FakeBillable.');
    }

    /**
     * @return Collection<int, PaymentMethod>
     */
    public function paymentMethods(?string $type = null): Collection
    {
        return new ArrayCollection();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function charge(int $amount, string $paymentMethod, array $options = []): Payment
    {
        throw new \BadMethodCallException('Not implemented in FakeBillable.');
    }

    /**
     * @param array<string, mixed> $options
     */
    public function pay(int $amount, array $options = []): Payment
    {
        throw new \BadMethodCallException('Not implemented in FakeBillable.');
    }

    /**
     * @param array<string, mixed> $options
     */
    public function refund(string $paymentIntent, array $options = []): Refund
    {
        throw new \BadMethodCallException('Not implemented in FakeBillable.');
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function invoices(bool $includePending = false): Collection
    {
        return new ArrayCollection();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function invoice(array $options = []): Invoice
    {
        throw new \BadMethodCallException('Not implemented in FakeBillable.');
    }

    public function upcomingInvoice(): ?Invoice
    {
        return null;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function tab(string $description, int $amount, array $options = []): self
    {
        return $this;
    }

    public function invoiceFor(string $description, int $amount): Invoice
    {
        throw new \BadMethodCallException('Not implemented in FakeBillable.');
    }

    /**
     * @param array<int, array{price: string, quantity?: int|null}> $items
     */
    public function checkout(array $items): Checkout
    {
        throw new \BadMethodCallException('Not implemented in FakeBillable.');
    }

    public function checkoutCharge(int $amount, string $name, int $quantity = 1): Checkout
    {
        throw new \BadMethodCallException('Not implemented in FakeBillable.');
    }

    public function balance(): string
    {
        return '0';
    }

    public function creditBalance(int $amount, ?string $description = null): CustomerBalanceTransaction
    {
        throw new \BadMethodCallException('Not implemented in FakeBillable.');
    }

    public function debitBalance(int $amount, ?string $description = null): CustomerBalanceTransaction
    {
        throw new \BadMethodCallException('Not implemented in FakeBillable.');
    }

    public function billingPortalUrl(?string $returnUrl = null): string
    {
        return 'https://example.test/billing';
    }

    /**
     * @return array<int, mixed>
     */
    public function taxRates(): array
    {
        return [];
    }

    public function isTaxExempt(): bool
    {
        return false;
    }
}
