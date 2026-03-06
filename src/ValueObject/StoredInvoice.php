<?php

declare(strict_types=1);

namespace CashierBundle\ValueObject;

/** Immutable value object holding the file metadata of a successfully stored invoice PDF. */
final readonly class StoredInvoice
{
    public function __construct(
        private string $absolutePath,
        private string $relativePath,
        private string $filename,
        private string $mimeType,
        private int $size,
        private string $checksum,
    ) {
    }

    public function absolutePath(): string
    {
        return $this->absolutePath;
    }

    public function relativePath(): string
    {
        return $this->relativePath;
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function checksum(): string
    {
        return $this->checksum;
    }
}
