<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service\InvoiceStorage;

use CashierBundle\Contract\InvoiceRendererInterface;
use CashierBundle\Model\Invoice;
use CashierBundle\Service\InvoiceStorage\LocalInvoiceStorage;
use PHPUnit\Framework\TestCase;
use Stripe\Invoice as StripeInvoice;
use Symfony\Component\HttpFoundation\Response;

final class LocalInvoiceStorageTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/cashier_invoice_storage_' . bin2hex(random_bytes(8)) . '/var/data/invoices';
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    public function testStoreCreatesTargetDirectoryAndPersistsPdf(): void
    {
        $storage = new LocalInvoiceStorage($this->storagePath);
        $invoice = $this->createInvoice('in_test_123', 'INV-2026-001');

        $storedInvoice = $storage->store($invoice, '%PDF-1.7 test');

        self::assertFileExists($storedInvoice->absolutePath());
        self::assertSame('INV-2026-001.pdf', $storedInvoice->filename());
        self::assertSame('var/data/invoices/INV-2026-001.pdf', $storedInvoice->relativePath());
        self::assertSame('%PDF-1.7 test', (string) file_get_contents($storedInvoice->absolutePath()));
    }

    private function createInvoice(string $id, ?string $number = null): Invoice
    {
        $stripeInvoice = new StripeInvoice($id);
        $stripeInvoice->number = $number;
        $stripeInvoice->status = 'paid';
        $stripeInvoice->currency = 'eur';
        $stripeInvoice->total = 1999;
        $stripeInvoice->created = time();

        return new Invoice($stripeInvoice, new class () implements InvoiceRendererInterface {
            public function render(Invoice $invoice, array $data = []): Response
            {
                return new Response('pdf');
            }

            public function renderBinary(Invoice $invoice, array $data = []): string
            {
                return 'pdf';
            }

            public function stream(Invoice $invoice, array $data = []): Response
            {
                return new Response('pdf');
            }
        });
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $path . '/' . $entry;
            if (is_dir($entryPath)) {
                $this->removeDirectory($entryPath);
                continue;
            }

            unlink($entryPath);
        }

        rmdir($path);
    }
}
