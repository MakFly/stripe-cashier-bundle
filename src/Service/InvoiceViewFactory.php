<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Model\Discount;
use CashierBundle\Model\Invoice;
use CashierBundle\Model\InvoiceLineItem;
use CashierBundle\Model\Tax;
use Stripe\Customer as StripeCustomer;

final class InvoiceViewFactory
{
    /**
     * @param array<string, mixed> $data
     *
     * @return array{invoice: array<string, mixed>, customer: array<string, mixed>|null, company: array<string, mixed>, footer: array<string, mixed>}
     */
    public function create(Invoice $invoice, array $data = []): array
    {
        $stripeInvoice = $invoice->asStripeInvoice();
        $customer = $stripeInvoice->customer instanceof StripeCustomer ? $stripeInvoice->customer : null;

        $lineItems = array_map(
            static function (InvoiceLineItem $item): array {
                return [
                    'id' => $item->id(),
                    'description' => $item->description(),
                    'quantity' => $item->quantity() ?? 1,
                    'unit_amount' => $item->quantity() && $item->quantity() > 0
                        ? (int) floor($item->rawAmount() / $item->quantity())
                        : $item->rawAmount(),
                    'amount' => $item->rawAmount(),
                    'currency' => $item->currency(),
                ];
            },
            $invoice->items(),
        );

        $discounts = array_map(
            static function (Discount $discount): array {
                $coupon = $discount->coupon();

                return [
                    'coupon' => [
                        'name' => $coupon->name(),
                        'amountOff' => $coupon->amountOff(),
                        'percentOff' => $coupon->percentOff(),
                    ],
                ];
            },
            $invoice->discounts(),
        );

        $taxes = array_map(
            static fn (Tax $tax): array => [
                'name' => $tax->name(),
                'percent' => $tax->percent(),
                'amount' => $tax->rawAmount(),
                'inclusive' => $tax->inclusive(),
            ],
            $invoice->taxes(),
        );

        return [
            'invoice' => [
                'id' => $invoice->id(),
                'number' => $invoice->number(),
                'status' => $invoice->status(),
                'date' => $invoice->date(),
                'dueDate' => $invoice->dueDate(),
                'currency' => $invoice->currency(),
                'subtotal' => $stripeInvoice->subtotal,
                'tax' => $stripeInvoice->tax ?? 0,
                'total' => $invoice->rawTotal(),
                'total_paid' => $stripeInvoice->amount_paid ?? $invoice->rawTotal(),
                'amount_due' => $stripeInvoice->amount_due ?? 0,
                'items' => $lineItems,
                'discounts' => $discounts,
                'taxes' => $taxes,
                'payment_intent' => $invoice->paymentIntentId(),
                'hosted_invoice_url' => $stripeInvoice->hosted_invoice_url ?? null,
            ],
            'customer' => $customer === null ? null : [
                'name' => $customer->name ?? $stripeInvoice->customer_name ?? null,
                'email' => $customer->email ?? $stripeInvoice->customer_email ?? null,
                'address' => $this->formatAddress($customer->address ?? $stripeInvoice->customer_address ?? null),
            ],
            'company' => [
                'name' => $data['company_name'] ?? 'Stripe-like Invoice',
                'address' => $data['company_address'] ?? null,
                'email' => $data['company_email'] ?? null,
                'phone' => $data['company_phone'] ?? null,
            ],
            'footer' => [
                'text' => $data['footer_text'] ?? null,
                'support_email' => $data['support_email'] ?? null,
            ],
        ];
    }

    /**
     * @param array<string, mixed>|object|null $address
     */
    private function formatAddress(null|object|array $address): ?string
    {
        if ($address === null) {
            return null;
        }

        $parts = [];

        foreach (['line1', 'line2', 'city', 'state', 'postal_code', 'country'] as $key) {
            $value = is_array($address) ? ($address[$key] ?? null) : ($address->{$key} ?? null);
            if (is_string($value) && $value !== '') {
                $parts[] = $value;
            }
        }

        return $parts === [] ? null : implode("\n", $parts);
    }
}
