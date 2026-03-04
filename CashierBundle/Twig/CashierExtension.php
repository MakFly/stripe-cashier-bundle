<?php

declare(strict_types=1);

namespace CashierBundle\Twig;

use CashierBundle\Model\Cashier;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for Cashier-specific formatting functions.
 */
final class CashierExtension extends AbstractExtension
{
    /**
     * @return array<int, TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('stripe_amount', [$this, 'formatAmount'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Format a Stripe amount (in cents) to a human-readable currency format.
     *
     * @param int $amount The amount in cents (e.g., 1000 for $10.00)
     * @param string $currency The currency code (e.g., 'usd', 'eur')
     * @return string The formatted amount (e.g., '$10.00', '10,00 €')
     */
    public function formatAmount(int $amount, string $currency = 'usd'): string
    {
        return Cashier::formatAmount($amount, $currency);
    }
}
