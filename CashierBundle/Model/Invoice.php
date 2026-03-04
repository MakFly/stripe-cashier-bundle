<?php

declare(strict_types=1);

namespace CashierBundle\Model;

use CashierBundle\Contract\InvoiceRendererInterface;
use Stripe\Invoice as StripeInvoice;
use Symfony\Component\HttpFoundation\Response;

final class Invoice
{
    public function __construct(
        private readonly StripeInvoice $invoice,
        private readonly InvoiceRendererInterface $renderer
    ) {}

    public function id(): string
    {
        return $this->invoice->id;
    }

    public function date(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->setTimestamp($this->invoice->created);
    }

    public function dueDate(): ?\DateTimeImmutable
    {
        return $this->invoice->due_date ? (new \DateTimeImmutable())->setTimestamp($this->invoice->due_date) : null;
    }

    public function total(): string
    {
        return Cashier::formatAmount($this->rawTotal(), $this->currency());
    }

    public function rawTotal(): int
    {
        return $this->invoice->total;
    }

    public function subtotal(): string
    {
        return Cashier::formatAmount($this->invoice->subtotal, $this->currency());
    }

    public function tax(): string
    {
        return Cashier::formatAmount($this->invoice->tax ?? 0, $this->currency());
    }

    public function currency(): string
    {
        return $this->invoice->currency;
    }

    /**
     * @return array<InvoiceLineItem>
     */
    public function items(): array
    {
        return array_map(
            static fn (StripeInvoice\LineItem $item) => new InvoiceLineItem($item),
            $this->invoice->lines->autoPagingIterator()->toArray()
        );
    }

    /**
     * @return array<Tax>
     */
    public function taxes(): array
    {
        return array_map(
            static fn (StripeInvoice\Tax $tax) => new Tax(
                $tax->name ?? 'Tax',
                (float) $tax->tax_rate->percentage,
                $tax->amount,
                $tax->tax_rate->inclusive ?? false
            ),
            $this->invoice->total_tax_amounts ?? []
        );
    }

    /**
     * @return array<InvoicePayment>
     */
    public function payments(): array
    {
        $payments = [];
        foreach ($this->invoice->payment_intents ?? [] as $paymentIntent) {
            $payments[] = new InvoicePayment(
                $paymentIntent->id,
                Cashier::formatAmount($paymentIntent->amount, $this->currency()),
                $paymentIntent->amount,
                $this->currency(),
                $paymentIntent->status
            );
        }

        return $payments;
    }

    /**
     * @return array<Discount>
     */
    public function discounts(): array
    {
        $discounts = [];
        foreach ($this->invoice->discounts ?? [] as $discount) {
            $discounts[] = new Discount($discount);
        }

        return $discounts;
    }

    public function download(array $data = []): Response
    {
        return $this->renderer->render($this, $data);
    }

    public function pay(): Payment
    {
        // Implementation would create/fetch a Payment from the invoice's payment intent
        // This is a placeholder - actual implementation depends on business logic
        return new Payment($this->invoice->payment_intent);
    }

    public function asStripeInvoice(): StripeInvoice
    {
        return $this->invoice;
    }
}
