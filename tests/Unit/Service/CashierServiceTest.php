<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service;

use CashierBundle\Model\Cashier;
use PHPUnit\Framework\TestCase;

/** Test suite for Cashier model (currency, locale, formatting). */
final class CashierServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset static properties before each test
        Cashier::useCurrency('usd');
        Cashier::useLocale('en');
    }

    public function testFormatAmountFormatsCorrectlyForUSD(): void
    {
        $formatted = Cashier::formatAmount(1000, 'usd', 'en');
        $this->assertStringContainsString('10', $formatted);
    }

    public function testFormatAmountFormatsCorrectlyForEUR(): void
    {
        $formatted = Cashier::formatAmount(2500, 'eur', 'fr');
        $this->assertStringContainsString('25', $formatted);
    }

    public function testFormatAmountWithZero(): void
    {
        $formatted = Cashier::formatAmount(0, 'usd', 'en');
        $this->assertStringContainsString('0', $formatted);
    }

    public function testFormatAmountWithLargeAmount(): void
    {
        $formatted = Cashier::formatAmount(1000000, 'usd', 'en');
        $this->assertStringContainsString('10', $formatted);
        $this->assertStringContainsString('000', $formatted);
    }

    public function testCurrencyCanBeChanged(): void
    {
        Cashier::useCurrency('eur');
        $this->assertEquals('eur', Cashier::getCurrency());
    }

    public function testLocaleCanBeChanged(): void
    {
        Cashier::useLocale('fr');
        $this->assertEquals('fr', Cashier::getLocale());
    }
}
