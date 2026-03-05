<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Integration\Invoice;

use CashierBundle\Contract\InvoiceRendererInterface;
use CashierBundle\Model\Invoice;
use CashierBundle\Service\Invoice\DefaultInvoiceLocaleResolver;
use CashierBundle\Service\Invoice\DefaultInvoiceTranslationProvider;
use CashierBundle\Service\InvoiceViewFactory;
use PHPUnit\Framework\TestCase;
use Stripe\Collection;
use Stripe\Customer;
use Stripe\Invoice as StripeInvoice;
use Stripe\InvoiceLineItem;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class InvoiceTemplateRenderingIntegrationTest extends TestCase
{
    public function testTwigTemplateRendersFrenchLabels(): void
    {
        $customer = new Customer('cus_test');
        $customer->preferred_locales = ['fr-FR'];
        $customer->name = 'Alice Martin';

        $lineItem = new InvoiceLineItem('il_test');
        $lineItem->description = 'Abonnement';
        $lineItem->amount = 1990;
        $lineItem->currency = 'eur';
        $lineItem->quantity = 1;

        $stripeInvoice = new StripeInvoice('in_test');
        $stripeInvoice->customer = $customer;
        $stripeInvoice->number = 'QAPYTJB5-0003';
        $stripeInvoice->status = 'paid';
        $stripeInvoice->currency = 'eur';
        $stripeInvoice->subtotal = 1990;
        $stripeInvoice->total = 1990;
        $stripeInvoice->amount_paid = 1990;
        $stripeInvoice->amount_due = 0;
        $stripeInvoice->created = strtotime('2026-03-05 12:00:00');
        /** @var Collection<InvoiceLineItem> $lines */
        $lines = Collection::constructFrom([
            'data' => [$lineItem],
            'has_more' => false,
        ]);
        $stripeInvoice->lines = $lines;

        $invoice = new Invoice($stripeInvoice, $this->createStub(InvoiceRendererInterface::class));
        $view = (new InvoiceViewFactory(
            new DefaultInvoiceLocaleResolver('en', ['en', 'fr']),
            new DefaultInvoiceTranslationProvider(),
        ))->create($invoice);

        $loader = new FilesystemLoader(__DIR__ . '/../../../src/Resources/views');
        $twig = new Environment($loader);
        $html = $twig->render('invoice/default.html.twig', $view);

        self::assertStringContainsString('Facture', $html);
        self::assertStringContainsString('Facturé à', $html);
        self::assertStringContainsString('Date d&#039;émission', $html);
        self::assertStringContainsString('Abonnement', $html);
        self::assertStringContainsString('19,90', $html);
    }
}
