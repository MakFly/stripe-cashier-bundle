<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Contract\InvoiceRendererInterface;
use CashierBundle\Contract\InvoiceStorageInterface;
use CashierBundle\Entity\GeneratedInvoice;
use CashierBundle\Event\PaymentSucceededEvent;
use CashierBundle\Model\Invoice;
use CashierBundle\Repository\GeneratedInvoiceRepository;
use CashierBundle\Repository\StripeCustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

final readonly class InvoiceArchiveService
{
    public function __construct(
        private StripeClient $stripe,
        private InvoiceRendererInterface $renderer,
        private InvoiceStorageInterface $storage,
        private StripeCustomerRepository $stripeCustomerRepository,
        private GeneratedInvoiceRepository $generatedInvoiceRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function archiveFromPaymentSuccess(PaymentSucceededEvent $event): ?GeneratedInvoice
    {
        if ($event->getInvoiceId() === null) {
            return null;
        }

        $existing = $this->generatedInvoiceRepository->findOneForStripeInvoice($event->getInvoiceId());
        if ($existing instanceof GeneratedInvoice) {
            return $existing;
        }

        $stripeInvoice = $this->retrieveStripeInvoice($event->getInvoiceId());
        $invoice = new Invoice($stripeInvoice, $this->renderer);
        $pdfContents = $this->renderer->renderBinary($invoice);
        $storedInvoice = $this->storage->store($invoice, $pdfContents);
        $customer = $this->stripeCustomerRepository->findByStripeId($event->getCustomerId());
        $metadata = $this->normalizeMetadata($stripeInvoice);

        $generatedInvoice = (new GeneratedInvoice())
            ->setCustomer($customer)
            ->setStripeInvoiceId($invoice->id())
            ->setStripePaymentIntentId($event->getPaymentIntentId() ?? $invoice->paymentIntentId())
            ->setStripeCheckoutSessionId($event->getCheckoutSessionId())
            ->setResourceType($this->resolveResourceType($metadata))
            ->setResourceId($this->resolveResourceId($metadata))
            ->setPlanCode($this->resolvePlanCode($metadata))
            ->setCurrency($event->getCurrency())
            ->setAmountTotal($event->getAmount())
            ->setStatus($invoice->status())
            ->setFilename($storedInvoice->filename())
            ->setRelativePath($storedInvoice->relativePath())
            ->setMimeType($storedInvoice->mimeType())
            ->setSize($storedInvoice->size())
            ->setChecksum($storedInvoice->checksum())
            ->setPayload([
                'stripe_invoice_id' => $invoice->id(),
                'stripe_checkout_session_id' => $event->getCheckoutSessionId(),
                'stripe_payment_intent_id' => $event->getPaymentIntentId() ?? $invoice->paymentIntentId(),
                'hosted_invoice_url' => $stripeInvoice->hosted_invoice_url ?? null,
                'metadata' => $metadata,
            ])
        ;

        $this->entityManager->persist($generatedInvoice);
        $this->entityManager->flush();

        return $generatedInvoice;
    }

    /**
     * @throws ApiErrorException
     */
    private function retrieveStripeInvoice(string $invoiceId): \Stripe\Invoice
    {
        return $this->stripe->invoices->retrieve($invoiceId, [
            'expand' => ['customer', 'payment_intent'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeMetadata(\Stripe\Invoice $invoice): array
    {
        if (!isset($invoice->metadata) || !method_exists($invoice->metadata, 'toArray')) {
            return [];
        }

        $metadata = $invoice->metadata->toArray();

        return is_array($metadata) ? $metadata : [];
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function resolveResourceType(array $metadata): ?string
    {
        $value = $metadata['app_resource_type'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function resolveResourceId(array $metadata): ?string
    {
        $value = $metadata['app_resource_id'] ?? $metadata['app_order_id'] ?? null;

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function resolvePlanCode(array $metadata): ?string
    {
        $value = $metadata['plan_code'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
