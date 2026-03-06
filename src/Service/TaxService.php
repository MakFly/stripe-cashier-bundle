<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Contract\BillableInterface;
use CashierBundle\Model\TaxRate;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\TaxRate as StripeTaxRate;

/** Retrieves and manages Stripe tax rates and automatic tax settings. */
class TaxService
{
    private bool $automaticTaxEnabled = false;

    public function __construct(
        private readonly StripeClient $stripe,
    ) {
    }

    /**
     * @return array<TaxRate>
     */
    public function getTaxRates(BillableInterface $billable): array
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            return [];
        }

        try {
            $customer = $this->stripe->customers->retrieve($stripeId);

            if ($customer->tax_ids === null || empty($customer->tax_ids->data)) {
                return [];
            }

            $taxRates = [];
            foreach ($customer->tax_ids->data as $taxId) {
                $taxRates[] = $this->buildTaxRateFromTaxId($taxId);
            }

            return $taxRates;
        } catch (ApiErrorException $e) {
            return [];
        }
    }

    /**
     * @return array<TaxRate>
     */
    public function getPriceTaxRates(string $priceId): array
    {
        try {
            $price = $this->stripe->prices->retrieve($priceId);

            if ($price->tax_behavior === null || !in_array($price->tax_behavior, ['inclusive', 'exclusive'], true)) {
                return [];
            }

            $taxRates = [];
            if (!empty($price->taxes)) {
                foreach ($price->taxes as $taxId) {
                    $taxRate = $this->stripe->taxRates->retrieve($taxId);
                    $taxRates[] = $this->buildTaxRate($taxRate);
                }
            }

            return $taxRates;
        } catch (ApiErrorException $e) {
            return [];
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function calculate(array $payload): array
    {
        try {
            return $this->stripe->tax->calculations->create($payload)->toArray();
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to calculate tax: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    public function isAutomaticTaxEnabled(): bool
    {
        return $this->automaticTaxEnabled;
    }

    public function setAutomaticTaxEnabled(bool $enabled): void
    {
        $this->automaticTaxEnabled = $enabled;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createTaxRate(string $displayName, float $percentage, bool $inclusive, array $options = []): string
    {
        $payload = array_merge([
            'display_name' => $displayName,
            'percentage' => $percentage,
            'inclusive' => $inclusive,
        ], $options);

        try {
            $taxRate = $this->stripe->taxRates->create($payload);

            return $taxRate->id;
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to create tax rate: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    private function buildTaxRate(StripeTaxRate $taxRate): TaxRate
    {
        return new TaxRate(
            $taxRate->id,
            $taxRate->display_name,
            $taxRate->description ?? $taxRate->display_name,
            (float) $taxRate->percentage,
            $taxRate->inclusive,
            $taxRate->jurisdiction ?? null,
            $taxRate->state ?? null,
            $taxRate->country ?? null,
        );
    }

    /**
     * @param \Stripe\TaxId $taxId
     */
    private function buildTaxRateFromTaxId($taxId): TaxRate
    {
        return new TaxRate(
            $taxId->id,
            $taxId->value,
            $taxId->value,
            0.0,
            false,
            null,
            null,
            $taxId->country ?? null,
        );
    }

    /**
     * @param array<string> $taxRateIds
     */
    public function attachTaxRatesToPrice(string $priceId, array $taxRateIds): void
    {
        try {
            $this->stripe->prices->update($priceId, [
                'taxes' => $taxRateIds,
            ]);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to attach tax rates to price: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @return array<TaxRate>
     */
    public function listAllTaxRates(): array
    {
        try {
            $taxRates = $this->stripe->taxRates->all();

            return array_map(
                fn (StripeTaxRate $taxRate) => $this->buildTaxRate($taxRate),
                $taxRates->data,
            );
        } catch (ApiErrorException $e) {
            return [];
        }
    }

    /**
     * @return array<TaxRate>
     */
    public function getTaxRatesForEntity(BillableInterface $billable): array
    {
        return $this->getTaxRates($billable);
    }

    public function isTaxExempt(BillableInterface $billable): bool
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            return false;
        }

        try {
            $customer = $this->stripe->customers->retrieve($stripeId);

            return ($customer->tax_exempt ?? 'none') !== 'none';
        } catch (ApiErrorException) {
            return false;
        }
    }
}
