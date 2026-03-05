<?php

declare(strict_types=1);

namespace CashierBundle\Service\InvoiceRenderer;

use CashierBundle\Contract\InvoiceRendererInterface;
use CashierBundle\Model\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class DompdfInvoiceRenderer implements InvoiceRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $paper = 'letter',
        private readonly bool $remoteEnabled = false,
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

        $options = new Options([
            'isRemoteEnabled' => $this->remoteEnabled,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper($this->paper);
        $dompdf->loadHtml($html);
        $dompdf->render();

        $filename = sprintf('invoice-%s.pdf', $invoice->id());

        return new Response(
            $dompdf->output(),
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

        $options = new Options([
            'isRemoteEnabled' => $this->remoteEnabled,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper($this->paper);
        $dompdf->loadHtml($html);
        $dompdf->render();

        $filename = sprintf('invoice-%s.pdf', $invoice->id());

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
            ],
        );
    }
}
