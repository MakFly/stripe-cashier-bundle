<?php

declare(strict_types=1);

namespace CashierBundle\Webhook\Handler;

use CashierBundle\Entity\StripeCustomer;
use CashierBundle\Entity\Subscription;
use CashierBundle\Event\SubscriptionCreatedEvent;
use CashierBundle\Repository\StripeCustomerRepository;
use CashierBundle\Repository\SubscriptionRepository;
use Stripe\Event;
use Stripe\Subscription as StripeSubscription;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles customer.subscription.created by persisting a new local Subscription entity.
 */
final readonly class SubscriptionCreatedHandler extends AbstractWebhookHandler
{
    public function __construct(
        private StripeCustomerRepository $customerRepository,
        private SubscriptionRepository $subscriptionRepository,
        private EventDispatcherInterface $dispatcher,
        #[Autowire(param: 'cashier.default_subscription_type')]
        private string $defaultSubscriptionType = 'default',
    ) {
    }

    public function handles(): array
    {
        return ['customer.subscription.created'];
    }

    public function handle(Event $event): void
    {
        $stripeSubscription = $this->getStripeSubscription($event);
        if (!$stripeSubscription instanceof StripeSubscription) {
            return;
        }

        $customer = $this->customerRepository->findOneBy([
            'stripeId' => $stripeSubscription->customer,
        ]);

        if (!$customer instanceof StripeCustomer) {
            return;
        }

        $subscription = $this->createSubscriptionFromStripe($stripeSubscription, $customer);
        $this->subscriptionRepository->save($subscription, true);

        $this->dispatcher->dispatch(new SubscriptionCreatedEvent($subscription));
    }

    /**
     * Maps a Stripe Subscription to a local Subscription entity, including price, quantity,
     * trial end date, and scheduled cancellation date when present.
     */
    private function createSubscriptionFromStripe(
        StripeSubscription $stripeSubscription,
        StripeCustomer $customer,
    ): Subscription {
        $subscription = new Subscription();
        $subscription->setCustomer($customer);
        $subscription->setStripeId($stripeSubscription->id);
        $subscription->setStripeStatus($stripeSubscription->status);
        $subscription->setType($this->defaultSubscriptionType);

        if (isset($stripeSubscription->items->data[0])) {
            $subscription->setStripePrice($stripeSubscription->items->data[0]->price->id);
            $subscription->setQuantity($stripeSubscription->items->data[0]->quantity ?? 1);
        }

        if ($stripeSubscription->trial_end) {
            $subscription->setTrialEndsAt(new \DateTimeImmutable('@' . $stripeSubscription->trial_end));
        }

        if ($stripeSubscription->cancel_at_period_end && $stripeSubscription->cancel_at) {
            $subscription->setEndsAt(new \DateTimeImmutable('@' . $stripeSubscription->cancel_at));
        }

        return $subscription;
    }
}
