<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Contract\InvoiceLocaleResolverInterface;
use CashierBundle\Contract\InvoiceTranslationProviderInterface;
use CashierBundle\Model\Discount;
use CashierBundle\Model\Invoice;
use CashierBundle\Model\InvoiceLineItem;
use CashierBundle\Model\Tax;
use Stripe\Customer as StripeCustomer;

final class InvoiceViewFactory
{
    public function __construct(
        private readonly InvoiceLocaleResolverInterface $localeResolver,
        private readonly InvoiceTranslationProviderInterface $translationProvider,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{
     *     meta: array<string, mixed>,
     *     invoice: array<string, mixed>,
     *     customer: array<string, mixed>|null,
     *     company: array<string, mixed>,
     *     footer: array<string, mixed>
     * }
     */
    public function create(Invoice $invoice, array $data = []): array
    {
        $stripeInvoice = $invoice->asStripeInvoice();
        $customer = $stripeInvoice->customer instanceof StripeCustomer ? $stripeInvoice->customer : null;
        $locale = $this->localeResolver->resolve($customer, $data);
        $translations = $this->translationProvider->getTranslations($locale);
        $currency = $invoice->currency();

        $lineItems = array_map(
            fn (InvoiceLineItem $item): array => [
                'id' => $item->id(),
                'description' => $item->description(),
                'quantity' => $item->quantity() ?? 1,
                'unit_amount' => $item->quantity() && $item->quantity() > 0
                    ? (int) floor($item->rawAmount() / $item->quantity())
                    : $item->rawAmount(),
                'amount' => $item->rawAmount(),
                'currency' => $item->currency(),
                'formatted_unit_amount' => Cashier::formatAmount(
                    $item->quantity() && $item->quantity() > 0
                        ? (int) floor($item->rawAmount() / $item->quantity())
                        : $item->rawAmount(),
                    $item->currency(),
                    $locale,
                ),
                'formatted_amount' => Cashier::formatAmount($item->rawAmount(), $item->currency(), $locale),
            ],
            $invoice->items(),
        );

        $discounts = array_map(
            fn (Discount $discount): array => $this->formatDiscount($discount, $currency, $locale),
            $invoice->discounts(),
        );

        $taxes = array_map(
            fn (Tax $tax): array => [
                'name' => $tax->name(),
                'percent' => $tax->percent(),
                'amount' => $tax->rawAmount(),
                'inclusive' => $tax->inclusive(),
                'formatted_amount' => Cashier::formatAmount($tax->rawAmount(), $currency, $locale),
            ],
            $invoice->taxes(),
        );

        return [
            'meta' => [
                'locale' => $locale,
                'labels' => $translations,
            ],
            'invoice' => [
                'id' => $invoice->id(),
                'number' => $invoice->number(),
                'status' => $invoice->status(),
                'status_label' => $translations['statuses'][$invoice->status()] ?? ucfirst($invoice->status()),
                'date' => $invoice->date(),
                'dueDate' => $invoice->dueDate(),
                'formatted_date' => $this->formatDate($invoice->date(), $locale),
                'formatted_due_date' => $invoice->dueDate() ? $this->formatDate($invoice->dueDate(), $locale) : null,
                'currency' => $currency,
                'subtotal' => $stripeInvoice->subtotal,
                'formatted_subtotal' => Cashier::formatAmount($stripeInvoice->subtotal, $currency, $locale),
                'tax' => $stripeInvoice->tax ?? 0,
                'formatted_tax' => Cashier::formatAmount($stripeInvoice->tax ?? 0, $currency, $locale),
                'total' => $invoice->rawTotal(),
                'formatted_total' => Cashier::formatAmount($invoice->rawTotal(), $currency, $locale),
                'total_paid' => $stripeInvoice->amount_paid ?? $invoice->rawTotal(),
                'formatted_total_paid' => Cashier::formatAmount($stripeInvoice->amount_paid ?? $invoice->rawTotal(), $currency, $locale),
                'amount_due' => $stripeInvoice->amount_due ?? 0,
                'formatted_amount_due' => Cashier::formatAmount($stripeInvoice->amount_due ?? 0, $currency, $locale),
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
                'text' => $data['footer_text'] ?? ($translations['thank_you'] ?? null),
                'support_email' => $data['support_email'] ?? null,
            ],
        ];
    }

    /**
     * @return array{coupon: array{name: string|null, amountOff: int|null, percentOff: float|null, formatted_amount_off: string|null, formatted_label: string}}
     */
    private function formatDiscount(Discount $discount, string $currency, string $locale): array
    {
        $coupon = $discount->coupon();
        $amountOff = $coupon->amountOff();
        $percentOff = $coupon->percentOff();

        if ($amountOff !== null) {
            $formattedLabel = Cashier::formatAmount($amountOff, $currency, $locale);
            $formattedAmountOff = $formattedLabel;
        } elseif ($percentOff !== null) {
            $formattedLabel = sprintf('%.0f%%', $percentOff);
            $formattedAmountOff = null;
        } else {
            $formattedLabel = '';
            $formattedAmountOff = null;
        }

        return [
            'coupon' => [
                'name' => $coupon->name(),
                'amountOff' => $amountOff,
                'percentOff' => $percentOff,
                'formatted_amount_off' => $formattedAmountOff,
                'formatted_label' => $formattedLabel,
            ],
        ];
    }

    private function formatDate(\DateTimeImmutable $date, string $locale): string
    {
        if (!class_exists(\IntlDateFormatter::class)) {
            return $date->format('Y-m-d');
        }

        try {
            $formatter = new \IntlDateFormatter(
                $locale,
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::NONE,
            );

            return $formatter->format($date) ?: $date->format('Y-m-d');
        } catch (\Throwable) {
            return $date->format('Y-m-d');
        }
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
