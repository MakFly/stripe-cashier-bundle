<?php

declare(strict_types=1);

namespace CashierBundle\Message;

final class RetryPaymentMessage
{
    public function __construct(
        public readonly string $invoiceId
    ) {}
}
