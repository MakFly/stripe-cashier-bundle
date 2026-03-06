<?php

declare(strict_types=1);

namespace CashierBundle\Message;

/** Message dispatched to asynchronously sync Stripe customer details to the local entity. */
final class SyncCustomerDetailsMessage
{
    public function __construct(
        public readonly string $stripeId,
    ) {
    }
}
