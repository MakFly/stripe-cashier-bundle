<?php

declare(strict_types=1);

namespace CashierBundle\Concerns;

trait BillableTrait
{
    use HandlesTaxes;
    use ManagesCustomer;
    use ManagesInvoices;
    use ManagesPaymentMethods;
    use ManagesSubscriptions;
    use PerformsCharges;
}
