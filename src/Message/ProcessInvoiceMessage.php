<?php

declare(strict_types=1);

namespace CashierBundle\Message;

final class ProcessInvoiceMessage
{
    public function __construct(
        public readonly string $invoiceId,
        public readonly bool $autoPay = false
    ) {}
}
