<?php

declare(strict_types=1);

namespace CashierBundle\Tests\Unit\Concerns;

use CashierBundle\Concerns\BillableTrait;
use CashierBundle\Contract\BillableEntityInterface;
use CashierBundle\Model\Cashier;
use CashierBundle\Model\Checkout;
use CashierBundle\Model\PaymentMethod;
use CashierBundle\Service\SubscriptionBuilder;
use CashierBundle\Tests\Support\TestStripeClient;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Stripe\PaymentMethod as StripePaymentMethod;

final class BillableTraitTest extends TestCase
{
    protected function tearDown(): void
    {
        Cashier::clearServiceResolver();
    }

    public function testTraitDelegatesToConfiguredCashierServices(): void
    {
        $paymentMethod = new PaymentMethod(new StripePaymentMethod('pm_test'));
        $checkout = new Checkout((object) [
            'id' => 'cs_test',
            'url' => 'https://checkout.example.test',
            'payment_intent' => null,
            'setup_intent' => null,
            'customer' => 'cus_test',
            'subscription' => null,
            'status' => 'open',
        ]);

        $subscriptionBuilder = new SubscriptionBuilder(
            $this->makeBillable(),
            'default',
            new TestStripeClient(),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(\CashierBundle\Repository\SubscriptionRepository::class),
        );

        Cashier::resolveServicesUsing(static function (string $service) use ($paymentMethod, $checkout, $subscriptionBuilder): object {
            return match ($service) {
                'customer' => new class () {
                    public function getStripeId(object $billable): string
                    {
                        return 'cus_test';
                    }

                    public function hasStripeId(object $billable): bool
                    {
                        return true;
                    }

                    /**
                     * @param array<string, mixed> $options
                     */
                    public function createOrGetCustomer(object $billable, array $options = []): string
                    {
                        return 'cus_created';
                    }

                    public function asStripeCustomer(object $billable): object
                    {
                        return (object) ['id' => 'cus_test'];
                    }
                },
                'payment_method' => new class ($paymentMethod) {
                    public function __construct(private readonly PaymentMethod $paymentMethod)
                    {
                    }

                    public function hasDefault(object $billable): bool
                    {
                        return true;
                    }

                    public function getDefault(object $billable): PaymentMethod
                    {
                        return $this->paymentMethod;
                    }

                    /**
                     * @return ArrayCollection<int, PaymentMethod>
                     */
                    public function all(object $billable, ?string $type = null): ArrayCollection
                    {
                        return new ArrayCollection([$this->paymentMethod]);
                    }
                },
                'invoice' => new class () {
                    public function getBalance(object $billable): string
                    {
                        return '1200';
                    }
                },
                'portal' => new class () {
                    public function url(object $billable, ?string $returnUrl = null): string
                    {
                        return $returnUrl ?? 'https://portal.example.test';
                    }
                },
                'tax' => new class () {
                    /**
                     * @return array<int, string>
                     */
                    public function getTaxRatesForEntity(object $billable): array
                    {
                        return ['txr_test'];
                    }

                    public function isTaxExempt(object $billable): bool
                    {
                        return false;
                    }
                },
                'checkout' => new class ($checkout) {
                    public function __construct(private readonly Checkout $checkout)
                    {
                    }

                    public function createCharge(object $billable, int $amount, string $name, int $quantity = 1): Checkout
                    {
                        return $this->checkout;
                    }
                },
                'subscription' => new class ($subscriptionBuilder) {
                    public function __construct(private readonly SubscriptionBuilder $subscriptionBuilder)
                    {
                    }

                    /**
                     * @param string|array<int, string> $prices
                     */
                    public function newSubscription(object $billable, string $type, string|array $prices = []): SubscriptionBuilder
                    {
                        return $this->subscriptionBuilder;
                    }
                },
                default => throw new \LogicException(sprintf('Unexpected service "%s".', $service)),
            };
        });

        $billable = $this->makeBillable();

        self::assertSame('cus_test', $billable->stripeId());
        self::assertTrue($billable->hasStripeId());
        self::assertSame('cus_created', $billable->createOrGetStripeCustomer());
        self::assertSame('pm_test', $billable->defaultPaymentMethod()?->id());
        self::assertCount(1, $billable->paymentMethods());
        self::assertSame('1200', $billable->balance());
        self::assertSame('https://app.example.test/return', $billable->billingPortalUrl('https://app.example.test/return'));
        self::assertSame(['txr_test'], $billable->taxRates());
        self::assertFalse($billable->isTaxExempt());
        self::assertSame('cs_test', $billable->checkoutCharge(1000, 'Demo')->id());
        self::assertInstanceOf(SubscriptionBuilder::class, $billable->newSubscription('default', 'price_demo'));
    }

    public function testTraitFailsFastWhenResolverIsMissing(): void
    {
        $billable = $this->makeBillable();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cashier service resolver is not configured');

        $billable->stripeId();
    }

    private function makeBillable(): BillableEntityInterface
    {
        return new class () implements BillableEntityInterface {
            use BillableTrait;

            public function getId(): ?int
            {
                return 1;
            }

            public function getEmail(): string
            {
                return 'billable@example.test';
            }

            public function getName(): ?string
            {
                return 'Billable';
            }
        };
    }
}
