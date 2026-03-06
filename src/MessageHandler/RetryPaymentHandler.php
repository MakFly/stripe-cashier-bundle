<?php

declare(strict_types=1);

namespace CashierBundle\MessageHandler;

use CashierBundle\Message\RetryPaymentMessage;
use CashierBundle\Service\PaymentService;

/** Handles RetryPaymentMessage by delegating invoice payment retry to PaymentService. */
class RetryPaymentHandler
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {
    }

    public function __invoke(RetryPaymentMessage $message): void
    {
        $this->paymentService->retryInvoice($message->invoiceId);
    }
}
