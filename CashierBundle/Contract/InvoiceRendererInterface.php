<?php

declare(strict_types=1);

namespace CashierBundle\Contract;

use CashierBundle\Model\Invoice;
use Symfony\Component\HttpFoundation\Response;

interface InvoiceRendererInterface
{
    public function render(Invoice $invoice, array $data = []): Response;
}
