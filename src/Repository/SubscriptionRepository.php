<?php

declare(strict_types=1);

namespace CashierBundle\Repository;

use CashierBundle\Entity\StripeCustomer;
use CashierBundle\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for Subscription entities with active-status and type-scoped lookups.
 *
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    public function findByStripeId(string $stripeId): ?Subscription
    {
        return $this->findOneBy(['stripeId' => $stripeId]);
    }

    /**
     * @return array<int, Subscription>
     */
    public function findActiveByCustomer(StripeCustomer $customer): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.customer = :customer')
            ->andWhere('s.stripeStatus IN (:statuses)')
            ->setParameter('customer', $customer)
            ->setParameter('statuses', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING,
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Subscription>
     */
    public function findByCustomerAndType(StripeCustomer $customer, string $type): array
    {
        return $this->findBy([
            'customer' => $customer,
            'type' => $type,
        ]);
    }

    public function findOneByCustomerAndType(StripeCustomer $customer, string $type): ?Subscription
    {
        return $this->findOneBy([
            'customer' => $customer,
            'type' => $type,
        ]);
    }

    /**
     * @return array<int, Subscription>
     */
    public function findActiveByCustomerAndType(StripeCustomer $customer, string $type): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.customer = :customer')
            ->andWhere('s.type = :type')
            ->andWhere('s.stripeStatus IN (:statuses)')
            ->setParameter('customer', $customer)
            ->setParameter('type', $type)
            ->setParameter('statuses', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING,
            ])
            ->getQuery()
            ->getResult();
    }

    public function save(Subscription $subscription, bool $flush = false): void
    {
        $this->getEntityManager()->persist($subscription);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Subscription $subscription, bool $flush = false): void
    {
        $this->getEntityManager()->remove($subscription);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
