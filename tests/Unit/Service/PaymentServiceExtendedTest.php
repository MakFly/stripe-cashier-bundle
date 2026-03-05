<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service;

use CashierBundle\Exception\IncompletePaymentException;
use CashierBundle\Exception\InvalidPaymentMethodException;
use CashierBundle\Service\PaymentService;
use CashierBundle\Tests\Support\FakeBillable;
use PHPUnit\Framework\TestCase;
use Stripe\Exception\InvalidRequestException;
use Stripe\Refund;
use Stripe\StripeClient;

final class PaymentServiceExtendedTest extends TestCase
{
    public function testChargeThrowsWhenBillableHasNoStripeId(): void
    {
        $service = new PaymentService(new StripeClient('sk_test'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Billable must have a Stripe customer ID.');

        $service->charge(new FakeBillable(null), 1200, 'pm_123');
    }

    public function testChargeThrowsIncompletePaymentWhenRequiresAction(): void
    {
        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->paymentIntents = new class () {
            /**
             * @param array<string, mixed> $options
             */
            public function create(array $options): object
            {
                return (object) [
                    'id' => 'pi_123',
                    'amount' => $options['amount'],
                    'currency' => $options['currency'],
                    'client_secret' => 'cs_test',
                    'status' => 'requires_action',
                ];
            }
        };

        $service = new PaymentService($stripe);

        $this->expectException(IncompletePaymentException::class);

        $service->charge(new FakeBillable('cus_123'), 1200, 'pm_123');
    }

    public function testPayThrowsWhenNoDefaultPaymentMethod(): void
    {
        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->customers = new class () {
            public function retrieve(string $id): object
            {
                return (object) [
                    'invoice_settings' => (object) [
                        'default_payment_method' => null,
                    ],
                ];
            }
        };

        $service = new PaymentService($stripe);

        $this->expectException(InvalidPaymentMethodException::class);
        $this->expectExceptionMessage('No default payment method found.');

        $service->pay(new FakeBillable('cus_123'), 500);
    }

    public function testRefundReturnsStripeRefund(): void
    {
        $refund = new Refund('re_123');

        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->refunds = new class ($refund) {
            public function __construct(private Refund $refund)
            {
            }

            /**
             * @param array<string, mixed> $payload
             */
            public function create(array $payload): Refund
            {
                return $this->refund;
            }
        };

        $service = new PaymentService($stripe);

        self::assertSame($refund, $service->refund('pi_123'));
    }

    public function testRefundWrapsStripeError(): void
    {
        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->refunds = new class () {
            /**
             * @param array<string, mixed> $payload
             */
            public function create(array $payload): Refund
            {
                throw new InvalidRequestException('bad refund');
            }
        };

        $service = new PaymentService($stripe);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to refund payment intent pi_123');

        $service->refund('pi_123');
    }
}
