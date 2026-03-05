<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Contract\BillableInterface;
use CashierBundle\Entity\Subscription;
use CashierBundle\Model\Invoice;
use CashierBundle\Repository\StripeCustomerRepository;
use CashierBundle\Repository\SubscriptionRepository;
use Doctrine\Persistence\ManagerRegistry;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;
use Stripe\StripeClient;

class Cashier
{
    public const VERSION = '1.0.0';

    public static string $currency = 'usd';
    public static string $currencyLocale = 'en';
    public static ?string $logger = null;
    public static bool $deactivatePastDue = true;
    public static bool $deactivateIncomplete = true;

    public function __construct(
        private readonly StripeClient $stripe,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly StripeCustomerRepository $customerRepository,
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public static function formatAmount(int $amount, ?string $currency = null, ?string $locale = null): string
    {
        $currency = strtoupper($currency ?? self::$currency);
        $locale = $locale ?? self::$currencyLocale;

        if (!class_exists(NumberFormatter::class)) {
            return \CashierBundle\Model\Cashier::formatAmount($amount, strtolower($currency), $locale);
        }

        try {
            $money = new Money($amount, new Currency($currency));
            $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies());

            return $moneyFormatter->format($money);
        } catch (\Throwable) {
            return \CashierBundle\Model\Cashier::formatAmount($amount, strtolower($currency), $locale);
        }
    }

    public static function normalizeZeroAmountDecimal(int $amount): int
    {
        if ($amount < 10) {
            return $amount * 100;
        }

        return $amount;
    }

    public function findSubscription(string $stripeId): ?Subscription
    {
        return $this->subscriptionRepository->findByStripeId($stripeId);
    }

    public function findInvoice(string $stripeId): ?Invoice
    {
        try {
            $stripeInvoice = $this->stripe->invoices->retrieve($stripeId);

            return new Invoice($stripeInvoice, null);
        } catch (\Stripe\Exception\ExceptionInterface $e) {
            return null;
        }
    }

    public function findCustomer(string $stripeId): ?BillableInterface
    {
        $customer = $this->customerRepository->findByStripeId($stripeId);

        if ($customer === null) {
            return null;
        }

        $billable = $customer->getBillable();
        if ($billable instanceof BillableInterface) {
            return $billable;
        }

        $billableType = $customer->getBillableType();
        $billableId = $customer->getBillableId();
        if ($billableType === null || $billableId === null) {
            return null;
        }

        $objectManager = $this->managerRegistry->getManagerForClass($billableType);
        if ($objectManager === null) {
            return null;
        }

        /** @phpstan-ignore-next-line runtime class name comes from persisted metadata */
        $billable = $objectManager->find($billableType, $billableId);
        if ($billable instanceof BillableInterface) {
            return $billable;
        }

        return null;
    }

    public function stripe(): StripeClient
    {
        return $this->stripe;
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function paymentIntentOptions(array $options = []): array
    {
        return array_merge([
            'confirmation_method' => \Stripe\PaymentIntent::CONFIRMATION_METHOD_AUTOMATIC,
            'confirm' => true,
        ], $options);
    }
}
