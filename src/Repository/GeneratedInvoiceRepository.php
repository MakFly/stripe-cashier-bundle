<?php

declare(strict_types=1);

namespace CashierBundle\Repository;

use CashierBundle\Entity\GeneratedInvoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for GeneratedInvoice entities, providing lookup by Stripe invoice ID and resource.
 *
 * @extends ServiceEntityRepository<GeneratedInvoice>
 */
class GeneratedInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeneratedInvoice::class);
    }

    /** @param string|null $stripeInvoiceId */
    public function findOneForStripeInvoice(?string $stripeInvoiceId): ?GeneratedInvoice
    {
        if ($stripeInvoiceId === null || $stripeInvoiceId === '') {
            return null;
        }

        return $this->findOneBy(['stripeInvoiceId' => $stripeInvoiceId]);
    }

    public function save(GeneratedInvoice $invoice, bool $flush = false): void
    {
        $this->getEntityManager()->persist($invoice);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneForResource(?string $resourceType, string|int|null $resourceId): ?GeneratedInvoice
    {
        if ($resourceType === null || $resourceType === '' || $resourceId === null || $resourceId === '') {
            return null;
        }

        return $this->findOneBy([
            'resourceType' => $resourceType,
            'resourceId' => (string) $resourceId,
        ], ['id' => 'DESC']);
    }
}
