<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Contract\BillableInterface;
use CashierBundle\Contract\InvoiceRendererInterface;
use CashierBundle\Exception\InvalidInvoiceException;
use CashierBundle\Model\CustomerBalanceTransaction;
use CashierBundle\Model\Invoice;
use CashierBundle\Model\InvoiceLineItem;
use CashierBundle\Model\Payment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * @implements \CashierBundle\Concerns\ManagesInvoices<BillableInterface>
 */
class InvoiceService
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly InvoiceRendererInterface $renderer,
    ) {
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function list(BillableInterface $billable, bool $includePending = false): Collection
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            return new ArrayCollection();
        }

        try {
            $invoices = $this->stripe->invoices->all([
                'customer' => $stripeId,
                'status' => $includePending ? null : 'paid',
            ]);

            return new ArrayCollection(
                array_map(
                    fn (\Stripe\Invoice $invoice) => new Invoice($invoice, $this->renderer),
                    $invoices->data,
                ),
            );
        } catch (ApiErrorException $e) {
            return new ArrayCollection();
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function create(BillableInterface $billable, array $options = []): Invoice
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            throw new \RuntimeException('Billable must have a Stripe customer ID.');
        }

        $payload = array_merge(['customer' => $stripeId], $options);

        try {
            $invoice = $this->stripe->invoices->create($payload);

            return new Invoice($invoice, $this->renderer);
        } catch (ApiErrorException $e) {
            throw new InvalidInvoiceException(
                sprintf('Failed to create invoice: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    public function find(string $id): ?Invoice
    {
        try {
            $invoice = $this->stripe->invoices->retrieve($id);

            return new Invoice($invoice, $this->renderer);
        } catch (ApiErrorException $e) {
            return null;
        }
    }

    public function upcoming(BillableInterface $billable): ?Invoice
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            return null;
        }

        try {
            $invoice = $this->stripe->invoices->upcoming(['customer' => $stripeId]);

            return new Invoice($invoice, $this->renderer);
        } catch (ApiErrorException $e) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $invoiceItems
     */
    public function createInvoiceItem(BillableInterface $billable, array $invoiceItems): void
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            throw new \RuntimeException('Billable must have a Stripe customer ID.');
        }

        $payload = array_merge(['customer' => $stripeId], $invoiceItems);

        try {
            $this->stripe->invoiceItems->create($payload);
        } catch (ApiErrorException $e) {
            throw new InvalidInvoiceException(
                sprintf('Failed to create invoice item: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function tab(BillableInterface $billable, string $description, int $amount, array $options = []): void
    {
        $this->createInvoiceItem($billable, array_merge([
            'amount' => $amount,
            'currency' => Cashier::$currency,
            'description' => $description,
        ], $options));
    }

    public function pay(Invoice $invoice): Payment
    {
        try {
            $paidInvoice = $this->stripe->invoices->pay($invoice->id());

            if ($paidInvoice->payment_intent === null) {
                throw new InvalidInvoiceException('No payment intent associated with this invoice.');
            }

            $paymentIntent = $this->stripe->paymentIntents->retrieve($paidInvoice->payment_intent);

            return new Payment($paymentIntent);
        } catch (ApiErrorException $e) {
            throw new InvalidInvoiceException(
                sprintf('Failed to pay invoice %s: %s', $invoice->id(), $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function invoiceFor(BillableInterface $billable, string $description, int $amount, array $options = []): Invoice
    {
        $this->tab($billable, $description, $amount, $options);

        return $this->create($billable);
    }

    /**
     * @return array<int, InvoiceLineItem>
     */
    public function getInvoiceLines(string $invoiceId): array
    {
        try {
            $invoice = $this->stripe->invoices->retrieve($invoiceId);

            return array_map(
                fn (\Stripe\InvoiceLineItem $line) => new InvoiceLineItem($line),
                iterator_to_array($invoice->lines->autoPagingIterator()),
            );
        } catch (ApiErrorException $e) {
            return [];
        }
    }

    public function deleteInvoiceItem(string $invoiceItemId): void
    {
        try {
            $this->stripe->invoiceItems->delete($invoiceItemId);
        } catch (ApiErrorException $e) {
            throw new InvalidInvoiceException(
                sprintf('Failed to delete invoice item %s: %s', $invoiceItemId, $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function all(BillableInterface $billable, bool $includePending = false): Collection
    {
        return $this->list($billable, $includePending);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createTabItem(BillableInterface $billable, string $description, int $amount, array $options = []): void
    {
        $this->tab($billable, $description, $amount, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createInvoiceFor(BillableInterface $billable, string $description, int $amount, array $options = []): Invoice
    {
        return $this->invoiceFor($billable, $description, $amount, $options);
    }

    public function getBalance(BillableInterface $billable): string
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            return '0';
        }

        try {
            $customer = $this->stripe->customers->retrieve($stripeId);

            return (string) ($customer->balance ?? 0);
        } catch (ApiErrorException) {
            return '0';
        }
    }

    public function creditBalance(BillableInterface $billable, int $amount, ?string $description = null): CustomerBalanceTransaction
    {
        return $this->createBalanceTransaction($billable, -abs($amount), $description);
    }

    public function debitBalance(BillableInterface $billable, int $amount, ?string $description = null): CustomerBalanceTransaction
    {
        return $this->createBalanceTransaction($billable, abs($amount), $description);
    }

    private function createBalanceTransaction(BillableInterface $billable, int $amount, ?string $description): CustomerBalanceTransaction
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            throw new \RuntimeException('Billable must have a Stripe customer ID.');
        }

        try {
            $transaction = $this->stripe->customers->createBalanceTransaction($stripeId, array_filter([
                'amount' => $amount,
                'currency' => Cashier::$currency,
                'description' => $description,
            ], static fn (mixed $value): bool => $value !== null));

            return new CustomerBalanceTransaction($transaction);
        } catch (ApiErrorException $e) {
            throw new InvalidInvoiceException(
                sprintf('Failed to create customer balance transaction: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }
}
