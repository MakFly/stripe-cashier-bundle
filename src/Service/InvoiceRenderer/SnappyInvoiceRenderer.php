<?php

declare(strict_types=1);

namespace CashierBundle\Service\InvoiceRenderer;

use CashierBundle\Contract\InvoiceRendererInterface;
use CashierBundle\Model\Invoice;
use CashierBundle\Service\InvoiceViewFactory;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class SnappyInvoiceRenderer implements InvoiceRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly InvoiceViewFactory $invoiceViewFactory,
        private readonly Pdf $snappy,
        private readonly string $paper = 'letter',
        private readonly string $orientation = 'portrait',
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

        return $this->snappy->getOutputFromHtml($html, [
            'page-size' => $this->paper,
            'orientation' => $this->orientation,
            'margin-top' => '10mm',
            'margin-right' => '10mm',
            'margin-bottom' => '10mm',
            'margin-left' => '10mm',
            'encoding' => 'UTF-8',
            'enable-local-file-access' => true,
        ]);

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
