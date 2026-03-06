<?php

declare(strict_types=1);

namespace CashierBundle\Message;

/** Message dispatched to asynchronously process a Stripe invoice, optionally triggering auto-pay. */
final class ProcessInvoiceMessage
{
    public function __construct(
        public readonly string $invoiceId,
        public readonly bool $autoPay = false,
    ) {
    }
}
