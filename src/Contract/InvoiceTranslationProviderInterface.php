<?php

declare(strict_types=1);

namespace CashierBundle\Contract;

/**
 * Provides translation strings for invoice rendering by locale.
 */
interface InvoiceTranslationProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getTranslations(string $locale): array;
}
