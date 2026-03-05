<?php

declare(strict_types=1);

namespace CashierBundle\Repository;

use CashierBundle\Contract\BillableEntityInterface;
use CashierBundle\Entity\StripeCustomer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StripeCustomer>
 */
class StripeCustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StripeCustomer::class);
    }

    public function findByStripeId(string $stripeId): ?StripeCustomer
    {
        return $this->findOneBy(['stripeId' => $stripeId]);
    }

    public function findByBillable(BillableEntityInterface $billable): ?StripeCustomer
    {
        return $this->findOneBy(['billable' => $billable]);
    }

    public function save(StripeCustomer $customer, bool $flush = false): void
    {
        $this->getEntityManager()->persist($customer);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(StripeCustomer $customer, bool $flush = false): void
    {
        $this->getEntityManager()->remove($customer);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
