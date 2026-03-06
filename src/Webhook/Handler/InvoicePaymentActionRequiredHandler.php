<?php

declare(strict_types=1);

namespace CashierBundle\Webhook\Handler;

use Stripe\Event;
use Stripe\Invoice as StripeInvoice;

/**
 * Handles invoice.payment_action_required for invoices awaiting customer authentication.
 */
final readonly class InvoicePaymentActionRequiredHandler extends AbstractWebhookHandler
{
    public function handles(): array
    {
        return ['invoice.payment_action_required'];
    }

    public function handle(Event $event): void
    {
        $invoice = $this->getStripeInvoice($event);
        if (!$invoice instanceof StripeInvoice) {
            return;
        }

        // Log or notify about payment action required
        // Application-specific logic would go here
        // For example: send email to customer, create notification, etc.
    }
}
