<?php

declare(strict_types=1);

namespace CashierBundle\Message;

final class SyncCustomerDetailsMessage
{
    public function __construct(
        public readonly string $stripeId,
    ) {
    }
}
