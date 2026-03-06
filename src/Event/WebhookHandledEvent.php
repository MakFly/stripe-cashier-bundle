<?php

declare(strict_types=1);

namespace CashierBundle\Event;

use Stripe\Event as StripeEvent;
use Symfony\Contracts\EventDispatcher\Event;

/** Dispatched after a Stripe webhook event has been fully processed by its handler. */
final class WebhookHandledEvent extends Event
{
    public function __construct(
        public readonly StripeEvent $stripeEvent,
    ) {
    }

    public function getStripeEvent(): StripeEvent
    {
        return $this->stripeEvent;
    }
}
