<?php

declare(strict_types=1);

namespace CashierBundle\Event;

use CashierBundle\Entity\Subscription;
use Symfony\Contracts\EventDispatcher\Event;

/** Dispatched when a new Stripe subscription is created and persisted. */
final class SubscriptionCreatedEvent extends Event
{
    public function __construct(
        public readonly Subscription $subscription,
    ) {
    }

    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }
}
