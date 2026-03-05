<?php

declare(strict_types=1);

namespace CashierBundle\Concerns;

use CashierBundle\Model\Cashier as CashierRuntime;

trait BillableTrait
{
    use HandlesTaxes;
    use ManagesCustomer;
    use ManagesInvoices;
    use ManagesPaymentMethods;
    use ManagesSubscriptions;
    use PerformsCharges;

    protected function getCashierService(string $service): object
    {
        return CashierRuntime::service($service);
    }
}
