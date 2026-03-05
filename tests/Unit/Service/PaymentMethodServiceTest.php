<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Service;

use CashierBundle\Service\PaymentMethodService;
use CashierBundle\Tests\Support\FakeBillable;
use PHPUnit\Framework\TestCase;
use Stripe\PaymentMethod;
use Stripe\StripeClient;
use Stripe\StripeObject;

final class PaymentMethodServiceTest extends TestCase
{
    public function testHasDefaultReturnsFalseWhenNoStripeId(): void
    {
        $service = new PaymentMethodService(new StripeClient('sk_test'));

        self::assertFalse($service->hasDefault(new FakeBillable(null)));
    }

    public function testDefaultReturnsPaymentMethodWhenCustomerHasDefault(): void
    {
        $pm = new PaymentMethod('pm_123');
        $pm->type = 'card';
        $pm->card = StripeObject::constructFrom([
            'brand' => 'visa',
            'last4' => '4242',
            'exp_month' => 12,
            'exp_year' => 2030,
        ]);

        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->customers = new class () {
            /**
             * @param array<string, mixed> $opts
             */
            public function retrieve(string $id, array $opts): object
            {
                return (object) [
                    'invoice_settings' => (object) [
                        'default_payment_method' => 'pm_123',
                    ],
                ];
            }
        };
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->paymentMethods = new class ($pm) {
            public function __construct(private PaymentMethod $pm)
            {
            }

            public function retrieve(string $id): PaymentMethod
            {
                return $this->pm;
            }
        };

        $service = new PaymentMethodService($stripe);
        $default = $service->default(new FakeBillable('cus_123'));

        self::assertNotNull($default);
        self::assertSame('pm_123', $default->id());
        self::assertSame('visa', $default->brand());
    }

    public function testListReturnsCollectionWithPaymentMethods(): void
    {
        $pm = new PaymentMethod('pm_123');
        $pm->type = 'card';

        $stripe = new StripeClient('sk_test');
        /** @phpstan-ignore-next-line test double assignment */
        $stripe->paymentMethods = new class ($pm) {
            public function __construct(private PaymentMethod $pm)
            {
            }

            /**
             * @param array<string, mixed> $params
             */
            public function all(array $params): object
            {
                return (object) ['data' => [$this->pm]];
            }
        };

        $service = new PaymentMethodService($stripe);
        $list = $service->list(new FakeBillable('cus_123'));

        self::assertCount(1, $list);
        self::assertSame('pm_123', $list->first()->id());
    }
}
