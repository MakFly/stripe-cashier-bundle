<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Integration\DependencyInjection;

use PHPUnit\Framework\TestCase;

final class ServiceConfigurationTest extends TestCase
{
    public function testBillableServiceLocatorIsExplicitlyPublicInServiceConfiguration(): void
    {
        $contents = file_get_contents(__DIR__ . '/../../../src/Resources/config/services.yaml');

        self::assertIsString($contents);
        self::assertStringContainsString('cashier.billable_service_locator:', $contents);
        self::assertMatchesRegularExpression('/cashier\.billable_service_locator:\R(?:[ \t]+.*\R)*?[ \t]+public:\s*true/m', $contents);
    }
}
