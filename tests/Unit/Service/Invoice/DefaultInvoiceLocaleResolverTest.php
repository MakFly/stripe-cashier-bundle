<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service\Invoice;

use CashierBundle\Service\Invoice\DefaultInvoiceLocaleResolver;
use PHPUnit\Framework\TestCase;
use Stripe\Customer;

final class DefaultInvoiceLocaleResolverTest extends TestCase
{
    public function testResolvePrefersExplicitLocaleAndNormalizesIt(): void
    {
        $resolver = new DefaultInvoiceLocaleResolver('en', ['en', 'fr']);

        self::assertSame('fr', $resolver->resolve(null, ['locale' => 'fr-FR']));
    }

    public function testResolveFallsBackToCustomerPreferredLocales(): void
    {
        $resolver = new DefaultInvoiceLocaleResolver('en', ['en', 'fr']);
        $customer = new Customer('cus_test');
        $customer->preferred_locales = ['fr-FR'];

        self::assertSame('fr', $resolver->resolve($customer));
    }

    public function testResolveFallsBackToConfiguredDefaultLocale(): void
    {
        $resolver = new DefaultInvoiceLocaleResolver('en', ['en', 'fr']);

        self::assertSame('en', $resolver->resolve(null, ['locale' => 'de-DE']));
    }
}
