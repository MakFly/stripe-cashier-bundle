<?php

declare(strict_types=1);

namespace CashierBundle\Model;

/** Static configuration and utility helper for currency formatting and service resolution. */
final class Cashier
{
    private static string $currency = 'usd';

    private static string $locale = 'en';

    /**
     * @var null|\Closure(string): object
     */
    private static ?\Closure $serviceResolver = null;

    /**
     * @param int $amount Amount in cents
     */
    public static function formatAmount(int $amount, ?string $currency = null, ?string $locale = null): string
    {
        $currency = $currency ?? self::$currency;
        $locale = $locale ?? self::$locale;

        // Convert amount from cents to decimal
        $decimal = $amount / 100;

        // Format based on currency
        return self::formatCurrency($decimal, $currency, $locale);
    }

    public static function useCurrency(string $currency): void
    {
        self::$currency = strtolower($currency);
    }

    public static function useLocale(string $locale): void
    {
        self::$locale = $locale;
    }

    public static function getCurrency(): string
    {
        return self::$currency;
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    /**
     * @param callable(string): object $resolver
     */
    public static function resolveServicesUsing(callable $resolver): void
    {
        self::$serviceResolver = $resolver(...);
    }

    public static function clearServiceResolver(): void
    {
        self::$serviceResolver = null;
    }

    public static function service(string $service): object
    {
        if (self::$serviceResolver === null) {
            throw new \LogicException('Cashier service resolver is not configured. Boot the bundle before using BillableTrait methods.');
        }

        return (self::$serviceResolver)($service);
    }

    private static function formatCurrency(float $amount, string $currency, string $locale): string
    {
        $symbol = self::getCurrencySymbol($currency);
        $position = self::getCurrencyPosition($currency, $locale);
        $decimals = self::getCurrencyDecimals($currency);
        [$decimalSeparator, $thousandsSeparator] = self::getNumberSeparators($locale);

        $formatted = number_format($amount, $decimals, $decimalSeparator, $thousandsSeparator);

        if ($position === 'before') {
            return $symbol . $formatted;
        }

        return $formatted . ' ' . $symbol;
    }

    private static function getCurrencySymbol(string $currency): string
    {
        return match (strtolower($currency)) {
            'usd' => '$',
            'eur' => '€',
            'gbp' => '£',
            'cad' => 'C$',
            'aud' => 'A$',
            'chf' => 'CHF',
            'sek' => 'kr',
            'nok' => 'kr',
            'dkk' => 'kr',
            'pln' => 'zł',
            'czk' => 'Kč',
            'huf' => 'Ft',
            'ron' => 'lei',
            'bgn' => 'лв',
            'hrk' => 'kn',
            'rub' => '₽',
            'try' => '₺',
            'cny' => '¥',
            'jpy' => '¥',
            'inr' => '₹',
            'mxn' => '$',
            'brl' => 'R$',
            default => strtoupper($currency),
        };
    }

    private static function getCurrencyPosition(string $currency, string $locale): string
    {
        $language = strtolower(strtok(str_replace('_', '-', $locale), '-') ?: $locale);
        if (in_array($language, ['fr', 'de', 'es', 'it', 'nl', 'pt'], true)) {
            return 'after';
        }

        $currenciesBefore = ['usd', 'eur', 'gbp', 'cad', 'aud', 'mxn', 'brl', 'cny', 'jpy', 'inr'];

        return in_array(strtolower($currency), $currenciesBefore, true) ? 'before' : 'after';
    }

    /**
     * @return array{0:string,1:string}
     */
    private static function getNumberSeparators(string $locale): array
    {
        $language = strtolower(strtok(str_replace('_', '-', $locale), '-') ?: $locale);

        return match ($language) {
            'fr', 'de', 'es', 'it', 'nl', 'pt' => [',', ' '],
            default => ['.', ','],
        };
    }

    private static function getCurrencyDecimals(string $currency): int
    {
        // Zero decimal currencies
        $zeroDecimal = ['jpy', 'clp', 'kid', 'krw', 'pyg', 'vnd', 'bif', 'djf', 'gnf', 'kmf', 'mga', 'rwf'];

        return in_array(strtolower($currency), $zeroDecimal, true) ? 0 : 2;
    }
}
