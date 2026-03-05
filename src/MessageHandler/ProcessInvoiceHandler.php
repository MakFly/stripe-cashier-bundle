<?php

declare(strict_types=1);

namespace CashierBundle\MessageHandler;

use CashierBundle\Message\ProcessInvoiceMessage;
use CashierBundle\Service\InvoiceService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProcessInvoiceHandler
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {
    }

    public function __invoke(ProcessInvoiceMessage $message): void
    {
        $invoice = $this->invoiceService->find($message->invoiceId);

        if ($invoice && $message->autoPay) {
            $this->invoiceService->pay($invoice);
        }
    }
}
