# CashierBundle - Récapitulatif Complet

## 📦 Vue d'ensemble

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         CashierBundle                                    │
│                   Stripe Billing for Symfony 8.X                         │
├─────────────────────────────────────────────────────────────────────────┤
│  📁 103 fichiers PHP  │  ✅ 78 tests  │  📚 5 pages de documentation    │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 🏗️ Architecture

```
CashierBundle/
├── 📂 Contract/              # Interfaces
│   ├── BillableInterface.php
│   ├── BillableEntityInterface.php
│   ├── InvoiceRendererInterface.php
│   └── WebhookHandlerInterface.php
│
├── 📂 Entity/                # Doctrine Entities
│   ├── Subscription.php
│   ├── SubscriptionItem.php
│   └── StripeCustomer.php
│
├── 📂 Model/                 # Value Objects
│   ├── Payment.php
│   ├── Checkout.php
│   ├── Coupon.php
│   ├── Discount.php
│   ├── Invoice.php
│   └── ... (13 modèles)
│
├── 📂 Service/               # Services métier
│   ├── Cashier.php
│   ├── SubscriptionBuilder.php
│   ├── CheckoutService.php
│   ├── InvoiceService.php
│   ├── PaymentService.php
│   └── InvoiceRenderer/
│
├── 📂 Webhook/               # Webhooks Stripe
│   ├── WebhookProcessor.php
│   └── Handler/ (10 handlers)
│
├── 📂 Event/                 # Symfony Events
│   ├── SubscriptionCreatedEvent.php
│   ├── PaymentSucceededEvent.php
│   └── ... (7 events)
│
├── 📂 Controller/            # Controllers
│   ├── WebhookController.php
│   └── PaymentController.php
│
├── 📂 Command/               # CLI Commands
│   ├── WebhookCommand.php
│   ├── ReportUsageCommand.php
│   └── CleanupExpiredSessionsCommand.php
│
├── 📂 Message/               # Async Messages
│   └── SyncCustomerDetailsMessage.php
│
└── 📂 Exception/             # Exceptions
    └── (8 exceptions)
```

---

## 🔄 Flux de Données

### 1. Création d'abonnement

```
┌──────────┐    ┌─────────────────────┐    ┌─────────────┐    ┌──────────┐
│   User   │───▶│  SubscriptionBuilder │───▶│   Stripe    │───▶│ Webhook  │
│ (Client) │    │    (Service)         │    │    API      │    │ Handler  │
└──────────┘    └─────────────────────┘    └─────────────┘    └────┬─────┘
     │                    │                      │                   │
     │                    │                      │                   ▼
     │                    │                      │           ┌──────────────┐
     │                    │                      │           │ Subscription │
     │                    │                      │           │   Created    │
     │                    │                      │           │   (Entity)   │
     │                    │                      │           └──────────────┘
     │                    │                      │                   │
     │                    │                      │                   ▼
     │                    │                      │           ┌──────────────┐
     │                    │                      │           │ Symfony Event│
     │                    │                      │           │   Dispatched │
     │                    │                      │           └──────────────┘
     ▼                    ▼                      ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                           Database (PostgreSQL)                           │
└──────────────────────────────────────────────────────────────────────────┘
```

### 2. Webhook Processing

```
┌─────────────┐         ┌────────────────────┐         ┌─────────────────┐
│   Stripe    │  POST   │  WebhookController │ verify  │   Middleware    │
 │   Events    │────────▶│   /stripe/webhook  │────────▶│   Signature     │
└─────────────┘         └────────────────────┘         └─────────────────┘
                               │                                │
                               ▼                                ▼
                        ┌────────────────────┐         ┌─────────────────┐
                        │ WebhookProcessor   │ dispatch│  Event System   │
                        │   (Service)        │────────▶│   (Symfony)     │
                        └────────────────────┘         └─────────────────┘
                               │
          ┌────────────────────┼────────────────────┐
          ▼                    ▼                    ▼
   ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
   │ Subscription│     │  Customer   │     │   Invoice   │
   │  Handlers   │     │  Handlers   │     │  Handlers   │
   └─────────────┘     └─────────────┘     └─────────────┘
```

---

## 🃏 Cartes des Services

### Services Principaux

```
┌─────────────────────────────────────────────────────────────────┐
│  💳 Cashier                                                      │
│  ──────────────────────────────────────────────────────────────  │
│  Service principal - Formatage montants, configuration          │
│                                                                  │
│  • formatAmount(int $amount, ?string $currency): string         │
│  • formatCurrency(int $amount, string $currency): string        │
│  • findSubscription(string $stripeId): ?Subscription            │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  🔄 SubscriptionBuilder                                          │
│  ──────────────────────────────────────────────────────────────  │
│  Constructeur fluide d'abonnements                               │
│                                                                  │
│  • price(string $price, int $quantity = 1): self                │
│  • trialDays(int $days): self                                   │
│  • withCoupon(?string $couponId): self                          │
│  • create(PaymentMethod|string|null $pm): Subscription          │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  🛒 CheckoutService                                              │
│  ──────────────────────────────────────────────────────────────  │
│  Stripe Checkout Sessions                                        │
│                                                                  │
│  • create(array $items): Checkout                               │
│  • charge(int $amount, string $name): Checkout                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  📄 InvoiceService                                               │
│  ──────────────────────────────────────────────────────────────  │
│  Gestion des factures                                            │
│                                                                  │
│  • create(BillableInterface $billable): Invoice                 │
│  • download(Invoice $invoice): Response                         │
│  • upcoming(BillableInterface $billable): ?Invoice              │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  💰 PaymentService                                               │
│  ──────────────────────────────────────────────────────────────  │
│  Traitement des paiements                                        │
│                                                                  │
│  • charge(BillableInterface $billable, int $amount): Payment    │
│  • refund(string $paymentIntent): Refund                        │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🃏 Cartes des Entités

```
┌─────────────────────────────────────────────────────────────────┐
│  👤 StripeCustomer                                               │
│  ──────────────────────────────────────────────────────────────  │
│  Client Stripe lié à l'utilisateur                               │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ id: int                                                  │    │
│  │ user: User (1:1)                                        │    │
│  │ stripeId: string                                        │    │
│  │ pmType: ?string (card, sepa_debit...)                   │    │
│  │ pmLastFour: ?string                                     │    │
│  │ trialEndsAt: ?DateTimeImmutable                         │    │
│  │ subscriptions: Collection (1:N)                         │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  📋 Subscription                                                 │
│  ──────────────────────────────────────────────────────────────  │
│  Abonnement Stripe                                               │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ id: int                                                  │    │
│  │ customer: StripeCustomer (N:1)                          │    │
│  │ type: string (default, pro, enterprise...)              │    │
│  │ stripeId: string                                        │    │
│  │ stripeStatus: string (active, canceled, past_due...)    │    │
│  │ stripePrice: ?string                                    │    │
│  │ quantity: ?int                                          │    │
│  │ trialEndsAt: ?DateTimeImmutable                         │    │
│  │ endsAt: ?DateTimeImmutable                              │    │
│  │ items: Collection (1:N)                                 │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  Méthodes de statut:                                             │
│  ✅ active()  ✅ onTrial()  ✅ canceled()  ✅ ended()           │
│  ✅ valid()   ✅ pastDue()  ✅ paused()    ✅ recurring()       │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  📦 SubscriptionItem                                             │
│  ──────────────────────────────────────────────────────────────  │
│  Item d'abonnement (multi-plan)                                  │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ id: int                                                  │    │
│  │ subscription: Subscription (N:1)                        │    │
│  │ stripeId: string                                        │    │
│  │ stripeProduct: string                                   │    │
│  │ stripePrice: string                                     │    │
│  │ quantity: ?int                                          │    │
│  │ meterId: ?string (metered billing)                      │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🃏 Cartes des Webhooks

```
┌─────────────────────────────────────────────────────────────────┐
│                    📡 WEBHOOK HANDLERS                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────┐  ┌─────────────────────────┐       │
│  │ 🆕 SubscriptionCreated  │  │ 🔄 SubscriptionUpdated  │       │
│  │ customer.subscription.  │  │ customer.subscription.  │       │
│  │       created           │  │       updated           │       │
│  └─────────────────────────┘  └─────────────────────────┘       │
│                                                                  │
│  ┌─────────────────────────┐  ┌─────────────────────────┐       │
│  │ ❌ SubscriptionDeleted  │  │ 👤 CustomerUpdated      │       │
│  │ customer.subscription.  │  │    customer.updated     │       │
│  │       deleted           │  │                         │       │
│  └─────────────────────────┘  └─────────────────────────┘       │
│                                                                  │
│  ┌─────────────────────────┐  ┌─────────────────────────┐       │
│  │ 💳 PaymentMethodUpdated │  │ ⚠️ InvoicePaymentAction │       │
│  │ payment_method.         │  │ invoice.payment_action_ │       │
│  │ automatically_updated   │  │       required          │       │
│  └─────────────────────────┘  └─────────────────────────┘       │
│                                                                  │
│  ┌─────────────────────────┐  ┌─────────────────────────┐       │
│  │ 💰 InvoicePaid          │  │ ❌ InvoicePaymentFailed │       │
│  │    invoice.paid         │  │ invoice.payment_failed  │       │
│  └─────────────────────────┘  └─────────────────────────┘       │
│                                                                  │
│  ┌─────────────────────────┐                                    │
│  │ ✅ CheckoutSessionComp. │                                    │
│  │ checkout.session.       │                                    │
│  │       completed         │                                    │
│  └─────────────────────────┘                                    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🃏 Cartes des Events Symfony

```
┌─────────────────────────────────────────────────────────────────┐
│                    🎭 SYMFONY EVENTS                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ 📥 WebhookReceivedEvent                                  │    │
│  │ Déclenché AVANT le traitement du webhook                 │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ 📤 WebhookHandledEvent                                   │    │
│  │ Déclenché APRÈS le traitement du webhook                 │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌───────────────────────┐  ┌───────────────────────┐           │
│  │ 🆕 SubscriptionCreated│  │ 🔄 SubscriptionUpdated│           │
│  └───────────────────────┘  └───────────────────────┘           │
│                                                                  │
│  ┌───────────────────────┐  ┌───────────────────────┐           │
│  │ ❌ SubscriptionDeleted│  │ 💰 PaymentSucceeded   │           │
│  └───────────────────────┘  └───────────────────────┘           │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ ❌ PaymentFailedEvent                                    │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🃏 Cartes des Commands CLI

```
┌─────────────────────────────────────────────────────────────────┐
│                      ⌨️ CLI COMMANDS                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ 🔗 cashier:webhook                                       │    │
│  │ ─────────────────────────────────────────────────────── │    │
│  │ Crée un endpoint webhook dans Stripe                     │    │
│  │                                                          │    │
│  │ $ php bin/console cashier:webhook \                      │    │
│  │     --url=https://domain.com/stripe/webhook              │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ 📊 cashier:report-usage                                  │    │
│  │ ─────────────────────────────────────────────────────── │    │
│  │ Reporte l'utilisation (metered billing)                  │    │
│  │                                                          │    │
│  │ $ php bin/console cashier:report-usage \                 │    │
│  │     <subscription-item-id> <quantity>                    │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ 🧹 cashier:cleanup-sessions                              │    │
│  │ ─────────────────────────────────────────────────────── │    │
│  │ Nettoie les sessions checkout expirées                   │    │
│  │                                                          │    │
│  │ $ php bin/console cashier:cleanup-sessions --hours=24    │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🃏 Cartes des Value Objects

```
┌─────────────────────────────────────────────────────────────────┐
│                      📦 VALUE OBJECTS                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │ 💳 Payment      │  │ 🛒 Checkout     │  │ 🎟️ Coupon      │  │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────────┤  │
│  │ id()            │  │ id()            │  │ id()            │  │
│  │ amount()        │  │ url()           │  │ percentOff()    │  │
│  │ currency()      │  │ status()        │  │ amountOff()     │  │
│  │ status()        │  │ isComplete()    │  │ duration()      │  │
│  │ isSucceeded()   │  │ isExpired()     │  │ isPercentage()  │  │
│  │ requiresAction()│  │ isOpen()        │  │ isFixedAmount() │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  │
│                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │ 📄 Invoice      │  │ 💰 Discount     │  │ 🏷️ PromotionCode│  │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────────┤  │
│  │ total()         │  │ coupon()        │  │ id()            │  │
│  │ subtotal()      │  │ amount()        │  │ code()          │  │
│  │ tax()           │  │ isPercentage()  │  │ coupon()        │  │
│  │ items()         │  │ isFixedAmount() │  │ active()        │  │
│  │ download()      │  └─────────────────┘  └─────────────────┘  │
│  └─────────────────┘                                             │
│                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │ 💵 Tax          │  │ 📊 TaxRate      │  │ 🔄 SetupIntent  │  │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────────┤  │
│  │ amount()        │  │ percentage()    │  │ id()            │  │
│  │ inclusive()     │  │ displayName()   │  │ clientSecret()  │  │
│  │ country()       │  │ inclusive()     │  │ status()        │  │
│  │ state()         │  │ active()        │  │ isSucceeded()   │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🃏 Cartes des Exceptions

```
┌─────────────────────────────────────────────────────────────────┐
│                      ⚠️ EXCEPTIONS                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────┐  ┌─────────────────────────┐       │
│  │ 🔄 CustomerAlready      │  │ 🎟️ InvalidCoupon        │       │
│  │    CreatedException     │  │    Exception            │       │
│  │ Client déjà dans Stripe │  │ Coupon invalide/expiré  │       │
│  └─────────────────────────┘  └─────────────────────────┘       │
│                                                                  │
│  ┌─────────────────────────┐  ┌─────────────────────────┐       │
│  │ 👤 InvalidCustomer      │  │ 📄 InvalidInvoice       │       │
│  │    Exception            │  │    Exception            │       │
│  │ Client non créé/invalid │  │ Facture invalide        │       │
│  └─────────────────────────┘  └─────────────────────────┘       │
│                                                                  │
│  ┌─────────────────────────┐  ┌─────────────────────────┐       │
│  │ 💳 InvalidPaymentMethod │  │ 🔄 SubscriptionUpdate   │       │
│  │    Exception            │  │    FailureException     │       │
│  │ Méthode paiement inval  │  │ Échec mise à jour abo   │       │
│  └─────────────────────────┘  └─────────────────────────┘       │
│                                                                  │
│  ┌─────────────────────────┐  ┌─────────────────────────┐       │
│  │ 💰 InvalidCustomer      │  │ ⚠️ IncompletePayment     │       │
│  │ BalanceTransactionExc.  │  │    Exception            │       │
│  │ Transaction solde error │  │ Paiement nécessite action│       │
│  └─────────────────────────┘  └─────────────────────────┘       │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔗 Relations Doctrine

```
┌──────────────────┐       1:1        ┌──────────────────┐
│      User        │─────────────────▶│  StripeCustomer  │
│  (Your Entity)   │                  │                  │
└──────────────────┘                  └────────┬─────────┘
                                               │
                                               │ 1:N
                                               ▼
                                      ┌──────────────────┐
                                      │   Subscription   │
                                      │                  │
                                      └────────┬─────────┘
                                               │
                                               │ 1:N
                                               ▼
                                      ┌──────────────────┐
                                      │ SubscriptionItem │
                                      │                  │
                                      └──────────────────┘
```

---

## 📊 Statistiques

```
┌─────────────────────────────────────────────────────────────────┐
│                      📊 STATISTIQUES                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│   📁 Fichiers PHP          │   103 fichiers                     │
│  ─────────────────────────────────────────────────────────────  │
│   ✅ Tests                 │   78 tests, 91 assertions          │
│  ─────────────────────────────────────────────────────────────  │
│   📂 Répertoires           │   15 dossiers                      │
│  ─────────────────────────────────────────────────────────────  │
│   🔌 Services              │   11 services                      │
│  ─────────────────────────────────────────────────────────────  │
│   📡 Webhook Handlers      │   10 handlers                      │
│  ─────────────────────────────────────────────────────────────  │
│   🎭 Symfony Events        │   7 events                         │
│  ─────────────────────────────────────────────────────────────  │
│   ⌨️ CLI Commands          │   3 commands                       │
│  ─────────────────────────────────────────────────────────────  │
│   📦 Value Objects         │   13 modèles                       │
│  ─────────────────────────────────────────────────────────────  │
│   ⚠️ Exceptions            │   8 exceptions                     │
│  ─────────────────────────────────────────────────────────────  │
│   📚 Pages Documentation   │   5 pages                          │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🚀 Quick Start

```php
// 1. Créer un abonnement
$subscription = $user->newSubscription('default', 'price_premium')
    ->trialDays(14)
    ->withCoupon('SUMMER2024')
    ->create($paymentMethodId);

// 2. Vérifier le statut
if ($user->subscribed('default')) {
    // Utilisateur abonné
}

// 3. Charger un paiement ponctuel
$payment = $user->charge(2000, $paymentMethodId); // €20.00

// 4. Générer une facture
$invoice = $user->invoice();
$pdf = $invoice->download();

// 5. Portal client
$url = $user->billingPortalUrl(returnUrl: '/account');
```

---

## 📚 Documentation

| Page | URL |
|------|-----|
| 🏠 Accueil | `/` |
| 📦 Installation | `/docs/installation` |
| 🔄 Abonnements | `/docs/subscriptions` |
| 📡 Webhooks | `/docs/webhooks` |

---

**MIT License** | Inspired by [Laravel Cashier](https://github.com/laravel/cashier-stripe)
