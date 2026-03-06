<?php

declare(strict_types=1);

namespace CashierBundle\EventSubscriber;

use CashierBundle\Event\PaymentSucceededEvent;
use CashierBundle\Service\InvoiceArchiveService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Archives a paid invoice when a PaymentSucceededEvent is dispatched.
 */
final readonly class GenerateInvoiceOnPaymentSucceededSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private InvoiceArchiveService $invoiceArchiveService,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PaymentSucceededEvent::class => 'onPaymentSucceeded',
        ];
    }

    public function onPaymentSucceeded(PaymentSucceededEvent $event): void
    {
        try {
            $this->invoiceArchiveService->archiveFromPaymentSuccess($event);
        } catch (\Throwable $exception) {
            $this->logger?->error('Failed to archive paid invoice.', [
                'exception' => $exception,
                'customer_id' => $event->getCustomerId(),
                'invoice_id' => $event->getInvoiceId(),
                'payment_intent_id' => $event->getPaymentIntentId(),
            ]);
        }
    }
}
