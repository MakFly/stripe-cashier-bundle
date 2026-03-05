<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Model;

use CashierBundle\Contract\InvoiceRendererInterface;
use CashierBundle\Model\Invoice;
use CashierBundle\Model\InvoiceLineItem;
use PHPUnit\Framework\TestCase;
use Stripe\Collection;
use Stripe\Invoice as StripeInvoice;
use Stripe\InvoiceLineItem as StripeInvoiceLineItem;
use Symfony\Component\HttpFoundation\Response;

final class InvoiceTest extends TestCase
{
    public function testItemsSupportStripeAutoPagingIteratorGenerator(): void
    {
        $stripeInvoice = new StripeInvoice('in_test');
        $stripeInvoice->currency = 'eur';
        $stripeInvoice->total = 1000;
        $stripeInvoice->created = time();

        $firstLine = new StripeInvoiceLineItem('il_1');
        $firstLine->description = 'Product A';
        $firstLine->amount = 700;
        $firstLine->currency = 'eur';
        $firstLine->quantity = 1;

        $secondLine = new StripeInvoiceLineItem('il_2');
        $secondLine->description = 'Product B';
        $secondLine->amount = 300;
        $secondLine->currency = 'eur';
        $secondLine->quantity = 2;

        /** @var Collection<StripeInvoiceLineItem> $lines */
        $lines = Collection::constructFrom([
            'data' => [$firstLine, $secondLine],
            'has_more' => false,
        ]);

        $stripeInvoice->lines = $lines;

        $invoice = new Invoice($stripeInvoice, new class () implements InvoiceRendererInterface {
            public function render(Invoice $invoice, array $data = []): Response
            {
                return new Response();
            }

            public function renderBinary(Invoice $invoice, array $data = []): string
            {
                return '';
            }

            public function stream(Invoice $invoice, array $data = []): Response
            {
                return new Response();
            }
        });

        $items = $invoice->items();

        self::assertCount(2, $items);
        self::assertContainsOnlyInstancesOf(InvoiceLineItem::class, $items);
        self::assertSame('Product A', $items[0]->description());
        self::assertSame('Product B', $items[1]->description());
    }
}
