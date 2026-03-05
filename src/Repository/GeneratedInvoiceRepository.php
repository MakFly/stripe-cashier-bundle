<?php

declare(strict_types=1);

namespace CashierBundle\Repository;

use CashierBundle\Entity\GeneratedInvoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GeneratedInvoice>
 */
class GeneratedInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeneratedInvoice::class);
    }

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
}
