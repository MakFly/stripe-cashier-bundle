<?php

declare(strict_types=1);

namespace CashierBundle\Repository;

use CashierBundle\Entity\Subscription;
use CashierBundle\Entity\SubscriptionItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for SubscriptionItem entities with subscription and price-scoped lookups.
 *
 * @extends ServiceEntityRepository<SubscriptionItem>
 */
class SubscriptionItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionItem::class);
    }

    public function findByStripeId(string $stripeId): ?SubscriptionItem
    {
        return $this->findOneBy(['stripeId' => $stripeId]);
    }

    /**
     * @return array<int, SubscriptionItem>
     */
    public function findBySubscription(Subscription $subscription): array
    {
        return $this->findBy(['subscription' => $subscription]);
    }

    /**
     * @return array<int, SubscriptionItem>
     */
    public function findBySubscriptionAndPrice(Subscription $subscription, string $stripePrice): array
    {
        return $this->findBy([
            'subscription' => $subscription,
            'stripePrice' => $stripePrice,
        ]);
    }

    public function save(SubscriptionItem $item, bool $flush = false): void
    {
        $this->getEntityManager()->persist($item);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SubscriptionItem $item, bool $flush = false): void
    {
        $this->getEntityManager()->remove($item);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
