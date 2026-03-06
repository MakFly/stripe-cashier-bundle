<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service\Invoice;

use CashierBundle\Service\Invoice\DefaultInvoiceTranslationProvider;
use PHPUnit\Framework\TestCase;

/** Test suite for DefaultInvoiceTranslationProvider. */
final class DefaultInvoiceTranslationProviderTest extends TestCase
{
    public function testFrenchTranslationsAreReturned(): void
    {
        $provider = new DefaultInvoiceTranslationProvider();

        $translations = $provider->getTranslations('fr');

        self::assertSame('Facture', $translations['title']);
        self::assertSame('Payée', $translations['statuses']['paid']);
    }

    public function testUnknownLocaleFallsBackToEnglish(): void
    {
        $provider = new DefaultInvoiceTranslationProvider();

        $translations = $provider->getTranslations('de');

        self::assertSame('Invoice', $translations['title']);
    }
}
