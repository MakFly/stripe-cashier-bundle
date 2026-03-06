<?php

declare(strict_types=1);

namespace App\Billing;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubscriptionCatalog
{
    public function __construct(
        #[Autowire('%env(default::STRIPE_PRICE_STARTER_MONTHLY)%')]
        private string $starterMonthlyPriceId,
        #[Autowire('%env(default::STRIPE_PRICE_STARTER_YEARLY)%')]
        private string $starterYearlyPriceId,
        #[Autowire('%env(default::STRIPE_PRICE_PRO_MONTHLY)%')]
        private string $proMonthlyPriceId,
        #[Autowire('%env(default::STRIPE_PRICE_PRO_YEARLY)%')]
        private string $proYearlyPriceId,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function publicPlans(): array
    {
        return array_map(
            fn (array $plan): array => [
                'code' => $plan['code'],
                'name' => $plan['name'],
                'description' => $plan['description'],
                'trialDays' => $plan['trialDays'],
                'monthly' => $plan['monthly'],
                'yearly' => $plan['yearly'],
                'yearlyDiscountPercent' => $plan['yearlyDiscountPercent'],
                'features' => $plan['features'],
            ],
            array_values($this->all()),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return [
            'starter' => [
                'code' => 'starter',
                'name' => 'Starter',
                'description' => '14 jours d’essai puis passage au plan standard.',
                'trialDays' => 14,
                'yearlyDiscountPercent' => 20,
                'features' => [
                    '14 jours gratuits',
                    'Gestion des commandes et factures',
                    'Support standard',
                ],
                'monthly' => [
                    'amount' => 999,
                    'currency' => 'eur',
                    'priceId' => $this->starterMonthlyPriceId,
                    'label' => '9,99 € / mois',
                ],
                'yearly' => [
                    'amount' => 9590,
                    'currency' => 'eur',
                    'priceId' => $this->starterYearlyPriceId,
                    'monthlyEquivalent' => 799,
                    'label' => '95,90 € / an',
                ],
            ],
            'pro' => [
                'code' => 'pro',
                'name' => 'Pro',
                'description' => 'Plan complet pour un usage récurrent sans période d’essai.',
                'trialDays' => 0,
                'yearlyDiscountPercent' => 20,
                'features' => [
                    'Accès immédiat au plan complet',
                    'Support prioritaire',
                    'Facturation avancée',
                ],
                'monthly' => [
                    'amount' => 1999,
                    'currency' => 'eur',
                    'priceId' => $this->proMonthlyPriceId,
                    'label' => '19,99 € / mois',
                ],
                'yearly' => [
                    'amount' => 19190,
                    'currency' => 'eur',
                    'priceId' => $this->proYearlyPriceId,
                    'monthlyEquivalent' => 1599,
                    'label' => '191,90 € / an',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $planCode): ?array
    {
        return $this->all()[$planCode] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findCheckoutPlan(string $planCode, string $billingCycle): ?array
    {
        $plan = $this->find($planCode);
        if ($plan === null) {
            return null;
        }

        if (!isset($plan[$billingCycle]) || !is_array($plan[$billingCycle])) {
            return null;
        }

        return array_merge($plan, [
            'billingCycle' => $billingCycle,
            'checkout' => $plan[$billingCycle],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function matchStripePrice(?string $stripePrice): ?array
    {
        if ($stripePrice === null || $stripePrice === '') {
            return null;
        }

        foreach ($this->all() as $plan) {
            foreach (['monthly', 'yearly'] as $billingCycle) {
                if (($plan[$billingCycle]['priceId'] ?? null) === $stripePrice) {
                    return array_merge($plan, [
                        'billingCycle' => $billingCycle,
                        'checkout' => $plan[$billingCycle],
                    ]);
                }
            }
        }

        return null;
    }

    public function isConfigured(string $planCode, string $billingCycle): bool
    {
        $plan = $this->findCheckoutPlan($planCode, $billingCycle);

        return is_array($plan) && is_string($plan['checkout']['priceId'] ?? null) && $plan['checkout']['priceId'] !== '';
    }
}
