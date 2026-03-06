<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\OrderController;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use CashierBundle\Entity\GeneratedInvoice;
use CashierBundle\Repository\GeneratedInvoiceRepository;
use CashierBundle\Model\Cashier;
use CashierBundle\Model\Checkout;
use CashierBundle\Service\CheckoutService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class OrderControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Cashier::clearServiceResolver();
    }

    public function testCreateCheckoutSessionUsesCashierAndReturnsPersistedOrderId(): void
    {
        $user = $this->makeUser();
        $cart = $this->makeCart($user);
        $capturedItems = null;
        $capturedOptions = null;
        $persistedOrder = null;

        $cartRepository = $this->createMock(CartRepository::class);
        $cartRepository->expects(self::once())
            ->method('findActiveCartForUser')
            ->with($user)
            ->willReturn($cart);

        $generatedInvoiceRepository = $this->createStub(GeneratedInvoiceRepository::class);
        $orderRepository = $this->createStub(OrderRepository::class);
        $userRepository = $this->createStub(UserRepository::class);
        $filesystem = $this->createStub(Filesystem::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(Order::class))
            ->willReturnCallback(static function (Order $order) use (&$persistedOrder): void {
                $persistedOrder = $order;
            });

        $entityManager->expects(self::exactly(2))
            ->method('flush')
            ->willReturnCallback(static function () use (&$persistedOrder): void {
                if ($persistedOrder instanceof Order && $persistedOrder->getId() === null) {
                    $reflection = new \ReflectionProperty($persistedOrder, 'id');
                    $reflection->setValue($persistedOrder, 42);
                }
            });

        $entityManager->expects(self::once())
            ->method('remove')
            ->with($cart->getItems()->first());

        $checkoutService = $this->createMock(CheckoutService::class);
        $checkoutService->expects(self::once())
            ->method('create')
            ->with(
                $user,
                self::callback(static function (array $items) use (&$capturedItems): bool {
                    $capturedItems = $items;

                    return $items[0]['price_data']['product_data']['name'] === 'Machine a cafe';
                }),
                self::callback(static function (array $options) use (&$capturedOptions): bool {
                    $capturedOptions = $options;

                    return str_contains((string) $options['success_url'], 'orderId=42')
                        && $options['metadata']['app_order_id'] === '42'
                        && $options['metadata']['app_user_id'] === '7';
                }),
            )
            ->willReturn(new Checkout((object) [
                'id' => 'cs_test_42',
                'url' => 'https://checkout.stripe.test/session/cs_test_42',
                'payment_intent' => null,
                'setup_intent' => null,
                'customer' => 'cus_test_42',
                'subscription' => null,
                'status' => 'open',
            ]));

        Cashier::resolveServicesUsing(static fn (string $service): object => match ($service) {
            'customer' => new class () {
                public function createOrGetCustomer(object $billable, array $options = []): string
                {
                    TestCase::assertSame(['fr'], $options['preferred_locales'] ?? null);
                    return 'cus_test_42';
                }

                public function updateCustomer(object $billable, array $options = []): void
                {
                    TestCase::assertSame(['fr'], $options['preferred_locales'] ?? null);
                }
            },
            default => throw new \LogicException(sprintf('Unexpected service %s', $service)),
        });

        $controller = new class ($userRepository, $cartRepository, $orderRepository, $generatedInvoiceRepository, $entityManager, $checkoutService, $filesystem, sys_get_temp_dir(), 'http://localhost:5173', $user) extends OrderController {
            public function __construct(
                UserRepository $userRepository,
                CartRepository $cartRepository,
                OrderRepository $orderRepository,
                GeneratedInvoiceRepository $generatedInvoiceRepository,
                EntityManagerInterface $entityManager,
                CheckoutService $checkoutService,
                Filesystem $filesystem,
                string $projectDir,
                string $frontendUrl,
                private readonly User $authenticatedUser,
            ) {
                parent::__construct($userRepository, $cartRepository, $orderRepository, $generatedInvoiceRepository, $entityManager, $checkoutService, $filesystem, $projectDir, $frontendUrl);
            }

            public function getUser(): ?UserInterface
            {
                return $this->authenticatedUser;
            }
        };

        $request = Request::create('/api/v1/orders/checkout/session', 'POST');
        $request->headers->set('origin', 'http://localhost:5173');
        $request->headers->set('Accept-Language', 'fr-FR,fr;q=0.9,en;q=0.8');
        $session = $this->createStub(SessionInterface::class);

        $response = $controller->createCheckoutSession($request, $session);
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('cs_test_42', $payload['sessionId']);
        self::assertSame('https://checkout.stripe.test/session/cs_test_42', $payload['checkoutUrl']);
        self::assertSame(42, $payload['order']['id']);
        self::assertSame('/orders/7/42', $payload['order']['detailPath']);
        self::assertSame('Machine a cafe', $capturedItems[0]['price_data']['product_data']['name']);
        self::assertSame('http://localhost:5173/checkout?canceled=1', $capturedOptions['cancel_url']);
    }

    public function testDeleteAllRemovesOrdersAndAssociatedInvoices(): void
    {
        $user = $this->makeUser();

        $firstOrder = (new Order())->setUser($user)->setTotal(1000);
        $secondOrder = (new Order())->setUser($user)->setTotal(2000);

        $invoice = (new GeneratedInvoice())
            ->setFilename('invoice.pdf')
            ->setRelativePath('var/data/invoices/invoice.pdf')
            ->setMimeType('application/pdf')
            ->setSize(12)
            ->setChecksum('checksum')
            ->setCurrency('eur')
            ->setAmountTotal(1000)
            ->setStatus('paid');

        $cartRepository = $this->createStub(CartRepository::class);
        $userRepository = $this->createStub(UserRepository::class);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects(self::once())
            ->method('findBy')
            ->with(['user' => $user])
            ->willReturn([$firstOrder, $secondOrder]);

        $generatedInvoiceRepository = $this->createMock(GeneratedInvoiceRepository::class);
        $generatedInvoiceRepository->expects(self::once())
            ->method('findBy')
            ->with([
                'billableId' => 7,
                'billableType' => User::class,
            ])
            ->willReturn([$invoice]);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::once())
            ->method('exists')
            ->with(sys_get_temp_dir() . '/var/data/invoices/invoice.pdf')
            ->willReturn(true);
        $filesystem->expects(self::once())
            ->method('remove')
            ->with(sys_get_temp_dir() . '/var/data/invoices/invoice.pdf');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(3))
            ->method('remove')
            ->with(self::callback(static fn (object $entity): bool => $entity instanceof Order || $entity instanceof GeneratedInvoice));
        $entityManager->expects(self::once())
            ->method('flush');

        $checkoutService = $this->createStub(CheckoutService::class);

        $controller = new class ($userRepository, $cartRepository, $orderRepository, $generatedInvoiceRepository, $entityManager, $checkoutService, $filesystem, $user) extends OrderController {
            public function __construct(
                UserRepository $userRepository,
                CartRepository $cartRepository,
                OrderRepository $orderRepository,
                GeneratedInvoiceRepository $generatedInvoiceRepository,
                EntityManagerInterface $entityManager,
                CheckoutService $checkoutService,
                Filesystem $filesystem,
                private readonly User $authenticatedUser,
            ) {
                parent::__construct($userRepository, $cartRepository, $orderRepository, $generatedInvoiceRepository, $entityManager, $checkoutService, $filesystem, sys_get_temp_dir(), 'http://localhost:5173');
            }

            public function getUser(): ?UserInterface
            {
                return $this->authenticatedUser;
            }
        };

        $response = $controller->deleteAll($this->createStub(SessionInterface::class));
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(2, $payload['deletedOrders']);
        self::assertSame(1, $payload['deletedInvoices']);
        self::assertSame(1, $payload['deletedInvoiceFiles']);
    }

    public function testGetOneByUserIncludesResolvedInvoice(): void
    {
        $user = $this->makeUser();
        $order = (new Order())
            ->setUser($user)
            ->setTotal(4990)
            ->setStripePaymentIntentId('pi_order_1');

        $invoice = (new GeneratedInvoice())
            ->setStripeInvoiceId('in_123')
            ->setFilename('INV-2026-42.pdf')
            ->setRelativePath('var/data/invoices/INV-2026-42.pdf')
            ->setMimeType('application/pdf')
            ->setSize(1024)
            ->setChecksum('checksum')
            ->setCurrency('eur')
            ->setAmountTotal(4990)
            ->setStatus('paid');
        $invoiceId = new \ReflectionProperty($invoice, 'id');
        $invoiceId->setValue($invoice, 12);

        $userRepository = $this->createStub(UserRepository::class);
        $cartRepository = $this->createStub(CartRepository::class);
        $filesystem = $this->createStub(Filesystem::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $checkoutService = $this->createStub(CheckoutService::class);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($order);

        $generatedInvoiceRepository = $this->createMock(GeneratedInvoiceRepository::class);
        $generatedInvoiceRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['stripePaymentIntentId' => 'pi_order_1'])
            ->willReturn($invoice);

        $controller = new class ($userRepository, $cartRepository, $orderRepository, $generatedInvoiceRepository, $entityManager, $checkoutService, $filesystem, $user) extends OrderController {
            public function __construct(
                UserRepository $userRepository,
                CartRepository $cartRepository,
                OrderRepository $orderRepository,
                GeneratedInvoiceRepository $generatedInvoiceRepository,
                EntityManagerInterface $entityManager,
                CheckoutService $checkoutService,
                Filesystem $filesystem,
                private readonly User $authenticatedUser,
            ) {
                parent::__construct($userRepository, $cartRepository, $orderRepository, $generatedInvoiceRepository, $entityManager, $checkoutService, $filesystem, sys_get_temp_dir(), 'http://localhost:5173');
            }

            public function getUser(): ?UserInterface
            {
                return $this->authenticatedUser;
            }
        };

        $response = $controller->getOneByUser(7, 42, $this->createStub(SessionInterface::class));
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('INV-2026-42.pdf', $payload['invoice']['filename']);
        self::assertSame('/api/v1/orders/12/invoice/download', $payload['invoice']['downloadPath']);
    }

    public function testGetOneByUserResolvesInvoiceByArchivedOrderMetadata(): void
    {
        $user = $this->makeUser();
        $order = (new Order())
            ->setUser($user)
            ->setTotal(4990)
            ->setStripePaymentIntentId('pi_missing');

        $orderId = new \ReflectionProperty($order, 'id');
        $orderId->setValue($order, 42);

        $invoice = (new GeneratedInvoice())
            ->setStripeInvoiceId('in_meta')
            ->setResourceType('order')
            ->setResourceId('42')
            ->setFilename('INV-META.pdf')
            ->setRelativePath('var/data/invoices/INV-META.pdf')
            ->setMimeType('application/pdf')
            ->setSize(1024)
            ->setChecksum('checksum')
            ->setCurrency('eur')
            ->setAmountTotal(4990)
            ->setStatus('paid');
        $invoiceId = new \ReflectionProperty($invoice, 'id');
        $invoiceId->setValue($invoice, 18);

        $userRepository = $this->createStub(UserRepository::class);
        $cartRepository = $this->createStub(CartRepository::class);
        $filesystem = $this->createStub(Filesystem::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $checkoutService = $this->createStub(CheckoutService::class);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($order);

        $generatedInvoiceRepository = $this->createMock(GeneratedInvoiceRepository::class);
        $generatedInvoiceRepository->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(static function (array $criteria) use ($invoice): ?GeneratedInvoice {
                return match ($criteria) {
                    ['stripePaymentIntentId' => 'pi_missing'] => null,
                    ['resourceType' => 'order', 'resourceId' => '42'] => $invoice,
                    default => throw new \LogicException('Unexpected criteria'),
                };
            });

        $controller = new class ($userRepository, $cartRepository, $orderRepository, $generatedInvoiceRepository, $entityManager, $checkoutService, $filesystem, $user) extends OrderController {
            public function __construct(
                UserRepository $userRepository,
                CartRepository $cartRepository,
                OrderRepository $orderRepository,
                GeneratedInvoiceRepository $generatedInvoiceRepository,
                EntityManagerInterface $entityManager,
                CheckoutService $checkoutService,
                Filesystem $filesystem,
                private readonly User $authenticatedUser,
            ) {
                parent::__construct($userRepository, $cartRepository, $orderRepository, $generatedInvoiceRepository, $entityManager, $checkoutService, $filesystem, sys_get_temp_dir(), 'http://localhost:5173');
            }

            public function getUser(): ?UserInterface
            {
                return $this->authenticatedUser;
            }
        };

        $response = $controller->getOneByUser(7, 42, $this->createStub(SessionInterface::class));
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('INV-META.pdf', $payload['invoice']['filename']);
        self::assertSame('/api/v1/orders/18/invoice/download', $payload['invoice']['downloadPath']);
    }

    private function makeUser(): User
    {
        $user = new User();
        $user->setId(7);
        $user->setEmail('alice@example.test');
        $user->setName('Alice');

        return $user;
    }

    private function makeCart(User $user): Cart
    {
        $product = new Product();
        $product->setName('Machine a cafe');
        $product->setSlug('machine-a-cafe');
        $product->setDescription('Modele demo');
        $product->setImageUrl('https://cdn.example.test/machine.jpg');
        $product->setPrice(12990);
        $product->setStock(5);

        $item = new CartItem();
        $item->setProduct($product);
        $item->setQuantity(1);

        $cart = new Cart();
        $cart->setUser($user);
        $cart->addItem($item);

        return $cart;
    }
}
