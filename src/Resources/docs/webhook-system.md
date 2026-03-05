# CashierBundle Webhook System

## Overview

The CashierBundle webhook system handles incoming Stripe webhooks synchronously. All webhook events are processed in real-time during the HTTP request.

## Architecture

### Components

1. **WebhookController** (`/home/kev/Documents/lab/brainstorming/cashier-symfony/CashierBundle/Controller/WebhookController.php`)
   - HTTP endpoint for Stripe webhooks
   - Route: `POST /cashier/webhook`
   - Delegates processing to WebhookProcessor

2. **WebhookProcessor** (`/home/kev/Documents/lab/brainstorming/cashier-symfony/CashierBundle/Webhook/WebhookProcessor.php`)
   - Verifies webhook signatures
   - Routes events to appropriate handlers
   - Dispatches Symfony events

3. **Webhook Handlers** (`/home/kev/Documents/lab/brainstorming/cashier-symfony/CashierBundle/Webhook/Handler/`)
   - AbstractWebhookHandler - Base class with common utilities
   - 10 concrete handlers for different Stripe events

4. **Symfony Events** (`/home/kev/Documents/lab/brainstorming/cashier-symfony/CashierBundle/Event/`)
   - WebhookReceivedEvent - Fired when webhook is received
   - WebhookHandledEvent - Fired after webhook is processed
   - SubscriptionCreatedEvent - New subscription created
   - SubscriptionUpdatedEvent - Subscription updated
   - SubscriptionDeletedEvent - Subscription deleted
   - PaymentSucceededEvent - Payment succeeded
   - PaymentFailedEvent - Payment failed

## Available Webhook Handlers

### Subscription Handlers

- **SubscriptionCreatedHandler** - `customer.subscription.created`
- **SubscriptionUpdatedHandler** - `customer.subscription.updated`
- **SubscriptionDeletedHandler** - `customer.subscription.deleted`

### Customer Handlers

- **CustomerUpdatedHandler** - `customer.updated`
- **CustomerDeletedHandler** - `customer.deleted`

### Payment Method Handlers

- **PaymentMethodUpdatedHandler** - `payment_method.automatically_updated`

### Invoice Handlers

- **InvoicePaidHandler** - `invoice.paid`
- **InvoicePaymentFailedHandler** - `invoice.payment_failed`
- **InvoicePaymentActionRequiredHandler** - `invoice.payment_action_required`

### Checkout Handlers

- **CheckoutSessionCompletedHandler** - `checkout.session.completed`

## Configuration

### Parameters

```yaml
# config/packages/cashier.yaml
cashier:
    webhook:
        secret: '%env(STRIPE_WEBHOOK_SECRET)%'
        tolerance: 300 # Default timestamp tolerance in seconds
```

### Services

All webhook handlers are automatically registered with the `cashier.webhook_handler` tag in `/home/kev/Documents/lab/brainstorming/cashier-symfony/CashierBundle/Resources/config/webhook_services.php`.

## Usage

### Listening to Webhook Events

Create an event subscriber to listen to webhook events:

```php
<?php

namespace App\EventSubscriber;

use CashierBundle\Event\SubscriptionCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SubscriptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SubscriptionCreatedEvent::class => 'onSubscriptionCreated',
        ];
    }

    public function onSubscriptionCreated(SubscriptionCreatedEvent $event): void
    {
        $subscription = $event->getSubscription();
        // Your logic here
    }
}
```

### Creating Custom Webhook Handlers

1. Create a handler class extending `AbstractWebhookHandler`:

```php
<?php

namespace App\Webhook;

use CashierBundle\Webhook\Handler\AbstractWebhookHandler;
use Stripe\Event;

final class CustomWebhookHandler extends AbstractWebhookHandler
{
    public function handles(): array
    {
        return ['custom.stripe.event'];
    }

    public function handle(Event $event): void
    {
        // Your handling logic
    }
}
```

2. Register it as a service with the `cashier.webhook_handler` tag:

```yaml
# config/services.yaml
services:
    App\Webhook\CustomWebhookHandler:
        tags:
            - { name: 'cashier.webhook_handler' }
```

## Security

- All webhooks are verified using Stripe signature verification
- Timestamp tolerance prevents replay attacks (default: 300 seconds)
- Invalid signatures return HTTP 400 Bad Request

## Testing

### Testing Webhook Locally

Use the Stripe CLI to forward webhooks to your local machine:

```bash
stripe listen --forward-to localhost:8000/cashier/webhook
```

### Triggering Test Webhooks

```bash
stripe trigger customer.subscription.created
stripe trigger invoice.payment_failed
```

## File Structure

```
CashierBundle/
├── Controller/
│   └── WebhookController.php
├── Event/
│   ├── PaymentFailedEvent.php
│   ├── PaymentSucceededEvent.php
│   ├── SubscriptionCreatedEvent.php
│   ├── SubscriptionDeletedEvent.php
│   ├── SubscriptionUpdatedEvent.php
│   ├── WebhookHandledEvent.php
│   └── WebhookReceivedEvent.php
├── Middleware/
│   └── VerifyWebhookSignatureMiddleware.php
├── Resources/
│   └── config/
│       ├── routes/
│       │   └── webhook.yaml
│       └── webhook_services.php
└── Webhook/
    ├── Handler/
    │   ├── AbstractWebhookHandler.php
    │   ├── CheckoutSessionCompletedHandler.php
    │   ├── CustomerDeletedHandler.php
    │   ├── CustomerUpdatedHandler.php
    │   ├── InvoicePaidHandler.php
    │   ├── InvoicePaymentActionRequiredHandler.php
    │   ├── InvoicePaymentFailedHandler.php
    │   ├── PaymentMethodUpdatedHandler.php
    │   ├── SubscriptionCreatedHandler.php
    │   ├── SubscriptionDeletedHandler.php
    │   └── SubscriptionUpdatedHandler.php
    └── WebhookProcessor.php
```

## Technical Details

- **PHP Version**: 8.3+
- **Symfony Version**: 8.0+
- **Stripe API**: Used for signature verification
- **Event Dispatcher**: Symfony EventDispatcher component
- **Locator Pattern**: Handlers are collected via service locator with tag `cashier.webhook_handler`
- **Readonly Classes**: All handlers use PHP 8.2 readonly class syntax
- **Type Safety**: Strict types enabled, full type hints
