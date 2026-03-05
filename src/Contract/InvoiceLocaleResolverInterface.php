<?php

declare(strict_types=1);

namespace CashierBundle\Contract;

use Stripe\Customer as StripeCustomer;

interface InvoiceLocaleResolverInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function resolve(?StripeCustomer $customer = null, array $data = []): string;
}
