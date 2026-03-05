<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service;

use CashierBundle\Model\Invoice;
use CashierBundle\Service\Invoice\DefaultInvoiceLocaleResolver;
use CashierBundle\Service\Invoice\DefaultInvoiceTranslationProvider;
use CashierBundle\Service\InvoiceViewFactory;
use PHPUnit\Framework\TestCase;
use Stripe\Collection;
use Stripe\Customer;
use Stripe\Invoice as StripeInvoice;
use Stripe\InvoiceLineItem;

final class InvoiceViewFactoryTest extends TestCase
{
    public function testCreateBuildsFrenchLocalizedView(): void
    {
        $customer = new Customer('cus_test');
        $customer->name = 'Alice Martin';
        $customer->email = 'alice@example.test';
        $customer->preferred_locales = ['fr-FR'];

        $lineItem = new InvoiceLineItem('il_test');
        $lineItem->description = 'Réservation premium';
        $lineItem->amount = 4990;
        $lineItem->currency = 'eur';
        $lineItem->quantity = 1;

        $stripeInvoice = new StripeInvoice('in_test');
        $stripeInvoice->customer = $customer;
        $stripeInvoice->number = 'INV-0003';
        $stripeInvoice->status = 'paid';
        $stripeInvoice->currency = 'eur';
        $stripeInvoice->subtotal = 4990;
        $stripeInvoice->total = 4990;
        $stripeInvoice->amount_paid = 4990;
        $stripeInvoice->amount_due = 0;
        $stripeInvoice->created = strtotime('2026-03-05 12:00:00');
        /** @var Collection<InvoiceLineItem> $lines */
        $lines = Collection::constructFrom([
            'data' => [$lineItem],
            'has_more' => false,
        ]);
        $stripeInvoice->lines = $lines;

        $view = (new InvoiceViewFactory(
            new DefaultInvoiceLocaleResolver('en', ['en', 'fr']),
            new DefaultInvoiceTranslationProvider(),
        ))->create(new Invoice($stripeInvoice, $this->createStub(\CashierBundle\Contract\InvoiceRendererInterface::class)));

        self::assertSame('fr', $view['meta']['locale']);
        self::assertSame('Facture', $view['meta']['labels']['title']);
        self::assertSame('Payée', $view['invoice']['status_label']);
        self::assertStringContainsString('49,90', $view['invoice']['formatted_total']);
        self::assertStringContainsString('€', $view['invoice']['formatted_total']);
        self::assertStringContainsString('49,90', $view['invoice']['items'][0]['formatted_amount']);
    }
}
