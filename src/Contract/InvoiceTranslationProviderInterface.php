<?php

declare(strict_types=1);

namespace CashierBundle\Contract;

interface InvoiceTranslationProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getTranslations(string $locale): array;
}
