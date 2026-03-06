<?php

declare(strict_types=1);

namespace CashierBundle\Message;

/** Message dispatched to asynchronously cancel a subscription immediately or at period end. */
final class CancelSubscriptionMessage
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly bool $atPeriodEnd = true,
    ) {
    }
}
