<?php

declare(strict_types=1);

namespace CashierBundle\Contract;

use CashierBundle\Entity\Subscription;
use CashierBundle\Model\Checkout;
use CashierBundle\Model\CustomerBalanceTransaction;
use CashierBundle\Model\Invoice;
use CashierBundle\Model\Payment;
use CashierBundle\Model\PaymentMethod;
use CashierBundle\Service\SubscriptionBuilder;
use Doctrine\Common\Collections\Collection;
use Stripe\Customer as StripeCustomer;
use Stripe\Refund;

interface BillableInterface
{
    // Customer
    public function stripeId(): ?string;
    public function hasStripeId(): bool;
    public function createAsStripeCustomer(array $options = []): string;
    public function updateStripeCustomer(array $options = []): void;
    public function createOrGetStripeCustomer(array $options = []): string;
    public function asStripeCustomer(): ?StripeCustomer;

    // Subscriptions
    public function subscriptions(): Collection;
    public function subscription(string $type = 'default'): ?Subscription;
    public function subscribed(string $type = 'default', ?string $price = null): bool;
    public function onTrial(string $type = 'default', ?string $price = null): bool;
    public function onGenericTrial(): bool;
    public function newSubscription(string $type, string|array $prices = []): SubscriptionBuilder;

    // Payment Methods
    public function hasDefaultPaymentMethod(): bool;
    public function defaultPaymentMethod(): ?PaymentMethod;
    public function addPaymentMethod(string $paymentMethod): PaymentMethod;
    public function updateDefaultPaymentMethod(string $paymentMethod): PaymentMethod;
    public function paymentMethods(?string $type = null): Collection;

    // Charges
    public function charge(int $amount, string $paymentMethod, array $options = []): Payment;
    public function pay(int $amount, array $options = []): Payment;
    public function refund(string $paymentIntent, array $options = []): Refund;

    // Invoices
    public function invoices(bool $includePending = false): Collection;
    public function invoice(array $options = []): Invoice;
    public function upcomingInvoice(): ?Invoice;
    public function tab(string $description, int $amount, array $options = []): self;
    public function invoiceFor(string $description, int $amount): Invoice;

    // Checkout
    public function checkout(array $items): Checkout;
    public function checkoutCharge(int $amount, string $name, int $quantity = 1): Checkout;

    // Balance
    public function balance(): string;
    public function creditBalance(int $amount, ?string $description = null): CustomerBalanceTransaction;
    public function debitBalance(int $amount, ?string $description = null): CustomerBalanceTransaction;

    // Billing Portal
    public function billingPortalUrl(?string $returnUrl = null): string;

    // Tax
    public function taxRates(): array;
    public function isTaxExempt(): bool;
}
