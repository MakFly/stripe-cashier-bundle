<?php

declare(strict_types=1);

namespace CashierBundle\Webhook\Handler;

use CashierBundle\Repository\StripeCustomerRepository;
use CashierBundle\Repository\SubscriptionRepository;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Event;

final readonly class CheckoutSessionCompletedHandler extends AbstractWebhookHandler
{
    public function __construct(
        private StripeCustomerRepository $customerRepository,
        private SubscriptionRepository $subscriptionRepository,
    ) {
    }

    public function handles(): array
    {
        return ['checkout.session.completed'];
    }

    public function handle(Event $event): void
    {
        $session = $event->data->object;
        if (!$session instanceof StripeSession) {
            return;
        }

        if ($session->mode === 'subscription' && isset($session->subscription)) {
            $subscription = $this->subscriptionRepository->findOneBy([
                'stripeId' => $session->subscription,
            ]);

            // Subscription will be created by SubscriptionCreatedHandler
            // This handler can be used for additional logic like:
            // - Sending welcome emails
            // - Granting access/resources
            // - Tracking conversions
        }

        if ($session->mode === 'payment' && isset($session->customer)) {
            $customer = $this->customerRepository->findOneBy([
                'stripeId' => $session->customer,
            ]);

            // Handle one-time payment completion
            // Application-specific logic would go here
        }
    }
}
