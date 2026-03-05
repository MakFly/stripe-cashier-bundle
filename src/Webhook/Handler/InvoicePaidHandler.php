<?php

declare(strict_types=1);

namespace CashierBundle\Webhook\Handler;

use CashierBundle\Event\PaymentSucceededEvent;
use Stripe\Event;
use Stripe\Invoice as StripeInvoice;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class InvoicePaidHandler extends AbstractWebhookHandler
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function handles(): array
    {
        return ['invoice.paid'];
    }

    public function handle(Event $event): void
    {
        $invoice = $this->getStripeInvoice($event);
        if (!$invoice instanceof StripeInvoice) {
            return;
        }

        $this->dispatcher->dispatch(new PaymentSucceededEvent(
            $invoice->customer,
            $invoice->payment_intent,
            $invoice->amount_paid,
            $invoice->currency,
        ));
    }
}
