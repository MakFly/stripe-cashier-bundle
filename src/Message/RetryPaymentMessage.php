<?php

declare(strict_types=1);

namespace CashierBundle\Message;

/** Message dispatched to asynchronously retry payment collection for a Stripe invoice. */
final class RetryPaymentMessage
{
    public function __construct(
        public readonly string $invoiceId,
    ) {
    }
}
