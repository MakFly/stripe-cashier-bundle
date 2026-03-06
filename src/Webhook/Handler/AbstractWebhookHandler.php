<?php

declare(strict_types=1);

namespace CashierBundle\Webhook\Handler;

use CashierBundle\Contract\WebhookHandlerInterface;
use Stripe\Event;

/**
 * Base class providing Stripe object extraction helpers for webhook handlers.
 */
abstract readonly class AbstractWebhookHandler implements WebhookHandlerInterface
{
    /**
     * Retrieves the Stripe Customer from the event object or its customer reference.
     */
    protected function getStripeCustomer(Event $event): ?\Stripe\Customer
    {
        $object = $event->data->object;

        if ($object instanceof \Stripe\Customer) {
            return $object;
        }

        if (isset($object->customer)) {
            return $object->customer;
        }

        return null;
    }

    /**
     * Retrieves the Stripe Subscription from the event object, or null if absent.
     */
    protected function getStripeSubscription(Event $event): ?\Stripe\Subscription
    {
        $object = $event->data->object;

        if ($object instanceof \Stripe\Subscription) {
            return $object;
        }

        return null;
    }

    /**
     * Retrieves the Stripe Invoice from the event object, or null if absent.
     */
    protected function getStripeInvoice(Event $event): ?\Stripe\Invoice
    {
        $object = $event->data->object;

        if ($object instanceof \Stripe\Invoice) {
            return $object;
        }

        return null;
    }
}
