<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Billing\SubscriptionCatalog;
use App\Controller\SubscriptionController;
use App\Entity\User;
use App\Repository\UserRepository;
use CashierBundle\Entity\Subscription;
use CashierBundle\Model\Cashier;
use CashierBundle\Model\Checkout;
use CashierBundle\Service\CheckoutService;
use CashierBundle\Service\SubscriptionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SubscriptionControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Cashier::clearServiceResolver();
    }

    public function testPlansReturnsPublicCatalog(): void
    {
        $controller = $this->makeController(
            new SubscriptionCatalog('price_starter_month', 'price_starter_year', 'price_pro_month', 'price_pro_year'),
            $this->makeUser(),
            $this->createStub(CheckoutService::class),
            $this->createStub(SubscriptionService::class),
        );

        $response = $controller->plans();
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(2, $payload['hydra:totalItems']);
        self::assertSame('starter', $payload['hydra:member'][0]['code']);
        self::assertSame(14, $payload['hydra:member'][0]['trialDays']);
    }

    public function testCreateCheckoutSessionUsesConfiguredPlan(): void
    {
        $user = $this->makeUser();
        $catalog = new SubscriptionCatalog('price_starter_month', 'price_starter_year', 'price_pro_month', 'price_pro_year');

        Cashier::resolveServicesUsing(static fn (string $service): object => match ($service) {
            'customer' => new class () {
                public function createOrGetCustomer(object $billable, array $options = []): string
                {
                    TestCase::assertSame(['fr'], $options['preferred_locales'] ?? null);

                    return 'cus_sub_1';
                }

                public function updateCustomer(object $billable, array $options = []): void
                {
                    TestCase::assertSame(['fr'], $options['preferred_locales'] ?? null);
                }
            },
            'subscription' => new class () {
                public function get(object $billable, string $type = 'default'): ?Subscription
                {
                    return null;
                }
            },
            default => throw new \LogicException(sprintf('Unexpected service %s', $service)),
        });

        $checkoutService = $this->createMock(CheckoutService::class);
        $checkoutService->expects(self::once())
            ->method('createSubscription')
            ->with(
                $user,
                [['price' => 'price_starter_year', 'quantity' => 1]],
                self::callback(static function (array $options): bool {
                    return $options['metadata']['app_resource_type'] === 'subscription_plan'
                        && $options['metadata']['app_resource_id'] === 'starter'
                        && $options['metadata']['billing_cycle'] === 'yearly'
                        && $options['subscription_data']['trial_period_days'] === 14
                        && str_contains((string) $options['success_url'], '/subscriptions?checkout=success');
                }),
            )
            ->willReturn(new Checkout((object) [
                'id' => 'cs_sub_123',
                'url' => 'https://checkout.stripe.test/subscriptions/cs_sub_123',
                'payment_intent' => null,
                'setup_intent' => null,
                'customer' => 'cus_sub_1',
                'subscription' => 'sub_123',
                'status' => 'open',
            ]));

        $controller = $this->makeController(
            $catalog,
            $user,
            $checkoutService,
            $this->createStub(SubscriptionService::class),
        );

        $request = Request::create('/api/v1/subscriptions/checkout/session', 'POST', server: [
            'HTTP_ORIGIN' => 'http://localhost:5173',
            'HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9',
        ], content: json_encode([
            'planCode' => 'starter',
            'billingCycle' => 'yearly',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->createCheckoutSession($request, $this->createStub(SessionInterface::class));
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('cs_sub_123', $payload['sessionId']);
        self::assertSame('starter', $payload['planCode']);
        self::assertSame('yearly', $payload['billingCycle']);
    }

    public function testCreateCheckoutSessionReturnsServiceUnavailableWhenPriceIsMissing(): void
    {
        $controller = $this->makeController(
            new SubscriptionCatalog('', '', '', ''),
            $this->makeUser(),
            $this->createStub(CheckoutService::class),
            $this->createStub(SubscriptionService::class),
        );

        Cashier::resolveServicesUsing(static fn (): object => new class () {
            public function get(object $billable, string $type = 'default'): ?Subscription
            {
                return null;
            }
        });

        $request = Request::create('/api/v1/subscriptions/checkout/session', 'POST', content: json_encode([
            'planCode' => 'starter',
            'billingCycle' => 'monthly',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->createCheckoutSession($request, $this->createStub(SessionInterface::class));
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Subscription plan is not configured yet', $payload['hydra:description']);
    }

    public function testCurrentReturnsMatchedPlan(): void
    {
        $user = $this->makeUser();
        $subscription = (new Subscription())
            ->setType('default')
            ->setStripeId('sub_current')
            ->setStripeStatus(Subscription::STATUS_TRIALING)
            ->setStripePrice('price_starter_month')
            ->setTrialEndsAt(new \DateTimeImmutable('+14 days'));

        Cashier::resolveServicesUsing(static fn (): object => new class ($subscription) {
            public function __construct(private readonly Subscription $subscription)
            {
            }

            public function get(object $billable, string $type = 'default'): ?Subscription
            {
                return $this->subscription;
            }
        });

        $controller = $this->makeController(
            new SubscriptionCatalog('price_starter_month', 'price_starter_year', 'price_pro_month', 'price_pro_year'),
            $user,
            $this->createStub(CheckoutService::class),
            $this->createStub(SubscriptionService::class),
        );

        $response = $controller->current($this->createStub(SessionInterface::class));
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($payload['hasSubscription']);
        self::assertSame('starter', $payload['subscription']['plan']['code']);
        self::assertSame('monthly', $payload['subscription']['plan']['billingCycle']);
    }

    private function makeController(
        SubscriptionCatalog $catalog,
        User $authenticatedUser,
        CheckoutService $checkoutService,
        SubscriptionService $subscriptionService,
    ): SubscriptionController {
        $userRepository = $this->createStub(UserRepository::class);

        return new class ($catalog, $userRepository, $checkoutService, $subscriptionService, 'http://localhost:5173', $authenticatedUser) extends SubscriptionController {
            public function __construct(
                SubscriptionCatalog $catalog,
                UserRepository $userRepository,
                CheckoutService $checkoutService,
                SubscriptionService $subscriptionService,
                string $frontendUrl,
                private readonly User $authenticatedUser,
            ) {
                parent::__construct($catalog, $userRepository, $checkoutService, $subscriptionService, $frontendUrl);
            }

            public function getUser(): ?UserInterface
            {
                return $this->authenticatedUser;
            }
        };
    }

    private function makeUser(): User
    {
        $user = new User();
        $user->setId(7);
        $user->setEmail('alice@example.test');
        $user->setName('Alice');

        return $user;
    }
}
