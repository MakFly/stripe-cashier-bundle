<?php

declare(strict_types=1);

namespace CashierBundle\Webhook\Handler;

use CashierBundle\Repository\StripeCustomerRepository;
use Stripe\Event;
use Stripe\PaymentMethod as StripePaymentMethod;

/**
 * Handles payment_method.automatically_updated by refreshing the stored card type and last four digits.
 */
final readonly class PaymentMethodUpdatedHandler extends AbstractWebhookHandler
{
    public function __construct(
        private StripeCustomerRepository $customerRepository,
    ) {
    }

    public function handles(): array
    {
        return ['payment_method.automatically_updated'];
    }

    public function handle(Event $event): void
    {
        $object = $event->data->object;
        if (!$object instanceof StripePaymentMethod) {
            return;
        }

        if (!isset($object->customer)) {
            return;
        }

        $customer = $this->customerRepository->findOneBy([
            'stripeId' => $object->customer,
        ]);

        if ($customer === null) {
            return;
        }

        $customer->setPmType($object->type);
        $customer->setPmLastFour($object->card->last4 ?? null);
        $this->customerRepository->save($customer, true);
    }
}
