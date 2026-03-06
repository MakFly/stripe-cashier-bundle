<?php

declare(strict_types=1);

namespace CashierBundle\Webhook\Handler;

use CashierBundle\Event\SubscriptionDeletedEvent;
use CashierBundle\Repository\SubscriptionRepository;
use Stripe\Event;
use Stripe\Subscription as StripeSubscription;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles customer.subscription.deleted by marking the local Subscription as canceled and dispatching an event.
 */
final readonly class SubscriptionDeletedHandler extends AbstractWebhookHandler
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function handles(): array
    {
        return ['customer.subscription.deleted'];
    }

    public function handle(Event $event): void
    {
        $stripeSubscription = $this->getStripeSubscription($event);
        if (!$stripeSubscription instanceof StripeSubscription) {
            return;
        }

        $subscription = $this->subscriptionRepository->findOneBy([
            'stripeId' => $stripeSubscription->id,
        ]);

        if ($subscription === null) {
            return;
        }

        $subscription->setStripeStatus($stripeSubscription->status);
        $this->subscriptionRepository->save($subscription, true);

        $this->dispatcher->dispatch(new SubscriptionDeletedEvent($subscription));
    }
}
