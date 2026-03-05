<?php

declare(strict_types=1);

namespace CashierBundle\Service\Invoice;

use CashierBundle\Contract\InvoiceLocaleResolverInterface;
use Stripe\Customer as StripeCustomer;

final readonly class DefaultInvoiceLocaleResolver implements InvoiceLocaleResolverInterface
{
    /**
     * @param list<string> $supportedLocales
     */
    public function __construct(
        private string $defaultLocale = 'en',
        private array $supportedLocales = ['en', 'fr'],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function resolve(?StripeCustomer $customer = null, array $data = []): string
    {
        $candidates = [];

        if (isset($data['locale']) && is_string($data['locale'])) {
            $candidates[] = $data['locale'];
        }

        if (isset($data['invoice_locale']) && is_string($data['invoice_locale'])) {
            $candidates[] = $data['invoice_locale'];
        }

        $preferredLocales = $customer?->preferred_locales ?? null;
        if (is_array($preferredLocales)) {
            foreach ($preferredLocales as $preferredLocale) {
                if (is_string($preferredLocale)) {
                    $candidates[] = $preferredLocale;
                }
            }
        }

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeLocale($candidate);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return $this->normalizeLocale($this->defaultLocale) ?? 'en';
    }

    private function normalizeLocale(string $locale): ?string
    {
        $normalized = strtolower(str_replace('_', '-', trim($locale)));
        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, $this->supportedLocales, true)) {
            return $normalized;
        }

        $language = strtok($normalized, '-') ?: $normalized;

        return in_array($language, $this->supportedLocales, true) ? $language : null;
    }
}
