<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Contract\BillableInterface;
use CashierBundle\Entity\StripeCustomer;
use CashierBundle\Entity\Subscription;
use CashierBundle\Entity\SubscriptionItem;
use CashierBundle\Exception\SubscriptionUpdateFailureException;
use CashierBundle\Repository\StripeCustomerRepository;
use CashierBundle\Repository\SubscriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Stripe\Subscription as StripeSubscription;

/**
 * Manages subscription creation, updates, cancellation, and local sync with Stripe.
 *
 * @phpstan-type SubscriptionItemOptions array{price: string, quantity: int|null}
 */
class SubscriptionService
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly SubscriptionRepository $repository,
        private readonly StripeCustomerRepository $customerRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<SubscriptionItemOptions> $items
     * @param array<string, mixed> $options
     */
    public function create(BillableInterface $billable, string $type, array $items, array $options = []): Subscription
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            throw new \RuntimeException('Billable must have a Stripe customer ID.');
        }

        $customer = $this->customerRepository->findByStripeId($stripeId);
        if ($customer === null) {
            throw new \RuntimeException('Local customer not found.');
        }

        $payload = array_merge([
            'customer' => $stripeId,
            'items' => array_map(fn ($item) => [
                'price' => $item['price'],
                'quantity' => $item['quantity'] ?? 1,
            ], $items),
            'expand' => ['latest_invoice.payment_intent'],
        ], $options);

        $stripeSubscription = $this->stripe->subscriptions->create($payload);

        return $this->syncSubscription($stripeSubscription, $customer, $type);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function update(Subscription $subscription, array $options = []): Subscription
    {
        try {
            $stripeSubscription = $this->stripe->subscriptions->update(
                $subscription->getStripeId(),
                $options,
            );

            return $this->syncSubscription($stripeSubscription, $subscription->getCustomer(), $subscription->getType());
        } catch (\Stripe\Exception\ExceptionInterface $e) {
            throw new SubscriptionUpdateFailureException(
                sprintf('Failed to update subscription %s: %s', $subscription->getStripeId(), $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function all(BillableInterface $billable): Collection
    {
        $customer = $this->getLocalCustomer($billable);
        if ($customer === null) {
            return new ArrayCollection();
        }

        return new ArrayCollection($this->repository->findBy(['customer' => $customer], ['createdAt' => 'DESC']));
    }

    public function get(BillableInterface $billable, string $type = 'default'): ?Subscription
    {
        $customer = $this->getLocalCustomer($billable);
        if ($customer === null) {
            return null;
        }

        return $this->repository->findOneByCustomerAndType($customer, $type);
    }

    public function onTrial(BillableInterface $billable, string $type = 'default', ?string $price = null): bool
    {
        $subscription = $this->get($billable, $type);
        if ($subscription === null) {
            return false;
        }

        if ($price !== null && $subscription->getStripePrice() !== $price) {
            return false;
        }

        return $subscription->onTrial();
    }

    public function subscribed(BillableInterface $billable, string $type = 'default', ?string $price = null): bool
    {
        $subscription = $this->get($billable, $type);
        if ($subscription === null) {
            return false;
        }

        if ($price !== null && $subscription->getStripePrice() !== $price) {
            return false;
        }

        return $subscription->valid();
    }

    public function onGenericTrial(BillableInterface $billable): bool
    {
        $customer = $this->getLocalCustomer($billable);

        return $customer?->onGenericTrial() ?? false;
    }

    /**
     * @param string|array<int, string> $prices
     */
    public function newSubscription(BillableInterface $billable, string $type, string|array $prices = []): SubscriptionBuilder
    {
        $builder = new SubscriptionBuilder(
            $billable,
            $type,
            $this->stripe,
            $this->entityManager,
            $this->repository,
        );

        if (is_string($prices) && $prices !== '') {
            $builder->price($prices);
        }

        if (is_array($prices)) {
            foreach ($prices as $price) {
                if (is_string($price) && $price !== '') {
                    $builder->price($price);
                }
            }
        }

        return $builder;
    }

    public function cancel(Subscription $subscription, bool $immediately = false): Subscription
    {
        try {
            if ($immediately) {
                $stripeSubscription = $this->stripe->subscriptions->cancel(
                    $subscription->getStripeId(),
                );
            } else {
                $stripeSubscription = $this->stripe->subscriptions->update(
                    $subscription->getStripeId(),
                    ['cancel_at_period_end' => true],
                );
            }

            $synced = $this->syncSubscription($stripeSubscription, $subscription->getCustomer(), $subscription->getType());

            if (!$immediately && $stripeSubscription->cancel_at_period_end) {
                $synced->setEndsAt(
                    \DateTimeImmutable::createFromFormat('U', (string) $stripeSubscription->current_period_end) ?: null,
                );
                $this->repository->save($synced, true);
            }

            return $synced;
        } catch (\Stripe\Exception\ExceptionInterface $e) {
            throw new SubscriptionUpdateFailureException(
                sprintf('Failed to cancel subscription %s: %s', $subscription->getStripeId(), $e->getMessage()),
                0,
                $e,
            );
        }
    }

    public function resume(Subscription $subscription): Subscription
    {
        if (!$subscription->onGracePeriod()) {
            throw new \RuntimeException('Cannot resume a subscription that is not on a grace period.');
        }

        try {
            $stripeSubscription = $this->stripe->subscriptions->update(
                $subscription->getStripeId(),
                [
                    'cancel_at_period_end' => false,
                    'trial_end' => 'now',
                ],
            );

            $synced = $this->syncSubscription($stripeSubscription, $subscription->getCustomer(), $subscription->getType());
            $synced->setEndsAt(null);
            $this->repository->save($synced, true);

            return $synced;
        } catch (\Stripe\Exception\ExceptionInterface $e) {
            throw new SubscriptionUpdateFailureException(
                sprintf('Failed to resume subscription %s: %s', $subscription->getStripeId(), $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function swap(Subscription $subscription, string $price, array $options = []): Subscription
    {
        $items = $subscription->getItems();

        if ($items->isEmpty()) {
            throw new \RuntimeException('Subscription has no items.');
        }

        $firstItem = $items->first();
        if (!$firstItem instanceof SubscriptionItem) {
            throw new \RuntimeException('First item is not a SubscriptionItem.');
        }

        $payload = array_merge([
            'items' => [
                [
                    'id' => $firstItem->getStripeId(),
                    'price' => $price,
                ],
            ],
            'proration_behavior' => 'create_prorations',
            'expand' => ['latest_invoice.payment_intent'],
        ], $options);

        try {
            $stripeSubscription = $this->stripe->subscriptions->update(
                $subscription->getStripeId(),
                $payload,
            );

            return $this->syncSubscription($stripeSubscription, $subscription->getCustomer(), $subscription->getType());
        } catch (\Stripe\Exception\ExceptionInterface $e) {
            throw new SubscriptionUpdateFailureException(
                sprintf('Failed to swap subscription %s: %s', $subscription->getStripeId(), $e->getMessage()),
                0,
                $e,
            );
        }
    }

    public function updateQuantity(Subscription $subscription, int $quantity): Subscription
    {
        $items = $subscription->getItems();

        if ($items->isEmpty()) {
            throw new \RuntimeException('Subscription has no items.');
        }

        $firstItem = $items->first();
        if (!$firstItem instanceof SubscriptionItem) {
            throw new \RuntimeException('First item is not a SubscriptionItem.');
        }

        try {
            $stripeSubscription = $this->stripe->subscriptions->update(
                $subscription->getStripeId(),
                [
                    'items' => [
                        [
                            'id' => $firstItem->getStripeId(),
                            'quantity' => $quantity,
                        ],
                    ],
                    'proration_behavior' => 'create_prorations',
                ],
            );

            return $this->syncSubscription($stripeSubscription, $subscription->getCustomer(), $subscription->getType());
        } catch (\Stripe\Exception\ExceptionInterface $e) {
            throw new SubscriptionUpdateFailureException(
                sprintf('Failed to update quantity for subscription %s: %s', $subscription->getStripeId(), $e->getMessage()),
                0,
                $e,
            );
        }
    }

    private function syncSubscription(StripeSubscription $stripeSubscription, StripeCustomer $customer, string $type): Subscription
    {
        $subscription = $this->repository->findByStripeId($stripeSubscription->id);

        if ($subscription === null) {
            $subscription = new Subscription();
            $subscription->setCustomer($customer);
            $subscription->setType($type);
            $subscription->setStripeId($stripeSubscription->id);
        }

        $subscription->setStripeStatus($stripeSubscription->status);

        if (isset($stripeSubscription->items->data[0])) {
            $subscription->setStripePrice($stripeSubscription->items->data[0]->price->id);
            $subscription->setQuantity($stripeSubscription->items->data[0]->quantity ?? 1);
        }

        if ($stripeSubscription->trial_end !== null && $stripeSubscription->trial_end > time()) {
            $subscription->setTrialEndsAt(
                \DateTimeImmutable::createFromFormat('U', (string) $stripeSubscription->trial_end) ?: null,
            );
        } else {
            $subscription->setTrialEndsAt(null);
        }

        if ($stripeSubscription->cancel_at_period_end && $stripeSubscription->cancel_at !== null) {
            $subscription->setEndsAt(
                \DateTimeImmutable::createFromFormat('U', (string) $stripeSubscription->cancel_at) ?: null,
            );
        } elseif ($stripeSubscription->canceled_at !== null) {
            $subscription->setEndsAt(
                \DateTimeImmutable::createFromFormat('U', (string) $stripeSubscription->canceled_at) ?: null,
            );
        }

        $this->repository->save($subscription, false);

        foreach ($stripeSubscription->items->data as $stripeItem) {
            $item = $subscription->getItems()->filter(
                fn (SubscriptionItem $i) => $i->getStripeId() === $stripeItem->id,
            )->first() ?: new SubscriptionItem();

            if (!$item instanceof SubscriptionItem) {
                $item = new SubscriptionItem();
                $item->setSubscription($subscription);
            }

            $item->setStripeId($stripeItem->id);
            $item->setStripeProduct($stripeItem->price->product);
            $item->setStripePrice($stripeItem->price->id);
            $item->setQuantity($stripeItem->quantity ?? 1);

            $this->entityManager->persist($item);
        }

        $this->entityManager->flush();

        return $subscription;
    }

    private function getLocalCustomer(BillableInterface $billable): ?StripeCustomer
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            return null;
        }

        return $this->customerRepository->findByStripeId($stripeId);
    }
}
