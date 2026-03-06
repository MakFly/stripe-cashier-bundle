<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiResponseHelper;
use App\Entity\CartItem;
use App\Entity\User;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Security\GuestCartCookieService;
use App\Serializer\CartSerializer;
use App\Service\User\CurrentUserResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/cart')]
class CartController extends AbstractController
{
    use ApiResponseHelper;

    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly CartItemRepository $cartItemRepository,
        private readonly ProductRepository $productRepository,
        private readonly GuestCartCookieService $guestCartCookieService,
        private readonly CartSerializer $cartSerializer,
        private readonly CurrentUserResolver $currentUserResolver,
    ) {
    }

    #[Route('/guest', name: 'api_cart_guest_get', methods: ['GET'])]
    public function getGuestCart(Request $request): JsonResponse
    {
        $decoded = $this->guestCartCookieService->decodeFromRequest($request);
        $hydratedItems = $this->hydrateGuestItems($decoded['items']);
        $serializedItems = $this->cartSerializer->serializeGuestItems($hydratedItems);
        $response = $this->guestCartResponse($serializedItems);

        if ($decoded['invalid']) {
            $this->guestCartCookieService->clear($response, $request);
            return $response;
        }

        $this->guestCartCookieService->attach(
            $response,
            $request,
            $this->cartSerializer->toCompactItems($hydratedItems),
        );

        return $response;
    }

    #[Route('/guest', name: 'api_cart_guest_put', methods: ['PUT'])]
    public function putGuestCart(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $items = $this->guestCartCookieService->normalizeItems($data['items'] ?? []);
        $hydratedItems = $this->hydrateGuestItems($items);
        $serializedItems = $this->cartSerializer->serializeGuestItems($hydratedItems);
        $response = $this->guestCartResponse($serializedItems);

        $this->guestCartCookieService->attach(
            $response,
            $request,
            $this->cartSerializer->toCompactItems($hydratedItems),
        );

        return $response;
    }

    #[Route('/guest', name: 'api_cart_guest_delete', methods: ['DELETE'])]
    public function clearGuestCart(Request $request): JsonResponse
    {
        $response = $this->apiResponse(['items' => [], 'total' => 0]);
        $this->guestCartCookieService->clear($response, $request);

        return $response;
    }

    #[Route('', name: 'api_cart_get', methods: ['GET'])]
    public function getCart(SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        return $this->userCartResponse($user);
    }

    #[Route('/merge-guest', name: 'api_cart_merge_guest', methods: ['POST'])]
    public function mergeGuest(Request $request, SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $decoded = $this->guestCartCookieService->decodeFromRequest($request);
        $itemsData = $decoded['items'];

        if (count($itemsData) > 0) {
            $cart = $this->cartRepository->findActiveCartForUser($user)
                ?? $this->cartRepository->createCartForUser($user);

            foreach ($itemsData as $itemData) {
                $productId = $itemData['productId'] ?? null;
                $quantity = $itemData['quantity'] ?? 1;
                if (!is_int($productId) || !is_int($quantity) || $quantity < 1) {
                    continue;
                }

                $product = $this->productRepository->find($productId);
                if ($product === null || $product->getStock() < 1) {
                    continue;
                }

                $safeQuantity = min($quantity, $product->getStock());
                if ($safeQuantity < 1) {
                    continue;
                }

                $this->cartItemRepository->addItemOrUpdateQuantity($cart, $product, $safeQuantity);
            }
        }

        $response = $this->userCartResponse($user);
        $this->guestCartCookieService->clear($response, $request);

        return $response;
    }

    #[Route('/items', name: 'api_cart_add_item', methods: ['POST'])]
    public function addItem(Request $request, SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $productInput = $data['product'] ?? $data['productId'] ?? null;
        $quantity = $data['quantity'] ?? 1;

        $product = null;

        if (is_string($productInput) && str_contains($productInput, '/')) {
            $rawIdentifier = basename($productInput);
            if (is_numeric($rawIdentifier)) {
                $product = $this->productRepository->find((int) $rawIdentifier);
            } else {
                $product = $this->productRepository->findOneBy(['slug' => $rawIdentifier]);
            }
        } elseif (is_numeric($productInput)) {
            $product = $this->productRepository->find((int) $productInput);
        } elseif (is_string($productInput) && $productInput !== '') {
            $product = $this->productRepository->findOneBy(['slug' => $productInput]);
        }

        if ($product === null) {
            return $this->apiError('Product ID is required', Response::HTTP_BAD_REQUEST);
        }

        if ($quantity < 1) {
            return $this->apiError('Quantity must be at least 1', Response::HTTP_BAD_REQUEST);
        }

        if ($product->getStock() < $quantity) {
            return $this->apiError('Not enough stock', Response::HTTP_BAD_REQUEST);
        }

        $cart = $this->cartRepository->findActiveCartForUser($user)
            ?? $this->cartRepository->createCartForUser($user);

        $item = $this->cartItemRepository->addItemOrUpdateQuantity($cart, $product, $quantity);

        return new JsonResponse($this->cartSerializer->serializeItem($item), Response::HTTP_CREATED);
    }

    #[Route('/items/{id}', name: 'api_cart_update_item', methods: ['PATCH'])]
    public function updateItem(int $id, Request $request, SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $item = $this->cartItemRepository->find($id);
        if ($item === null) {
            return $this->apiError('Item not found', Response::HTTP_NOT_FOUND);
        }

        if ($item->getCart()?->getUser()?->getId() !== $user->getId()) {
            return $this->apiError('Access denied', Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $quantity = $data['quantity'] ?? null;

        if ($quantity === null || $quantity < 1) {
            return $this->apiError('Quantity must be at least 1', Response::HTTP_BAD_REQUEST);
        }

        $item->setQuantity($quantity);
        $this->cartItemRepository->save($item);

        return new JsonResponse($this->cartSerializer->serializeItem($item));
    }

    #[Route('/items/{id}', name: 'api_cart_remove_item', methods: ['DELETE'])]
    public function removeItem(int $id, SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $item = $this->cartItemRepository->find($id);
        if ($item === null) {
            return $this->apiError('Item not found', Response::HTTP_NOT_FOUND);
        }

        if ($item->getCart()?->getUser()?->getId() !== $user->getId()) {
            return $this->apiError('Access denied', Response::HTTP_FORBIDDEN);
        }

        $this->cartItemRepository->removeItem($item);

        return $this->apiResponse(['message' => 'Item removed']);
    }

    #[Route('/clear', name: 'api_cart_clear', methods: ['POST'])]
    public function clear(SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $cart = $this->cartRepository->findActiveCartForUser($user);
        if ($cart !== null) {
            foreach ($cart->getItems() as $item) {
                $this->cartItemRepository->removeItem($item);
            }
        }

        return $this->apiResponse(['message' => 'Cart cleared']);
    }

    #[Route('/sync', name: 'api_cart_sync', methods: ['POST'])]
    public function sync(Request $request, SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session, $this->getUser());
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $itemsData = $data['items'] ?? [];

        $cart = $this->cartRepository->findActiveCartForUser($user)
            ?? $this->cartRepository->createCartForUser($user);

        foreach ($itemsData as $itemData) {
            $productId = $itemData['productId'] ?? null;
            $quantity = $itemData['quantity'] ?? 1;

            if ($productId === null || $quantity < 1) {
                continue;
            }

            $product = $this->productRepository->find($productId);
            if ($product === null) {
                continue;
            }

            $this->cartItemRepository->addItemOrUpdateQuantity($cart, $product, $quantity);
        }

        return $this->userCartResponse($user);
    }

    /**
     * @param list<array{productId: int, quantity: int}> $items
     * @return list<array{productId: int, quantity: int, name: string, slug: string, price: int, imageUrl: ?string, stock: int, description: ?string}>
     */
    private function hydrateGuestItems(array $items): array
    {
        $hydrated = [];

        foreach ($items as $item) {
            $product = $this->productRepository->find($item['productId']);
            if ($product === null) {
                continue;
            }

            if ($product->getStock() < 1) {
                continue;
            }

            $quantity = min($item['quantity'], $product->getStock());
            if ($quantity < 1) {
                continue;
            }

            $hydrated[] = [
                'productId' => $product->getId(),
                'quantity' => $quantity,
                'name' => $product->getName(),
                'slug' => $product->getSlug(),
                'price' => $product->getPrice(),
                'imageUrl' => $product->getImageUrl(),
                'stock' => $product->getStock(),
                'description' => $product->getDescription(),
            ];
        }

        return $hydrated;
    }

    /**
     * @param list<array{productId: int, quantity: int, name: string, slug: string, price: int, imageUrl: ?string, stock: int, description: ?string}> $items
     */
    private function guestCartResponse(array $items): JsonResponse
    {
        $total = $this->cartSerializer->calculateTotal($items);

        return $this->apiResponse([
            'items' => $items,
            'total' => $total,
        ]);
    }

    private function userCartResponse(User $user): JsonResponse
    {
        $cart = $this->cartRepository->findActiveCartForUser($user);
        if ($cart === null) {
            return $this->apiResource('Cart', '/api/v1/cart', [
                'id'    => null,
                'items' => [],
                'total' => 0,
            ]);
        }

        $items = $cart->getItems()->map(fn (CartItem $item) => $this->cartSerializer->serializeItem($item))->toArray();

        return $this->apiResource('Cart', '/api/v1/cart', [
            'id'    => $cart->getId(),
            'items' => array_values($items),
            'total' => $cart->getTotal(),
        ]);
    }
}
