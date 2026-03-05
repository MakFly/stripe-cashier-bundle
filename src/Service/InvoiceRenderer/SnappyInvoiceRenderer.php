<?php

declare(strict_types=1);

namespace CashierBundle\Service\InvoiceRenderer;

use CashierBundle\Contract\InvoiceRendererInterface;
use CashierBundle\Model\Invoice;
use Knp\Snappy\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class SnappyInvoiceRenderer implements InvoiceRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
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
        $html = $this->twig->render('@Cashier/invoice/default.html.twig', array_merge([
            'invoice' => $invoice,
        ], $data));

        $pdfContent = $this->snappy->getOutputFromHtml($html, [
            'page-size' => $this->paper,
            'orientation' => $this->orientation,
            'margin-top' => '10mm',
            'margin-right' => '10mm',
            'margin-bottom' => '10mm',
            'margin-left' => '10mm',
            'encoding' => 'UTF-8',
            'enable-local-file-access' => true,
        ]);

        $filename = sprintf('invoice-%s.pdf', $invoice->id());

        return new Response(
            $pdfContent,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ],
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function stream(Invoice $invoice, array $data = []): Response
    {
        $html = $this->twig->render('@Cashier/invoice/default.html.twig', array_merge([
            'invoice' => $invoice,
        ], $data));

        $pdfContent = $this->snappy->getOutputFromHtml($html, [
            'page-size' => $this->paper,
            'orientation' => $this->orientation,
            'margin-top' => '10mm',
            'margin-right' => '10mm',
            'margin-bottom' => '10mm',
            'margin-left' => '10mm',
            'encoding' => 'UTF-8',
            'enable-local-file-access' => true,
        ]);

        $filename = sprintf('invoice-%s.pdf', $invoice->id());

        return new Response(
            $pdfContent,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
            ],
        );
    }
}
