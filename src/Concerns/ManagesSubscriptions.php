<?php

declare(strict_types=1);

namespace CashierBundle\Concerns;

use CashierBundle\Entity\Subscription;
use CashierBundle\Service\SubscriptionBuilder;
use Doctrine\Common\Collections\Collection;

/**
 * @implements \CashierBundle\Contract\BillableInterface
 */
trait ManagesSubscriptions
{
    /**
     * Get all of the subscriptions for the billable entity.
     *
     * @return Collection<int, Subscription>
     */
    public function subscriptions(): Collection
    {
        return $this->getCashierService('subscription')->all($this);
    }

    /**
     * Get a subscription instance by type.
     */
    public function subscription(string $type = 'default'): ?Subscription
    {
        return $this->getCashierService('subscription')->get($this, $type);
    }

    /**
     * Determine if the billable entity is on trial.
     *
     * @param string $type The type of subscription
     * @param string|null $price The specific price to check
     */
    public function onTrial(string $type = 'default', ?string $price = null): bool
    {
        return $this->getCashierService('subscription')->onTrial($this, $type, $price);
    }

    /**
     * Determine if the billable entity has a given subscription.
     *
     * @param string $type The type of subscription
     * @param string|null $price The specific price to check
     */
    public function subscribed(string $type = 'default', ?string $price = null): bool
    {
        return $this->getCashierService('subscription')->subscribed($this, $type, $price);
    }

    /**
     * Determine if the entity is on a generic trial.
     */
    public function onGenericTrial(): bool
    {
        return $this->getCashierService('subscription')->onGenericTrial($this);
    }

    /**
     * Begin creating a new subscription.
     *
     * @param string $type The type of subscription
     * @param string|array<string> $prices The price or prices for the subscription
     */
    public function newSubscription(string $type, string|array $prices = []): SubscriptionBuilder
    {
        return $this->getCashierService('subscription')->newSubscription($this, $type, $prices);
    }

    /**
     * Get a Cashier service by name.
     *
     * @template T of object
     *
     * @param string $service
     *
     * @return T
     */
    abstract protected function getCashierService(string $service): object;
}
