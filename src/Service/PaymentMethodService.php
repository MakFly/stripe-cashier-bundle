<?php

declare(strict_types=1);

namespace CashierBundle\Service;

use CashierBundle\Contract\BillableInterface;
use CashierBundle\Exception\InvalidPaymentMethodException;
use CashierBundle\Model\PaymentMethod;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\StripeClient;

/**
 * Attaches, detaches, and lists Stripe payment methods for billable entities.
 *
 * @implements \CashierBundle\Concerns\ManagesPaymentMethods<BillableInterface>
 */
class PaymentMethodService
{
    public function __construct(
        private readonly StripeClient $stripe,
    ) {
    }

    public function add(BillableInterface $billable, string $paymentMethod): PaymentMethod
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            throw new \RuntimeException('Billable must have a Stripe customer ID.');
        }

        try {
            $stripePaymentMethod = $this->stripe->paymentMethods->retrieve($paymentMethod);
            $this->stripe->paymentMethods->attach($paymentMethod, ['customer' => $stripeId]);

            return new PaymentMethod($stripePaymentMethod);
        } catch (ApiErrorException $e) {
            throw new InvalidPaymentMethodException(
                sprintf('Failed to add payment method: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    public function updateDefault(BillableInterface $billable, string $paymentMethod): PaymentMethod
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            throw new \RuntimeException('Billable must have a Stripe customer ID.');
        }

        try {
            $this->stripe->customers->update($stripeId, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethod,
                ],
            ]);

            $stripePaymentMethod = $this->stripe->paymentMethods->retrieve($paymentMethod);

            return new PaymentMethod($stripePaymentMethod);
        } catch (ApiErrorException $e) {
            throw new InvalidPaymentMethodException(
                sprintf('Failed to update default payment method: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * @return Collection<int, PaymentMethod>
     */
    public function list(BillableInterface $billable, ?string $type = null): Collection
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            return new ArrayCollection();
        }

        try {
            $params = ['customer' => $stripeId];
            if ($type !== null) {
                $params['type'] = $type;
            }

            $paymentMethods = $this->stripe->paymentMethods->all($params);

            return new ArrayCollection(
                array_map(
                    fn (StripePaymentMethod $pm) => new PaymentMethod($pm),
                    $paymentMethods->data,
                ),
            );
        } catch (ApiErrorException $e) {
            return new ArrayCollection();
        }
    }

    public function default(BillableInterface $billable): ?PaymentMethod
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            return null;
        }

        try {
            $customer = $this->stripe->customers->retrieve($stripeId, ['expand' => ['invoice_settings.default_payment_method']]);

            $defaultPaymentMethod = $customer->invoice_settings->default_payment_method ?? null;
            if ($defaultPaymentMethod === null) {
                return null;
            }

            if (is_string($defaultPaymentMethod)) {
                $defaultPaymentMethod = $this->stripe->paymentMethods->retrieve($defaultPaymentMethod);
            }

            return new PaymentMethod($defaultPaymentMethod);
        } catch (ApiErrorException $e) {
            return null;
        }
    }

    public function hasDefault(BillableInterface $billable): bool
    {
        return $this->default($billable) !== null;
    }

    /**
     * @return Collection<int, PaymentMethod>
     */
    public function all(BillableInterface $billable, ?string $type = null): Collection
    {
        return $this->list($billable, $type);
    }

    public function getDefault(BillableInterface $billable): ?PaymentMethod
    {
        return $this->default($billable);
    }

    public function remove(BillableInterface $billable, string $paymentMethod): void
    {
        $stripeId = $billable->stripeId();
        if ($stripeId === null) {
            throw new \RuntimeException('Billable must have a Stripe customer ID.');
        }

        try {
            $this->stripe->paymentMethods->detach($paymentMethod);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(
                sprintf('Failed to remove payment method: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }
}
