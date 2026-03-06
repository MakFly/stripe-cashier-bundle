<?php

declare(strict_types=1);

namespace CashierBundle\Service\InvoiceRenderer;

use CashierBundle\Contract\InvoiceRendererInterface;
use CashierBundle\Model\Invoice;
use CashierBundle\Service\InvoiceViewFactory;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/** Renders invoice PDFs using the Dompdf HTML-to-PDF library. */
class DompdfInvoiceRenderer implements InvoiceRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly InvoiceViewFactory $invoiceViewFactory,
        private readonly string $paper = 'letter',
        private readonly bool $remoteEnabled = false,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(Invoice $invoice, array $data = []): Response
    {
        return $this->createResponse($invoice, $this->renderBinary($invoice, $data), 'attachment');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function renderBinary(Invoice $invoice, array $data = []): string
    {
        $html = $this->twig->render(
            '@Cashier/invoice/default.html.twig',
            array_merge($this->invoiceViewFactory->create($invoice, $data), $data),
        );

        $options = new Options([
            'isRemoteEnabled' => $this->remoteEnabled,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper($this->paper);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function stream(Invoice $invoice, array $data = []): Response
    {
        return $this->createResponse($invoice, $this->renderBinary($invoice, $data), 'inline');
    }

    private function createResponse(Invoice $invoice, string $pdfContent, string $disposition): Response
    {
        $filename = sprintf('invoice-%s.pdf', $invoice->number() ?? $invoice->id());

        return new Response(
            $pdfContent,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $filename),
            ],
        );
    }
}
