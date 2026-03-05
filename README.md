# CashierBundle

[![CI](https://github.com/MakFly/stripe-cashier-bundle/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/MakFly/stripe-cashier-bundle/actions/workflows/ci.yml)

Stripe subscription billing for Symfony 7.x and 8.x, inspired by [Laravel Cashier](https://github.com/laravel/cashier-stripe).

## Features

- **Subscription Management** - Create, update, cancel subscriptions with trial support
- **Payment Processing** - One-time charges, payment intents, setup intents
- **Invoice Generation** - PDF invoices via Dompdf or Snappy
- **Webhook Handling** - Automatic Stripe webhook processing with 10+ handlers
- **Checkout Sessions** - Stripe Checkout integration
- **Coupons & Discounts** - Full coupon and promotion code support
- **Tax Management** - Tax rates and automatic tax calculation
- **Customer Portal** - Billing portal URL generation
- **Metered Billing** - Usage-based subscription reporting

## Installation

```bash
composer require makfly/stripe-cashier-bundle
```

## Configuration

### 1. Environment Variables

```env
STRIPE_KEY=pk_test_your_public_key
STRIPE_SECRET=sk_test_your_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
```

### 2. Bundle Configuration

Create `config/packages/cashier.yaml`:

```yaml
cashier:
    key: '%env(STRIPE_KEY)%'
    secret: '%env(STRIPE_SECRET)%'
    webhook:
        secret: '%env(STRIPE_WEBHOOK_SECRET)%'
        tolerance: 300
    currency: 'usd'
    currency_locale: 'en'
    invoices:
        renderer: 'CashierBundle\Service\InvoiceRenderer\DompdfInvoiceRenderer'
```

### 3. Entity Setup

Make your User entity implement `BillableEntityInterface`:

```php
use CashierBundle\Contract\BillableEntityInterface;
use CashierBundle\Contract\BillableTrait;

class User implements BillableEntityInterface
{
    use BillableTrait;

    #[ORM\OneToOne]
    private ?StripeCustomer $stripeCustomer = null;
}
```

### 4. Database

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Quick Start

### Create a Subscription

```php
use CashierBundle\Service\SubscriptionBuilder;

$user = $userRepository->find(1);
$subscription = $user->newSubscription('default', 'price_monthly')
    ->trialDays(14)
    ->create($paymentMethodId);
```

### Check Subscription Status

```php
if ($user->subscribed('default')) {
    // User has active subscription
}

if ($user->onTrial('default')) {
    // User is on trial
}
```

### Process Payments

```php
$payment = $user->charge(1000, $paymentMethodId); // $10.00
```

### Handle Webhooks

Configure your webhook URL in Stripe dashboard:

```
https://your-domain.com/stripe/webhook
```

Or create automatically:

```bash
php bin/console cashier:webhook --url=https://your-domain.com/stripe/webhook
```

## Webhook Events

| Handler | Stripe Event |
|---------|--------------|
| SubscriptionCreatedHandler | `customer.subscription.created` |
| SubscriptionUpdatedHandler | `customer.subscription.updated` |
| SubscriptionDeletedHandler | `customer.subscription.deleted` |
| CustomerUpdatedHandler | `customer.updated` |
| InvoicePaidHandler | `invoice.paid` |
| CheckoutSessionCompletedHandler | `checkout.session.completed` |

## Symfony Events

Listen to these events in your application:

- `SubscriptionCreatedEvent` - New subscription created
- `SubscriptionUpdatedEvent` - Subscription updated
- `SubscriptionDeletedEvent` - Subscription cancelled
- `PaymentSucceededEvent` - Payment successful
- `PaymentFailedEvent` - Payment failed

## Services

| Service | Purpose |
|---------|---------|
| `CashierBundle\Service\Cashier` | Main service, formatting utilities |
| `CashierBundle\Service\SubscriptionBuilder` | Build subscriptions |
| `CashierBundle\Service\CheckoutService` | Stripe Checkout |
| `CashierBundle\Service\InvoiceService` | Invoice management |
| `CashierBundle\Service\PaymentService` | Payment processing |
| `CashierBundle\Service\CustomerService` | Customer management |

## Commands

| Command | Description |
|---------|-------------|
| `cashier:webhook` | Create Stripe webhook endpoint |
| `cashier:report-usage` | Report metered usage |
| `cashier:cleanup-sessions` | Cleanup expired checkout sessions |

## Testing

```bash
./vendor/bin/phpunit --configuration phpunit.xml
```

## Quality Checks

```bash
composer lint:phpstan
composer lint:cs
composer audit:composer
composer quality
```

## Documentation

Full documentation available at [cashier-symfony.vercel.app](https://cashier-symfony.vercel.app)

- [Installation Guide](https://cashier-symfony.vercel.app/docs/installation)
- [Subscriptions](https://cashier-symfony.vercel.app/docs/subscriptions)
- [Webhooks](https://cashier-symfony.vercel.app/docs/webhooks)

## Requirements

- PHP 8.2 to 8.5 (tested in CI)
- Symfony 7.x or 8.x
- Doctrine ORM 3.0+
- Stripe PHP SDK ^16.0

## License

MIT License
