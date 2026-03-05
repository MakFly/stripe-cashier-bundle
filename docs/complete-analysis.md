# Analyse Complète du Code Source CashierSymfony

## 1. Entités et Traits

### 1.1 Entités

#### StripeCustomer (`CashierBundle\Entity\StripeCustomer`)

**Namespace**: `CashierBundle\Entity`

**Propriétés**:
```php
// Identifiant unique
private ?int $id = null;

// Entité facturable associée
private BillableEntityInterface $billable;

// ID Stripe du client
private string $stripeId;

// Type de méthode de paiement par défaut
private ?string $pmType = null;

// Derniers 4 chiffres de la carte bancaire
private ?string $pmLastFour = null;

// Date de fin de période d'essai
private ?\DateTimeImmutable $trialEndsAt = null;

// Timestamps
private \DateTimeImmutable $createdAt;
private \DateTimeImmutable $updatedAt;

// Relations
private Collection $subscriptions;
```

**Méthodes**:
```php
public function getId(): ?int
public function getBillable(): BillableEntityInterface
public function setBillable(BillableEntityInterface $billable): self
public function getStripeId(): string
public function setStripeId(string $stripeId): self
public function getPmType(): ?string
public function setPmType(?string $pmType): self
public function getPmLastFour(): ?string
public function setPmLastFour(?string $pmLastFour): self
public function getTrialEndsAt(): ?\DateTimeImmutable
public function setTrialEndsAt(?\DateTimeImmutable $trialEndsAt): self
public function getCreatedAt(): \DateTimeImmutable
public function setCreatedAt(\DateTimeImmutable $createdAt): self
public function getUpdatedAt(): \DateTimeImmutable
public function setUpdatedAt(\DateTimeImmutable $updatedAt): self

// Collection de souscriptions
public function getSubscriptions(): Collection<int, Subscription>
public function addSubscription(Subscription $subscription): self
public function removeSubscription(Subscription $subscription): self

// Méthodes de vérification de période d'essai
public function onTrial(): bool
public function onGenericTrial(): bool
```

#### Subscription (`CashierBundle\Entity\Subscription`)

**Namespace**: `CashierBundle\Entity`

**Constantes de statut**:
```php
public const STATUS_ACTIVE = 'active';
public const STATUS_CANCELED = 'canceled';
public const STATUS_INCOMPLETE = 'incomplete';
public const STATUS_INCOMPLETE_EXPIRED = 'incomplete_expired';
public const STATUS_PAST_DUE = 'past_due';
public const STATUS_PAUSED = 'paused';
public const STATUS_TRIALING = 'trialing';
public const STATUS_UNPAID = 'unpaid';
```

**Propriétés**:
```php
// Identifiant unique
private ?int $id = null;

// Client associé
private StripeCustomer $customer;

// Type de souscription
private string $type = 'default';

// ID Stripe de la souscription
private string $stripeId;

// Statut Stripe
private string $stripeStatus;

// Prix Stripe (optionnel)
private ?string $stripePrice = null;

// Quantité (optionnel)
private ?int $quantity = null;

// Dates
private ?\DateTimeImmutable $trialEndsAt = null;
private ?\DateTimeImmutable $endsAt = null;
private \DateTimeImmutable $createdAt;
private \DateTimeImmutable $updatedAt;

// Articles de souscription
private Collection $items;
```

**Méthodes**:
```php
// Getters/Setters standards
public function getId(): ?int
public function getCustomer(): StripeCustomer
public function setCustomer(StripeCustomer $customer): self
public function getType(): string
public function setType(string $type): self
public function getStripeId(): string
public function setStripeId(string $stripeId): self
public function getStripeStatus(): string
public function setStripeStatus(string $stripeStatus): self
public function getStripePrice(): ?string
public function setStripePrice(?string $stripePrice): self
public function getQuantity(): ?int
public function setQuantity(?int $quantity): self
public function getTrialEndsAt(): ?\DateTimeImmutable
public function setTrialEndsAt(?\DateTimeImmutable $trialEndsAt): self
public function getEndsAt(): ?\DateTimeImmutable
public function setEndsAt(?\DateTimeImmutable $endsAt): self
public function getCreatedAt(): \DateTimeImmutable
public function setCreatedAt(\DateTimeImmutable $createdAt): self
public function getUpdatedAt(): \DateTimeImmutable
public function setUpdatedAt(\DateTimeImmutable $updatedAt): self

// Articles de souscription
public function getItems(): Collection<int, SubscriptionItem>
public function addItem(SubscriptionItem $item): self
public function removeItem(SubscriptionItem $item): self

// Méthodes de statut
public function valid(): bool
public function active(): bool
public function onTrial(): bool
public function onGracePeriod(): bool
public function canceled(): bool
public function ended(): bool
public function incomplete(): bool
public function pastDue(): bool
public function incompleteAndExpired(): bool
public function notOnGracePeriod(): bool
public function notOnTrial(): bool
public function recurring(): bool
public function paused(): bool
public function onPausedGracePeriod(): bool
public function notPaused(): bool
public function notPausedOrOnPausedGracePeriod(): bool
```

#### SubscriptionItem (`CashierBundle\Entity\SubscriptionItem`)

**Namespace**: `CashierBundle\Entity`

**Propriétés**:
```php
// Identifiant unique
private ?int $id = null;

// Souscription parente
private Subscription $subscription;

// IDs Stripe
private string $stripeId;
private string $stripeProduct;
private string $stripePrice;

// Quantité
private ?int $quantity = null;

// Metered billing
private ?string $meterId = null;
private ?string $meterEventName = null;

// Timestamps
private \DateTimeImmutable $createdAt;
private \DateTimeImmutable $updatedAt;
```

**Méthodes**:
```php
// Getters/Setters standards
public function getId(): ?int
public function getSubscription(): Subscription
public function setSubscription(Subscription $subscription): self
public function getStripeId(): string
public function setStripeId(string $stripeId): self
public function getStripeProduct(): string
public function setStripeProduct(string $stripeProduct): self
public function getStripePrice(): string
public function setStripePrice(string $stripePrice): self
public function getQuantity(): ?int
public function setQuantity(?int $quantity): self
public function getMeterId(): ?string
public function setMeterId(?string $meterId): self
public function getMeterEventName(): ?string
public function setMeterEventName(?string $meterEventName): self
public function getCreatedAt(): \DateTimeImmutable
public function setCreatedAt(\DateTimeImmutable $createdAt): self
public function getUpdatedAt(): \DateTimeImmutable
public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
```

### 1.2 Traits

#### BillableTrait (`CashierBundle\Concerns\BillableTrait`)

**Namespace**: `CashierBundle\Concerns`

**Utilise tous les traits de gestion**:
```php
trait BillableTrait
{
    use HandlesTaxes;
    use ManagesCustomer;
    use ManagesInvoices;
    use ManagesPaymentMethods;
    use ManagesSubscriptions;
    use PerformsCharges;
}
```

#### HandlesTaxes (`CashierBundle\Concerns\HandlesTaxes`)

**Namespace**: `CashierBundle\Concerns`

**Implémente**: `BillableInterface`

**Méthodes**:
```php
public function taxRates(): array<string>
public function isTaxExempt(): bool
```

#### ManagesCustomer (`CashierBundle\Concerns\ManagesCustomer`)

**Namespace**: `CashierBundle\Concerns`

**Implémente**: `BillableInterface`

**Méthodes**:
```php
public function stripeId(): ?string
public function hasStripeId(): bool
public function createAsStripeCustomer(array $options = []): string
public function updateStripeCustomer(array $options = []): void
public function createOrGetStripeCustomer(array $options = []): string
public function asStripeCustomer(): ?StripeCustomer
```

#### ManagesInvoices (`CashierBundle\Concerns\ManagesInvoices`)

**Namespace**: `CashierBundle\Concerns`

**Implémente**: `BillableInterface`

**Méthodes**:
```php
public function invoices(bool $includePending = false): Collection<int, Invoice>
public function invoice(array $options = []): Invoice
public function upcomingInvoice(): ?Invoice
public function tab(string $description, int $amount, array $options = []): self
public function invoiceFor(string $description, int $amount): Invoice
public function balance(): string
public function creditBalance(int $amount, ?string $description = null): CustomerBalanceTransaction
public function debitBalance(int $amount, ?string $description = null): CustomerBalanceTransaction
```

#### ManagesPaymentMethods (`CashierBundle\Concerns\ManagesPaymentMethods`)

**Namespace**: `CashierBundle\Concerns`

**Implémente**: `BillableInterface`

**Méthodes**:
```php
public function hasDefaultPaymentMethod(): bool
public function defaultPaymentMethod(): ?PaymentMethod
public function addPaymentMethod(string $paymentMethod): PaymentMethod
public function updateDefaultPaymentMethod(string $paymentMethod): PaymentMethod
public function paymentMethods(?string $type = null): Collection<int, PaymentMethod>
```

#### ManagesSubscriptions (`CashierBundle\Concerns\ManagesSubscriptions`)

**Namespace**: `CashierBundle\Concerns`

**Implémente**: `BillableInterface`

**Méthodes**:
```php
public function subscriptions(): Collection<int, Subscription>
public function subscription(string $type = 'default'): ?Subscription
public function subscribed(string $type = 'default', ?string $price = null): bool
public function onTrial(string $type = 'default', ?string $price = null): bool
public function onGenericTrial(): bool
public function newSubscription(string $type, string|array $prices = []): SubscriptionBuilder
```

#### PerformsCharges (`CashierBundle\Concerns\PerformsCharges`)

**Namespace**: `CashierBundle\Concerns`

**Implémente**: `BillableInterface`

**Méthodes**:
```php
public function charge(int $amount, string $paymentMethod, array $options = []): Payment
public function pay(int $amount, array $options = []): Payment
public function refund(string $paymentIntent, array $options = []): Refund
public function checkout(array $items): Checkout
public function checkoutCharge(int $amount, string $name, int $quantity = 1): Checkout
public function billingPortalUrl(?string $returnUrl = null): string
```

## 2. Services

### 2.1 Services Principaux

#### Cashier (`CashierBundle\Service\Cashier`)

**Propriétés statiques**:
```php
public static string $currency = 'usd';
public static string $currencyLocale = 'en';
public static ?string $logger = null;
public static bool $deactivatePastDue = true;
public static bool $deactivateIncomplete = true;
```

**Dépendances injectées**:
- `StripeClient $stripe`
- `SubscriptionRepository $subscriptionRepository`
- `StripeCustomerRepository $customerRepository`

**Méthodes**:
```php
public static function formatAmount(int $amount, ?string $currency = null, ?string $locale = null): string
public static function normalizeZeroAmountDecimal(int $amount): int
public function findSubscription(string $stripeId): ?Subscription
public function findInvoice(string $stripeId): ?Invoice
public function findCustomer(string $stripeId): ?BillableInterface
public function stripe(): StripeClient
public static function paymentIntentOptions(array $options = []): array
```

#### CustomerService (`CashierBundle\Service\CustomerService`)

**Dépendances injectées**:
- `StripeClient $stripe`
- `StripeCustomerRepository $repository`
- `EntityManagerInterface $entityManager`

**Méthodes**:
```php
public function create(BillableInterface $billable, array $options = []): string
public function update(BillableInterface $billable, array $options = []): void
public function find(string $stripeId): ?LocalStripeCustomer
public function createOrGetStripeId(BillableInterface $billable, array $options = []): string
public function sync(StripeCustomer $stripeCustomer, ?BillableInterface $billable = null): void
public function getStripePayload(BillableInterface $billable): array<string, mixed>
```

### 2.2 Autres Services

*(À compléter avec les autres services)*

## 3. Événements

### 3.1 Webhook Events

#### WebhookReceivedEvent (`CashierBundle\Event\WebhookReceivedEvent`)

**Namespace**: `CashierBundle\Event`

**Propriétés**:
```php
private readonly array $payload;
private readonly string $signature;
```

#### WebhookHandledEvent (`CashierBundle\Event\WebhookHandledEvent`)

**Namespace**: `CashierBundle\Event`

**Propriétés**:
```php
private readonly array $payload;
private readonly string $type;
private readonly mixed $response;
```

### 3.2 Payment Events

#### PaymentSucceededEvent (`CashierBundle\Event\PaymentSucceededEvent`)

**Namespace**: `CashierBundle\Event`

**Propriétés**:
```php
private readonly array $payload;
private readonly string $paymentIntentId;
```

#### PaymentFailedEvent (`CashierBundle\Event\PaymentFailedEvent`)

**Namespace**: `CashierBundle\Event`

**Propriétés**:
```php
private readonly array $payload;
private readonly string $paymentIntentId;
```

### 3.3 Subscription Events

#### SubscriptionCreatedEvent (`CashierBundle\Event\SubscriptionCreatedEvent`)

**Namespace**: `CashierBundle\Event`

**Propriétés**:
```php
private readonly array $payload;
private readonly string $subscriptionId;
```

#### SubscriptionUpdatedEvent (`CashierBundle\Event\SubscriptionUpdatedEvent`)

**Namespace**: `CashierBundle\Event`

**Propriétés**:
```php
private readonly array $payload;
private readonly string $subscriptionId;
```

#### SubscriptionDeletedEvent (`CashierBundle\Event\SubscriptionDeletedEvent`)

**Namespace**: `CashierBundle\Event`

**Propriétés**:
```php
private readonly array $payload;
private readonly string $subscriptionId;
```

## 4. Exceptions

### 4.1 Exceptions Client

#### CustomerAlreadyCreatedException (`CashierBundle\Exception\CustomerAlreadyCreatedException`)

**Message**: "Customer with Stripe ID {$stripeId} already exists."

Méthodes:
```php
public static function create(string $stripeId): self
```

#### InvalidCustomerException (`CashierBundle\Exception\InvalidCustomerException`)

Messages:
- "Customer has not been created in Stripe yet."
- "Invalid customer ID: {$stripeId}"

Méthodes:
```php
public static function notYetCreated(): self
public static function invalidId(string $stripeId): self
```

#### InvalidPaymentMethodException (`CashierBundle\Exception\InvalidPaymentMethodException`)

**Message**: "Invalid payment method: {$paymentMethodId}"

Méthodes:
```php
public static function invalid(string $paymentMethodId): self
```

#### InvalidCouponException (`CashierBundle\Exception\InvalidCouponException`)

**Message**: "Invalid coupon: {$couponId}"

Méthodes:
```php
public static function invalid(string $couponId): self
```

### 4.2 Exceptions Transactionnelles

#### IncompletePaymentException (`CashierBundle\Exception\IncompletePaymentException`)

*(À compléter)*

#### InvalidInvoiceException (`CashierBundle\Exception\InvalidInvoiceException`)

*(À compléter)*

#### SubscriptionUpdateFailureException (`CashierBundle\Exception\SubscriptionUpdateFailureException`)

*(À compléter)*

### 4.3 Exceptions Balance

#### InvalidCustomerBalanceTransactionException (`CashierBundle\Exception\InvalidCustomerBalanceTransactionException`)

*(À compléter)*

## 5. Configuration

### 5.1 Configuration Principale (`cashier.yaml`)

**Fichier**: `src/Infrastructure/config/packages/cashier.yaml`

#### Options disponibles:

```yaml
cashier:
    # Configuration monnaie
    currency: '%env(default:usd:CASHIER_CURRENCY)%'          # Devise par défaut (usd)
    currency_locale: '%env(default:en:CASHIER_CURRENCY_LOCALE)%' # Locale pour le formatage

    # Configuration des factures
    invoice:
        paper: '%env(default:letter:CASHIER_INVOICE_PAPER)'           # Format papier (letter)
        remote_enabled: '%env(default:false:CASHIER_INVOICE_REMOTE_ENABLED)'    # Activer assets distants
        orientation: '%env(default:portrait:CASHIER_INVOICE_ORIENTATION)'     # Orientation (portrait)

    # Configuration automatique des taxes
    automatic_tax:
        enabled: '%env(default:false:CASHIER_AUTOMATIC_TAX)'          # Activer les taxes automatiques

    # Comportement des souscriptions
    subscription:
        deactivate_past_due: true          # Désactiver les souscriptions en retard
        deactivate_incomplete: true       # Désactiver les souscriptions incomplètes
```

### 5.2 Configuration Développement (`dev/cashier.yaml`)

**Fichier**: `src/Infrastructure/config/packages/dev/cashier.yaml`

#### Spécificités développement:

```yaml
cashier:
    # Utiliser le mode test
    currency: 'usd'
    currency_locale: 'en'

    # Activer les features de debug
    invoice:
        remote_enabled: true      # Permet de charger des assets distants dans les PDFs

    # Configuration des taxes en dev
    automatic_tax:
        enabled: false

    # Ne pas désactiver les souscriptions en retard/incomplètes
    subscription:
        deactivate_past_due: false
        deactivate_incomplete: false
```

### 5.3 Services Webhook

**Fichier**: `src/Resources/config/webhook_services.php`

#### Services configurés:

**Handlers de Souscriptions**:
- `SubscriptionCreatedHandler` - Gère la création de souscription
- `SubscriptionUpdatedHandler` - Gère la mise à jour de souscription
- `SubscriptionDeletedHandler` - Gère la suppression de souscription

**Handlers Client**:
- `CustomerUpdatedHandler` - Gère la mise à jour client
- `CustomerDeletedHandler` - Gère la suppression client

**Handlers Méthode de Paiement**:
- `PaymentMethodUpdatedHandler` - Gère la mise à jour méthode de paiement

**Handlers Facture**:
- `InvoicePaidHandler` - Gère le paiement de facture
- `InvoicePaymentFailedHandler` - Gère l'échec de paiement
- `InvoicePaymentActionRequiredHandler` - Gère les actions requises

**Handlers Checkout**:
- `CheckoutSessionCompletedHandler` - Gère la session checkout terminée

### 5.4 Configuration des Routes

**Fichiers**:
- `src/Resources/config/routes.yaml` - Configuration principale
- `src/Resources/config/routes/webhook.yaml` - Route webhook spécifique

**Routes définies**:
```yaml
# Route principale webhook
cashier_webhook_stripe:
    path: '%cashier.path%/webhook'  # Parfait: /cashier/webhook
    methods: [POST]
    controller: CashierBundle\Controller\WebhookController::handle

# Route webhook alternative
cashier_webhook:
    path: /cashier/webhook
    methods: [POST]
    controller: CashierBundle\Controller\WebhookController::handle

# Routes du PaymentController (dans le controller)
# - cashier_payment_show    GET  /cashier/payment/{paymentIntentId}
# - cashier_payment_confirm POST /cashier/payment/{paymentIntentId}/confirm
```

### 5.5 Configuration des Services Symfony

**Fichier**: `src/Resources/config/services.yaml`

#### Dépendances configurées:

```yaml
# Client Stripe
Stripe\StripeClient:
    arguments:
        $apiKey: '%cashier.secret%'
    public: false

# Controllers
CashierBundle\Controller\:
    resource: '../../Controller/'
    tags: ['controller.service_arguments']

# Webhook handlers
CashierBundle\Webhook\:
    resource: '../../Webhook/'
    exclude: '../../Webhook/{WebhookController}'
    tags: ['cashier.webhook_handler']

# Extensions
CashierBundle\Twig\CashierExtension:
    tags: ['twig.extension']

# Aliases
CashierBundle\Service\Invoice\InvoiceRendererInterface:
    alias: '%cashier.invoices.renderer%'

CashierBundle\Controller\PaymentController:
    arguments:
        $stripePublishableKey: '%cashier.key%'
```

## 6. Controllers et Routes

### 6.1 PaymentController

**Namespace**: `CashierBundle\Controller`

**Route de base**: `#[Route('/cashier/payment')]`

#### Routes et Méthodes:

**show** (`GET /{paymentIntentId}`)
```php
#[Route('/{paymentIntentId}', name: 'cashier_payment_show', methods: ['GET'])]
public function show(Request $request, string $paymentIntentId): Response
```
- Affiche la page de confirmation de paiement pour les paiements SCA
- Récupère le PaymentIntent via Stripe API
- Rend le template `@Cashier/payment/show.html.twig`
- Paramètres: `paymentIntentId`, `return_url`

**confirm** (`POST /{paymentIntentId}/confirm`)
```php
#[Route('/{paymentIntentId}/confirm', name: 'cashier_payment_confirm', methods: ['POST'])]
public function confirm(Request $request, string $paymentIntentId): Response
```
- Gère la confirmation du paiement
- Confirme le PaymentIntent via Stripe
- Redirige vers le return_url en cas de succès
- Ajoute des messages flash pour le feedback utilisateur

**handleIncompletePayment** (méthode publique)
```php
public function handleIncompletePayment(IncompletePaymentException $exception): Response
```
- Gère les exceptions de paiement incomplet
- Redirige selon le type d'action requis (SCA, méthode de paiement, confirmation)

**Dépendances injectées**:
- `StripeClient $stripeClient`
- `string $stripePublishableKey`

### 6.2 WebhookController

**Namespace**: `CashierBundle\Controller`

**Route de base**:
- Configurée via YAML: `/cashier/webhook` (POST)

#### Méthodes:

**handle** (`POST /cashier/webhook`)
```php
public function handle(Request $request): Response
```
- Méthode unique pour traiter tous les webhooks Stripe
- Extrait le payload et la signature du header `Stripe-Signature`
- Utilise `WebhookProcessor` pour traiter le webhook
- Retourne HTTP 200 en cas de succès, HTTP 400 en cas d'erreur

**Dépendances injectées**:
- `WebhookProcessor $processor`

## 7. Messages et Handlers

### 7.1 Messages

#### CancelSubscriptionMessage (`CashierBundle\Message\CancelSubscriptionMessage`)

*(À compléter)*

#### ProcessInvoiceMessage (`CashierBundle\Message\ProcessInvoiceMessage`)

*(À compléter)*

#### UpdateSubscriptionQuantityMessage (`CashierBundle\Message\UpdateSubscriptionQuantityMessage`)

*(À compléter)*

#### SyncCustomerDetailsMessage (`CashierBundle\Message\SyncCustomerDetailsMessage`)

*(À compléter)*

#### RetryPaymentMessage (`CashierBundle\Message\RetryPaymentMessage`)

*(À compléter)*

### 7.2 Handlers

#### CancelSubscriptionHandler (`CashierBundle\MessageHandler\CancelSubscriptionHandler`)

*(À compléter)*

#### ProcessInvoiceHandler (`CashierBundle\MessageHandler\ProcessInvoiceHandler`)

*(À compléter)*

#### UpdateSubscriptionQuantityHandler (`CashierBundle\MessageHandler\UpdateSubscriptionQuantityHandler`)

*(À compléter)*

#### SyncCustomerDetailsHandler (`CashierBundle\MessageHandler\SyncCustomerDetailsHandler`)

*(À compléter)*

#### RetryPaymentHandler (`CashierBundle\MessageHandler\RetryPaymentHandler`)

*(À compléter)*

## 8. Webhook Handlers

### 8.1 PaymentMethodUpdatedHandler (`CashierBundle\Webhook\Handler\PaymentMethodUpdatedHandler`)

*(À compléter)*

### 8.2 InvoicePaymentFailedHandler (`CashierBundle\Webhook\Handler\InvoicePaymentFailedHandler`)

*(À compléter)*

### 8.3 CustomerUpdatedHandler (`CashierBundle\Webhook\Handler\CustomerUpdatedHandler`)

*(À compléter)*

## 9. Interfaces Contractuelles

### 9.1 BillableInterface (`CashierBundle\Contract\BillableInterface`)

**Namespace**: `CashierBundle\Contract`

**Méthodes**:
```php
// Customer management
public function stripeId(): ?string
public function hasStripeId(): bool
public function createAsStripeCustomer(array $options = []): string
public function updateStripeCustomer(array $options = []): void
public function createOrGetStripeCustomer(array $options = []): string
public function asStripeCustomer(): ?StripeCustomer

// Subscriptions
public function subscriptions(): Collection
public function subscription(string $type = 'default'): ?Subscription
public function subscribed(string $type = 'default', ?string $price = null): bool
public function onTrial(string $type = 'default', ?string $price = null): bool
public function onGenericTrial(): bool
public function newSubscription(string $type, string|array $prices = []): SubscriptionBuilder

// Payment Methods
public function hasDefaultPaymentMethod(): bool
public function defaultPaymentMethod(): ?PaymentMethod
public function addPaymentMethod(string $paymentMethod): PaymentMethod
public function updateDefaultPaymentMethod(string $paymentMethod): PaymentMethod
public function paymentMethods(?string $type = null): Collection

// Charges
public function charge(int $amount, string $paymentMethod, array $options = []): Payment
public function pay(int $amount, array $options = []): Payment
public function refund(string $paymentIntent, array $options = []): Refund

// Invoices
public function invoices(bool $includePending = false): Collection
public function invoice(array $options = []): Invoice
public function upcomingInvoice(): ?Invoice
public function tab(string $description, int $amount, array $options = []): self
public function invoiceFor(string $description, int $amount): Invoice

// Checkout
public function checkout(array $items): Checkout
public function checkoutCharge(int $amount, string $name, int $quantity = 1): Checkout

// Balance
public function balance(): string
public function creditBalance(int $amount, ?string $description = null): CustomerBalanceTransaction
public function debitBalance(int $amount, ?string $description = null): CustomerBalanceTransaction

// Billing Portal
public function billingPortalUrl(?string $returnUrl = null): string

// Tax
public function taxRates(): array
public function isTaxExempt(): bool
```

### 9.2 BillableEntityInterface (`CashierBundle\Contract\BillableEntityInterface`)

**Namespace**: `CashierBundle\Contract`

**Hérite de**: `BillableInterface`

**Méthodes additionnelles**:
```php
public function getId(): ?int
public function getEmail(): string
public function getName(): ?string
```

## 10. Modèles

### 10.1 Checkout (`CashierBundle\Model\Checkout`)

**Namespace**: `CashierBundle\Model`

**Constructeur**:
```php
public function __construct(
    private readonly object $session
)
```

**Propriétés**:
```php
private readonly object $session
```

**Méthodes**:
```php
public function id(): string
public function url(): ?string
public function paymentIntentId(): ?string
public function setupIntentId(): ?string
public function customerId(): ?string
public function subscriptionId(): ?string
public function status(): string
public function isComplete(): bool
public function isExpired(): bool
public function isOpen(): bool
public function asStripeCheckoutSession(): object
```

### 10.2 Coupon (`CashierBundle\Model\Coupon`)

**Namespace**: `CashierBundle\Model`

**Constructeur**:
```php
public function __construct(
    private readonly object $coupon
)
```

**Propriétés**:
```php
private readonly object $coupon
```

**Méthodes**:
```php
public function id(): string
public function name(): ?string
public function percentOff(): ?float
public function amountOff(): ?int
public function currency(): ?string
public function duration(): string
public function durationInMonths(): ?int
public function valid(): bool
public function isPercentage(): bool
public function isFixedAmount(): bool
```

### 10.3 CustomerBalanceTransaction (`CashierBundle\Model\CustomerBalanceTransaction`)

**Namespace**: `CashierBundle\Model`

**Constructeur**:
```php
public function __construct(
    private readonly StripeCustomerBalanceTransaction $transaction
)
```

**Propriétés**:
```php
private readonly StripeCustomerBalanceTransaction $transaction
```

**Méthodes**:
```php
public function id(): string
public function amount(): string
public function rawAmount(): int
public function currency(): string
public function type(): string
public function description(): ?string
public function isCredit(): bool
public function isDebit(): bool
```

### 10.4 Invoice (`CashierBundle\Model\Invoice`)

**Namespace**: `CashierBundle\Model`

**Constructeur**:
```php
public function __construct(
    private readonly StripeInvoice $invoice,
    private readonly InvoiceRendererInterface $renderer
)
```

**Propriétés**:
```php
private readonly StripeInvoice $invoice
private readonly InvoiceRendererInterface $renderer
```

**Méthodes**:
```php
public function id(): string
public function date(): \DateTimeImmutable
public function dueDate(): ?\DateTimeImmutable
public function total(): string
public function rawTotal(): int
public function subtotal(): string
public function tax(): string
public function currency(): string
public function items(): array<InvoiceLineItem>
public function taxes(): array<Tax>
public function payments(): array<InvoicePayment>
public function discounts(): array<Discount>
public function download(array $data = []): Response
public function pay(): Payment
public function asStripeInvoice(): StripeInvoice
```

### 10.5 Payment (`CashierBundle\Model\Payment`)

**Namespace**: `CashierBundle\Model`

**Constructeur**:
```php
public function __construct(
    private readonly object $paymentIntent
)
```

**Propriétés**:
```php
private readonly object $paymentIntent
```

**Méthodes**:
```php
public function id(): string
public function amount(): string
public function rawAmount(): int
public function currency(): string
public function clientSecret(): ?string
public function status(): string
public function capture(): self
public function cancel(): self
public function requiresPaymentMethod(): bool
public function requiresAction(): bool
public function requiresConfirmation(): bool
public function requiresCapture(): bool
public function isCanceled(): bool
public function isSucceeded(): bool
public function isProcessing(): bool
public function asStripePaymentIntent(): object
```

### 10.6 PaymentMethod (`CashierBundle\Model\PaymentMethod`)

**Namespace**: `CashierBundle\Model`

**Constructeur**:
```php
public function __construct(
    private readonly StripePaymentMethod $paymentMethod
)
```

**Propriétés**:
```php
private readonly StripePaymentMethod $paymentMethod
```

**Méthodes**:
```php
public function id(): string
public function type(): string
public function isDefault(): bool
public function brand(): ?string
public function lastFour(): ?string
public function expiryMonth(): ?int
public function expiryYear(): ?int
public function bank(): ?string
public function asStripePaymentMethod(): StripePaymentMethod
```

### 10.7 Autres Modèles

#### InvoiceLineItem (`CashierBundle\Model\InvoiceLineItem`)
*(À compléter)*

#### InvoicePayment (`CashierBundle\Model\InvoicePayment`)
*(À compléter)*

#### Discount (`CashierBundle\Model\Discount`)
*(À compléter)*

#### PromotionCode (`CashierBundle\Model\PromotionCode`)
*(À compléter)*

#### Tax (`CashierBundle\Model\Tax`)
*(À compléter)*

#### TaxRate (`CashierBundle\Model\TaxRate`)
*(À compléter)*

#### SetupIntent (`CashierBundle\Model\SetupIntent`)
*(À compléter)*

## 11. Repositories

### 11.1 StripeCustomerRepository (`CashierBundle\Repository\StripeCustomerRepository`)

*(À compléter avec les méthodes)*

### 11.2 SubscriptionRepository (`CashierBundle\Repository\SubscriptionRepository`)

*(À compléter avec les méthodes)*

### 11.3 SubscriptionItemRepository (`CashierBundle\Repository\SubscriptionItemRepository`)

*(À compléter avec les méthodes)*

## 12. Commands

### 12.1 CleanupExpiredSessionsCommand (`CashierBundle\Command\CleanupExpiredSessionsCommand`)

*(À compléter)*

### 12.2 ReportUsageCommand (`CashierBundle\Command\ReportUsageCommand`)

*(À compléter)*

### 12.3 WebhookCommand (`CashierBundle\Command\WebhookCommand`)

*(À compléter)*

## 13. Divers

### 13.1 Notification ConfirmPaymentNotification

**Namespace**: `CashierBundle\Notification`

*(À compléter)*

### 13.2 Logger StripeLogger

**Namespace**: `CashierBundle\Logger`

*(À compléter)*

### 13.3 Twig Extension CashierExtension

**Namespace**: `CashierBundle\Twig`

*(À compléter)*