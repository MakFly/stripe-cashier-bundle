<?php

declare(strict_types=1);

namespace CashierBundle;

use CashierBundle\Model\Cashier as CashierRuntime;
use CashierBundle\Service\Cashier as CashierService;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CashierBundle extends Bundle
{
    public function boot(): void
    {
        parent::boot();

        if ($this->container === null) {
            return;
        }

        $locator = $this->container->get('cashier.billable_service_locator');

        CashierRuntime::useCurrency((string) $this->container->getParameter('cashier.currency'));
        CashierRuntime::useLocale((string) $this->container->getParameter('cashier.currency_locale'));
        CashierRuntime::resolveServicesUsing(static fn (string $service): object => $locator->get($service));

        CashierService::$currency = (string) $this->container->getParameter('cashier.currency');
        CashierService::$currencyLocale = (string) $this->container->getParameter('cashier.currency_locale');
    }

    public function shutdown(): void
    {
        CashierRuntime::clearServiceResolver();

        parent::shutdown();
    }
}
