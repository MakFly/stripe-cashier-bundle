<?php

declare(strict_types=1);

namespace CashierBundle\Webhook\Handler;

use CashierBundle\Event\SubscriptionUpdatedEvent;
use CashierBundle\Repository\SubscriptionRepository;
use Stripe\Event;
use Stripe\Subscription as StripeSubscription;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class SubscriptionUpdatedHandler extends AbstractWebhookHandler
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function handles(): array
    {
        return ['customer.subscription.updated'];
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

        $this->updateSubscriptionFromStripe($subscription, $stripeSubscription);
        $this->subscriptionRepository->save($subscription, true);

        $this->dispatcher->dispatch(new SubscriptionUpdatedEvent($subscription));
    }

    private function updateSubscriptionFromStripe(
        \CashierBundle\Entity\Subscription $subscription,
        StripeSubscription $stripeSubscription
    ): void {
        $subscription->setStripeStatus($stripeSubscription->status);

        if (isset($stripeSubscription->items->data[0])) {
            $subscription->setStripePrice($stripeSubscription->items->data[0]->price->id);
            $subscription->setQuantity($stripeSubscription->items->data[0]->quantity ?? 1);
        }

        if ($stripeSubscription->trial_end) {
            $subscription->setTrialEndsAt(new \DateTimeImmutable('@' . $stripeSubscription->trial_end));
        } else {
            $subscription->setTrialEndsAt(null);
        }

        if ($stripeSubscription->cancel_at_period_end && $stripeSubscription->cancel_at) {
            $subscription->setEndsAt(new \DateTimeImmutable('@' . $stripeSubscription->cancel_at));
        } elseif (!$stripeSubscription->cancel_at_period_end) {
            $subscription->setEndsAt(null);
        }
    }
}
