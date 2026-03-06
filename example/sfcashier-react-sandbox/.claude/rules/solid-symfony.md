# SOLID & SRP for Symfony Controllers

## SRP — Single Responsibility Principle

Un controller Symfony ne doit faire que **3 choses**:
1. **Récupérer** les données de la requête
2. **Déléguer** au service approprié
3. **Retourner** la réponse

### ❌ Controller SRP-violé (actuel)

```php
class OrderController extends AbstractController
{
    // VIOLATION: logique métier
    private function createOrderFromCart(User $user, Cart $cart): Order { ... }
    
    // VIOLATION: sérialisation
    private function serializeOrder(Order $order): array { ... }
    
    // VIOLATION: résolution externe
    private function resolveInvoiceForOrder(Order $order): ?GeneratedInvoice { ... }
    
    // OK: seulement orchestration
    public function create(SessionInterface $session): JsonResponse
    {
        $user = $this->getCurrentUser($session);  // demandé
        $order = $this->createOrderFromCart($user, $cart);  // délégué
        return $this->apiResource('Order', ...);  // réponse
    }
}
```

### ✅ Controller SRP-conforme

```php
class OrderController extends AbstractController
{
    public function __construct(
        private readonly CurrentUserResolver $currentUserResolver,
        private readonly OrderCreationService $orderCreationService,
        private readonly OrderSerializer $orderSerializer,
    ) {}

    public function create(SessionInterface $session): JsonResponse
    {
        $user = $this->currentUserResolver->resolve($session);
        if ($user === null) {
            return $this->apiError('Not authenticated', Response::HTTP_UNAUTHORIZED);
        }

        $cart = $this->cartRepository->findActiveCartForUser($user);
        if ($cart === null || $cart->getItems()->isEmpty()) {
            return $this->apiError('Cart is empty', Response::HTTP_BAD_REQUEST);
        }

        $order = $this->orderCreationService->createFromCart($user, $cart);

        return $this->apiResource(
            'Order',
            '/api/v1/orders/' . $order->getId(),
            $this->orderSerializer->serialize($order, withItems: true),
            Response::HTTP_CREATED,
        );
    }
}
```

## Services à extraire — Patterns

### 1. CurrentUserResolver

```php
// src/Service/User/CurrentUserResolver.php
namespace App\Service\User;

class CurrentUserResolver
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function resolve(SessionInterface $session, ?User $authenticatedUser): ?User
    {
        if ($authenticatedUser instanceof User) {
            return $authenticatedUser;
        }

        $userId = $session->get('user_id');
        if ($userId === null) {
            return null;
        }

        return $this->userRepository->find($userId);
    }
}
```

### 2. EntitySerializer

```php
// src/Serializer/OrderSerializer.php
namespace App\Serializer;

class OrderSerializer
{
    public function __construct(
        private readonly GeneratedInvoiceRepository $generatedInvoiceRepository,
    ) {}

    public function serialize(Order $order, bool $withItems = false): array
    {
        $base = [
            '@id' => '/api/v1/orders/' . $order->getId(),
            '@type' => 'Order',
            'id' => $order->getId(),
            // ...
        ];

        $invoice = $this->resolveInvoiceForOrder($order);
        if ($invoice instanceof GeneratedInvoice) {
            $base['invoice'] = $this->serializeInvoice($invoice);
        }

        if ($withItems) {
            $base['items'] = $order->getItems()->map(
                fn (OrderItem $item): array => $this->serializeOrderItem($item)
            )->toArray();
        }

        return $base;
    }
}
```

### 3. OrderCreationService

```php
// src/Service/Order/OrderCreationService.php
namespace App\Service\Order;

class OrderCreationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function createFromCart(User $user, Cart $cart): Order
    {
        $order = new Order();
        $order->setUser($user);
        $order->setTotal($cart->getTotal());
        $order->setStatus(OrderStatus::PENDING);

        foreach ($cart->getItems() as $cartItem) {
            $product = $cartItem->getProduct();
            if ($product === null) {
                continue;
            }

            $orderItem = new OrderItem();
            $orderItem
                ->setProductId((int) $product->getId())
                ->setProductName($product->getName())
                // ...
            $order->addItem($orderItem);
        }

        $this->entityManager->persist($order);
        return $order;
    }
}
```

### 4. CheckoutLineItemBuilder

```php
// src/Service/Stripe/CheckoutLineItemBuilder.php
namespace App\Service\Stripe;

class CheckoutLineItemBuilder
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function build(Order $order): array
    {
        $lineItems = [];
        foreach ($order->getItems() as $orderItem) {
            $lineItem = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $orderItem->getUnitPrice(),
                    'product_data' => [
                        'name' => $orderItem->getProductName(),
                    ],
                ],
                'quantity' => $orderItem->getQuantity(),
            ];
            // ...
            $lineItems[] = $lineItem;
        }
        return $lineItems;
    }
}
```

### 5. LocaleResolver

```php
// src/Service/Request/LocaleResolver.php
namespace App\Service\Request;

class LocaleResolver
{
    public function resolve(Request $request): string
    {
        $acceptLanguage = strtolower((string) $request->headers->get('Accept-Language', ''));
        if ($acceptLanguage === '') {
            return 'en';
        }

        $candidates = preg_split('/\s*,\s*/', $acceptLanguage) ?: [];
        foreach ($candidates as $candidate) {
            $locale = trim(explode(';', $candidate)[0] ?? '');
            if ($locale === '') {
                continue;
            }
            if (str_starts_with($locale, 'fr')) return 'fr';
            if (str_starts_with($locale, 'en')) return 'en';
        }

        return 'en';
    }
}
```

## DRY — Règle d'extraction

**Si une méthode privée apparaît dans 2+ controllers → Service partagé**

| Méthode | Controllers concernés | Service |
|---------|----------------------|---------|
| `getCurrentUser` | Order, Cart, Subscription | `CurrentUserResolver` |
| `serializeOrder*` | Order | `OrderSerializer` |
| `serializeProduct/Item` | Cart | `CartSerializer` |
| `serializeSubscription` | Subscription | `SubscriptionSerializer` |
| `resolvePreferredLocale` | Order, Subscription | `LocaleResolver` |
| `resolveFrontendOrigin` | Order, Subscription | `FrontendOriginResolver` |
| `createOrderFromCart` | Order | `OrderCreationService` |
| `buildCheckoutLineItems` | Order | `CheckoutLineItemBuilder` |

## Structure de dossiers recommandée

```
src/
├── Controller/
│   ├── OrderController.php
│   ├── CartController.php
│   └── SubscriptionController.php
├── Service/
│   ├── User/
│   │   └── CurrentUserResolver.php
│   ├── Order/
│   │   ├── OrderCreationService.php
│   │   └── CheckoutLineItemBuilder.php
│   ├── Cart/
│   │   └── CartService.php
│   ├── Subscription/
│   │   └── SubscriptionService.php
│   └── Request/
│       ├── LocaleResolver.php
│       └── FrontendOriginResolver.php
├── Serializer/
│   ├── OrderSerializer.php
│   ├── CartSerializer.php
│   └── SubscriptionSerializer.php
└── Repository/
```

## Injection de dépendances

**Toujours injecter par constructeur**, jamais par `$this->get()`:

```php
// ✅ Bon
public function __construct(
    private readonly OrderSerializer $orderSerializer,
) {}

// ❌ Mauvais (deprecated)
$serializer = $this->get(OrderSerializer::class);
```

## SOLID — Autres principes

### OCP — Open/Closed Principle
- Les services doivent être **extensibles** sans modification
- Utiliser des interfaces si besoin de variants

### LSP — Liskov Substitution Principle
- Les services substitués doivent respecter le contrat
- Ne pas changer le comportement attendu

### ISP — Interface Segregation
- Préférer plusieurs petits services spécialisés
- `CurrentUserResolver` ≠ `OrderCreationService`

### DIP — Dependency Inversion
- Dépendre des **abstractions** (interfaces), pas des implémentations
- Mais pour des services simples, une classe concrète suffit

## Régles de nommage

| Type | Pattern | Exemples |
|------|---------|----------|
| Service | `Verb + [Entity]` | `CreateOrder`, `BuildLineItems`, `ResolveLocale` |
| Serializer | `Entity + Serializer` | `OrderSerializer`, `CartItemSerializer` |
| Resolver | `Resolve + Something` | `ResolveCurrentUser`, `ResolveFrontendOrigin` |
| Builder | `Build + Something` | `BuildCheckoutLineItems` |
| Transformer | `Transform + Something` | `TransformToApiResponse` |
