<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Webhook\Handler;

use CashierBundle\Event\PaymentSucceededEvent;
use CashierBundle\Webhook\Handler\InvoicePaidHandler;
use PHPUnit\Framework\TestCase;
use Stripe\Event;
use Stripe\Invoice;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/** Test suite for InvoicePaidHandler. */
final class InvoicePaidHandlerTest extends TestCase
{
    public function testHandleDispatchesPaymentSucceededEvent(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function ($event): bool {
                return $event instanceof PaymentSucceededEvent
                    && $event->customerId === 'cus_123'
                    && $event->paymentIntentId === 'pi_123'
                    && $event->amount === 1999
                    && $event->currency === 'eur'
                    && $event->invoiceId === 'in_123';
            }));

        $handler = new InvoicePaidHandler($dispatcher);
        $handler->handle($this->makeInvoicePaidEvent());
    }

    public function testHandleIgnoresNonInvoicePayloads(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $event = new Event('evt_1');
        /** @phpstan-ignore-next-line Stripe SDK uses dynamic assignment in tests */
        $event->data = (object) ['object' => (object) ['id' => 'something_else']];

        $handler = new InvoicePaidHandler($dispatcher);
        $handler->handle($event);
    }

    private function makeInvoicePaidEvent(): Event
    {
        $invoice = new Invoice('in_123');
        $invoice->customer = 'cus_123';
        $invoice->payment_intent = 'pi_123';
        $invoice->amount_paid = 1999;
        $invoice->currency = 'eur';

        $event = new Event('evt_invoice_paid');
        /** @phpstan-ignore-next-line Stripe SDK uses dynamic assignment in tests */
        $event->data = (object) ['object' => $invoice];

        return $event;
    }
}
