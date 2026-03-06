<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiResponseHelper;
use App\Entity\Order;
use App\Entity\User;
use App\Exception\CheckoutSessionCreationException;
use App\Exception\CheckoutSessionVerificationException;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use App\Serializer\OrderSerializer;
use App\Service\Order\OrderCreationService;
use App\Service\Request\FrontendOriginResolver;
use App\Service\Request\LocaleResolver;
use App\Service\Stripe\CheckoutLineItemBuilder;
use App\Service\User\CurrentUserResolver;
use CashierBundle\Repository\GeneratedInvoiceRepository;
use CashierBundle\Service\CheckoutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/orders')]
class OrderController extends AbstractController
{
    use ApiResponseHelper;

    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly OrderRepository $orderRepository,
        private readonly GeneratedInvoiceRepository $generatedInvoiceRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CheckoutService $checkoutService,
        private readonly Filesystem $filesystem,
        private readonly CurrentUserResolver $currentUserResolver,
        private readonly OrderSerializer $orderSerializer,
        private readonly OrderCreationService $orderCreationService,
        private readonly CheckoutLineItemBuilder $checkoutLineItemBuilder,
        private readonly LocaleResolver $localeResolver,
        private readonly FrontendOriginResolver $frontendOriginResolver,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[Route('', name: 'api_orders_list', methods: ['GET'])]
    public function list(SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $orders = $this->orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->apiCollection(
            '/api/v1/contexts/Order',
            '/api/v1/orders',
            array_map(fn (Order $o) => $this->orderSerializer->serialize($o), $orders),
            count($orders),
        );
    }

    #[Route('', name: 'api_orders_delete_all', methods: ['DELETE'])]
    public function deleteAll(SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $orders = $this->orderRepository->findBy(['user' => $user]);
        $invoices = $this->generatedInvoiceRepository->findBy([
            'billableId' => $user->getId(),
            'billableType' => User::class,
        ]);

        foreach ($orders as $order) {
            $this->entityManager->remove($order);
        }

        $deletedOrders = count($orders);
        $invoiceSummary = $this->purgeGeneratedInvoices($invoices);
        $this->entityManager->flush();

        return $this->apiResponse([
            'deletedOrders' => $deletedOrders,
            'deletedInvoices' => $invoiceSummary['deleted'],
            'deletedInvoiceFiles' => $invoiceSummary['files'],
        ]);
    }

    #[Route('/{invoiceId<\d+>}/invoice/download', name: 'api_orders_invoice_download', methods: ['GET'])]
    public function downloadInvoice(int $invoiceId, SessionInterface $session): Response
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $invoice = $this->generatedInvoiceRepository->find($invoiceId);
        if (!$invoice instanceof \CashierBundle\Entity\GeneratedInvoice
            || $invoice->getBillableId() !== $user->getId()
            || $invoice->getBillableType() !== User::class) {
            return $this->apiError('Invoice not found', Response::HTTP_NOT_FOUND);
        }

        $absolutePath = $this->projectDir . '/' . ltrim($invoice->getRelativePath(), '/');
        if (!$this->filesystem->exists($absolutePath)) {
            return $this->apiError('Invoice file not found', Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($absolutePath);
        $response->headers->set('Content-Type', $invoice->getMimeType());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $invoice->getFilename());

        return $response;
    }

    #[Route('/{orderId<\d+>}', name: 'api_orders_get', methods: ['GET'])]
    public function getOne(int $orderId, SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $order = $this->orderRepository->find($orderId);
        if (!$order instanceof Order || $order->getUser()?->getId() !== $user->getId()) {
            return $this->apiError('Order not found', Response::HTTP_NOT_FOUND);
        }

        return $this->apiResource(
            'Order',
            '/api/v1/orders/' . $order->getId(),
            $this->orderSerializer->serialize($order, true),
        );
    }

    #[Route('/user/{userId<\d+>}/{orderId<\d+>}', name: 'api_orders_get_by_user', methods: ['GET'])]
    public function getOneByUser(int $userId, int $orderId, SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        if ($user->getId() !== $userId) {
            return $this->apiError('Access denied', Response::HTTP_FORBIDDEN);
        }

        $order = $this->orderRepository->find($orderId);
        if (!$order instanceof Order || $order->getUser()?->getId() !== $userId) {
            return $this->apiError('Order not found', Response::HTTP_NOT_FOUND);
        }

        return $this->apiResource(
            'Order',
            '/api/v1/orders/' . $order->getId(),
            $this->orderSerializer->serialize($order, true),
        );
    }

    #[Route('', name: 'api_orders_create', methods: ['POST'])]
    public function create(SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $cart = $this->cartRepository->findActiveCartForUser($user);
        if ($cart === null || $cart->getItems()->isEmpty()) {
            return $this->apiError('Cart is empty', Response::HTTP_BAD_REQUEST);
        }

        $order = $this->orderCreationService->createFromCart($user, $cart);
        $order->setStatus(\App\Entity\OrderStatus::PAID);

        $this->orderCreationService->clearCart($cart);
        $this->entityManager->flush();

        return $this->apiResource(
            'Order',
            '/api/v1/orders/' . $order->getId(),
            $this->orderSerializer->serialize($order, true),
            Response::HTTP_CREATED,
        );
    }

    #[Route('/checkout/session', name: 'api_orders_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request, SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $cart = $this->cartRepository->findActiveCartForUser($user);
        if ($cart === null || $cart->getItems()->isEmpty()) {
            return $this->apiError('Cart is empty', Response::HTTP_BAD_REQUEST);
        }

        $order = $this->orderCreationService->createFromCart($user, $cart);
        $this->entityManager->flush();
        $origin = $this->frontendOriginResolver->resolve($request);
        $lineItems = $this->checkoutLineItemBuilder->build($order);

        try {
            $preferredLocale = $this->localeResolver->resolve($request);
            $customerOptions = [
                'preferred_locales' => [$preferredLocale],
            ];

            $user->createOrGetStripeCustomer($customerOptions);
            $user->updateStripeCustomer($customerOptions);
            $checkoutSession = $this->checkoutService->create($user, $lineItems, [
                'payment_method_types' => ['card'],
                'success_url' => sprintf(
                    '%s/checkout/success?orderId=%d&session_id={CHECKOUT_SESSION_ID}',
                    $origin,
                    $order->getId()
                ),
                'cancel_url' => sprintf('%s/checkout?canceled=1', $origin),
                'metadata' => [
                    'app_order_id' => (string) $order->getId(),
                    'app_user_id' => (string) $user->getId(),
                ],
            ]);
        } catch (\Throwable $exception) {
            $this->orderCreationService->discard($order);
            throw new CheckoutSessionCreationException($exception);
        }

        $order->setStripeCheckoutSessionId($checkoutSession->id());
        $this->orderCreationService->clearCart($cart);
        $this->entityManager->flush();

        return $this->apiResource(
            'CheckoutSession',
            '/api/v1/orders/checkout/session',
            [
                'order' => $this->orderSerializer->serialize($order, true),
                'checkoutUrl' => $checkoutSession->url(),
                'sessionId' => $checkoutSession->id(),
            ],
            Response::HTTP_CREATED,
        );
    }

    #[Route('/checkout/confirm', name: 'api_orders_checkout_confirm', methods: ['POST'])]
    public function confirmCheckoutSession(Request $request, SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($request->getContent(), true);
        $orderId = (int) ($payload['orderId'] ?? 0);
        $sessionId = (string) ($payload['sessionId'] ?? '');

        if ($orderId < 1 || $sessionId === '') {
            return $this->apiError('Missing orderId or sessionId', Response::HTTP_BAD_REQUEST);
        }

        $order = $this->orderRepository->find($orderId);
        if (!$order instanceof Order || $order->getUser()?->getId() !== $user->getId()) {
            return $this->apiError('Order not found', Response::HTTP_NOT_FOUND);
        }

        if ($order->getStripeCheckoutSessionId() !== $sessionId) {
            return $this->apiError('Invalid checkout session', Response::HTTP_BAD_REQUEST);
        }

        $checkoutSession = $this->checkoutService->findSession($sessionId);
        if ($checkoutSession === null) {
            throw new CheckoutSessionVerificationException();
        }

        $stripeSession = $checkoutSession->asStripeCheckoutSession();
        if (($stripeSession->payment_status ?? null) === 'paid') {
            $order->setStatus(\App\Entity\OrderStatus::PAID);
            $paymentIntentId = $checkoutSession->paymentIntentId();
            if (is_string($paymentIntentId)) {
                $order->setStripePaymentIntentId($paymentIntentId);
            }
            $this->entityManager->flush();
        }

        return $this->apiResource(
            'Order',
            '/api/v1/orders/' . $order->getId(),
            $this->orderSerializer->serialize($order, true),
        );
    }

    /**
     * @param list<\CashierBundle\Entity\GeneratedInvoice> $invoices
     *
     * @return array{deleted:int, files:int}
     */
    private function purgeGeneratedInvoices(array $invoices): array
    {
        $deletedInvoices = 0;
        $deletedFiles = 0;

        foreach ($invoices as $invoice) {
            $relativePath = ltrim($invoice->getRelativePath(), '/');
            $absolutePath = $this->projectDir . '/' . $relativePath;

            if ($relativePath !== '' && $this->filesystem->exists($absolutePath)) {
                $this->filesystem->remove($absolutePath);
                ++$deletedFiles;
            }

            $this->entityManager->remove($invoice);
            ++$deletedInvoices;
        }

        return [
            'deleted' => $deletedInvoices,
            'files' => $deletedFiles,
        ];
    }
}
