<?php

declare(strict_types=1);

namespace CashierBundle\Webhook\Handler;

use CashierBundle\Contract\WebhookHandlerInterface;
use Stripe\Event;

abstract readonly class AbstractWebhookHandler implements WebhookHandlerInterface
{
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

    protected function getStripeSubscription(Event $event): ?\Stripe\Subscription
    {
        $object = $event->data->object;

        if ($object instanceof \Stripe\Subscription) {
            return $object;
        }

        return null;
    }

    protected function getStripeInvoice(Event $event): ?\Stripe\Invoice
    {
        $object = $event->data->object;

        if ($object instanceof \Stripe\Invoice) {
            return $object;
        }

        return null;
    }
}
