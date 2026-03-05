<?php

declare(strict_types=1);

namespace CashierBundle\Contract;

use CashierBundle\Model\Invoice;
use Symfony\Component\HttpFoundation\Response;

interface InvoiceRendererInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function render(Invoice $invoice, array $data = []): Response;

    /**
     * @param array<string, mixed> $data
     */
    public function renderBinary(Invoice $invoice, array $data = []): string;

    /**
     * @param array<string, mixed> $data
     */
    public function stream(Invoice $invoice, array $data = []): Response;
}
