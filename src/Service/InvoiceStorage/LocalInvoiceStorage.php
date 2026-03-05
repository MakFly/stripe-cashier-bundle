<?php

declare(strict_types=1);

namespace CashierBundle\Service\InvoiceStorage;

use CashierBundle\Contract\InvoiceStorageInterface;
use CashierBundle\Model\Invoice;
use CashierBundle\ValueObject\StoredInvoice;
use Symfony\Component\Filesystem\Filesystem;

final class LocalInvoiceStorage implements InvoiceStorageInterface
{
    public function __construct(
        private readonly string $storagePath,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function store(Invoice $invoice, string $contents, array $context = []): StoredInvoice
    {
        $directory = rtrim($this->storagePath, '/');
        $this->filesystem->mkdir($directory);

        $filename = $this->resolveFilename($invoice);
        $absolutePath = $directory . '/' . $filename;

        $this->filesystem->dumpFile($absolutePath, $contents);

        return new StoredInvoice(
            $absolutePath,
            $this->resolveRelativePath($filename),
            $filename,
            'application/pdf',
            strlen($contents),
            hash('sha256', $contents),
        );
    }

    private function resolveFilename(Invoice $invoice): string
    {
        $identifier = $invoice->number() ?? $invoice->id();
        $identifier = preg_replace('/[^A-Za-z0-9._-]+/', '-', $identifier) ?? $invoice->id();
        $identifier = trim($identifier, '-');

        if ($identifier === '') {
            $identifier = $invoice->id();
        }

        return sprintf('%s.pdf', $identifier);
    }

    private function resolveRelativePath(string $filename): string
    {
        $normalized = str_replace('\\', '/', rtrim($this->storagePath, '/'));
        $position = strpos($normalized, '/var/');
        if ($position !== false) {
            return ltrim(substr($normalized, $position + 1) . '/' . $filename, '/');
        }

        return $filename;
    }
}
