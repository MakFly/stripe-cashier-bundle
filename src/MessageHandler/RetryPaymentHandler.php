<?php

declare(strict_types=1);

namespace CashierBundle\MessageHandler;

use CashierBundle\Message\RetryPaymentMessage;
use CashierBundle\Service\PaymentService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RetryPaymentHandler
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function __invoke(RetryPaymentMessage $message): void
    {
        $this->paymentService->retryInvoice($message->invoiceId);
    }
}
