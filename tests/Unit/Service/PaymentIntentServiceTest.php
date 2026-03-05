<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service;

use CashierBundle\Exception\IncompletePaymentException;
use CashierBundle\Exception\InvalidPaymentMethodException;
use CashierBundle\Service\PaymentIntentService;
use PHPUnit\Framework\TestCase;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;

final class PaymentIntentServiceTest extends TestCase
{
    public function testCreateReturnsPaymentModel(): void
    {
        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->paymentIntents = new class () {
            /**
             * @param array<string, mixed> $payload
             */
            public function create(array $payload): object
            {
                return (object) [
                    'id' => 'pi_abc',
                    'amount' => $payload['amount'],
                    'currency' => $payload['currency'],
                    'client_secret' => 'cs_abc',
                    'status' => 'succeeded',
                ];
            }
        };

        $service = new PaymentIntentService($stripe);
        $payment = $service->create(1500, 'eur');

        self::assertSame('pi_abc', $payment->id());
        self::assertSame(1500, $payment->rawAmount());
    }

    public function testConfirmThrowsIncompletePaymentWhenActionRequired(): void
    {
        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->paymentIntents = new class () {
            /**
             * @param array<string, mixed> $payload
             */
            public function confirm(string $id, array $payload): object
            {
                return (object) [
                    'id' => $id,
                    'amount' => 1500,
                    'currency' => 'eur',
                    'client_secret' => 'cs_abc',
                    'status' => 'requires_action',
                ];
            }
        };

        $service = new PaymentIntentService($stripe);

        $this->expectException(IncompletePaymentException::class);

        $service->confirm('pi_abc');
    }

    public function testAuthorizeWrapsStripeError(): void
    {
        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->paymentIntents = new class () {
            /**
             * @param array<string, mixed> $payload
             */
            public function create(array $payload): object
            {
                throw new InvalidRequestException('bad auth');
            }
        };

        $service = new PaymentIntentService($stripe);

        $this->expectException(InvalidPaymentMethodException::class);
        $this->expectExceptionMessage('Failed to authorize payment');

        $service->authorize(2000, 'usd', 'pm_1');
    }
}
