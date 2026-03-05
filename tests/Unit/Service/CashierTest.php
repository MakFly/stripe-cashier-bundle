<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service;

use CashierBundle\Service\Cashier;
use PHPUnit\Framework\TestCase;

final class CashierTest extends TestCase
{
    public function testFormatAmountKeepsWorkingWithUnexpectedLocaleInput(): void
    {
        $formatted = Cashier::formatAmount(4990, 'eur', "\xFF");

        self::assertNotSame('', $formatted);
        self::assertIsString($formatted);
    }
}
