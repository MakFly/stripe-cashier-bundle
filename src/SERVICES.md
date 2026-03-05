# CashierBundle Services Documentation

## Overview

All services have been created in `/home/kev/Documents/lab/brainstorming/cashier-symfony/CashierBundle/Service/`

## Services List

### Core Services

1. **Cashier** (`Service/Cashier.php`)
   - Main service class with static configuration
   - Methods: `formatAmount()`, `findSubscription()`, `findInvoice()`, `findCustomer()`
   - Configuration: `$currency`, `$currencyLocale`, `$logger`, `$deactivatePastDue`, `$deactivateIncomplete`

2. **CustomerService** (`Service/CustomerService.php`)
   - Manages Stripe customer lifecycle
   - Methods: `create()`, `update()`, `find()`, `sync()`, `createOrGetStripeId()`

3. **SubscriptionService** (`Service/SubscriptionService.php`)
   - Manages subscriptions
   - Methods: `create()`, `update()`, `cancel()`, `resume()`, `swap()`, `updateQuantity()`

4. **SubscriptionBuilder** (`Service/SubscriptionBuilder.php`)
   - Fluent builder for creating subscriptions
   - Methods: `price()`, `meteredPrice()`, `quantity()`, `trialDays()`, `trialUntil()`, `skipTrial()`, `withCoupon()`, `withPromotionCode()`, `create()`

### Payment Services

5. **PaymentService** (`Service/PaymentService.php`)
   - Handles charges and refunds
   - Methods: `charge()`, `pay()`, `refund()`, `refundPartial()`

6. **PaymentIntentService** (`Service/PaymentIntentService.php`)
   - Manages payment intents
   - Methods: `create()`, `find()`, `capture()`, `cancel()`, `confirm()`, `update()`, `authorize()`

7. **PaymentMethodService** (`Service/PaymentMethodService.php`)
   - Manages payment methods
   - Methods: `add()`, `updateDefault()`, `list()`, `default()`, `hasDefault()`, `remove()`

8. **SetupIntentService** (`Service/SetupIntentService.php`)
   - Manages setup intents for saving payment methods
   - Methods: `create()`, `find()`, `update()`, `confirm()`, `cancel()`

### Invoice Services

9. **InvoiceService** (`Service/InvoiceService.php`)
   - Manages invoices
   - Methods: `list()`, `create()`, `find()`, `upcoming()`, `createInvoiceItem()`, `tab()`, `pay()`, `invoiceFor()`

10. **InvoiceRenderer/DompdfInvoiceRenderer** (`Service/InvoiceRenderer/DompdfInvoiceRenderer.php`)
    - PDF invoice renderer using Dompdf
    - Methods: `render()`, `stream()`

11. **InvoiceRenderer/SnappyInvoiceRenderer** (`Service/InvoiceRenderer/SnappyInvoiceRenderer.php`)
    - PDF invoice renderer using Snappy (wkhtmltopdf)
    - Methods: `render()`, `stream()`

### Other Services

12. **CheckoutService** (`Service/CheckoutService.php`)
    - Manages Stripe Checkout sessions
    - Methods: `create()`, `charge()`, `billingPortal()`, `findSession()`, `createSubscription()`

13. **TaxService** (`Service/TaxService.php`)
    - Manages tax rates and calculations
    - Methods: `getTaxRates()`, `getPriceTaxRates()`, `calculate()`, `createTaxRate()`, `attachTaxRatesToPrice()`, `listAllTaxRates()`

14. **StripeLogger** (`Logger/StripeLogger.php`)
    - PSR-3 logger implementation for Stripe
    - Implements: `LoggerInterface`

## Configuration

Services are configured in `Infrastructure/config/services.yaml`

### Environment Variables

- `STRIPE_SECRET_KEY` - Stripe API secret key (required)
- `CASHIER_CURRENCY` - Default currency (default: `usd`)
- `CASHIER_CURRENCY_LOCALE` - Default locale (default: `en`)
- `CASHIER_INVOICE_PAPER` - Invoice paper size (default: `letter`)
- `CASHIER_INVOICE_REMOTE_ENABLED` - Enable remote assets in PDF (default: `false`)
- `CASHIER_INVOICE_ORIENTATION` - Invoice orientation (default: `portrait`)
- `CASHIER_AUTOMATIC_TAX` - Enable automatic tax calculation (default: `false`)

## Dependencies

### Required

- `stripe/stripe-php` - Stripe PHP library
- `symfony/framework-bundle` - Symfony framework
- `doctrine/orm` - Doctrine ORM
- `moneyphp/money` - Money formatting
- `twig/twig` - Template engine

### Optional (for invoice rendering)

Choose one:
- `dompdf/dompdf` - For DompdfInvoiceRenderer
- `knplabs/knp-snappy-bundle` - For SnappyInvoiceRenderer

## Usage Examples

### Creating a Subscription

```php
use CashierBundle\Service\SubscriptionBuilder;

// Inject SubscriptionBuilder (create new instance each time)
$subscription = $billable->newSubscription('default', 'price_xxx')
    ->trialDays(14)
    ->withCoupon('SUMMER2025')
    ->create();
```

### Processing a Payment

```php
use CashierBundle\Service\PaymentService;

try {
    $payment = $paymentService->charge(
        $billable,
        1000, // $10.00
        'pm_xxx',
        ['description' => 'Order #123']
    );

    echo "Payment successful: " . $payment->id();
} catch (IncompletePaymentException $e) {
    // Handle payment requiring action
    $payment = $e->payment();
    echo "Payment requires action: " . $payment->clientSecret();
}
```

### Managing Invoices

```php
use CashierBundle\Service\InvoiceService;

// List invoices
$invoices = $invoiceService->list($billable);

// Get upcoming invoice
$upcoming = $invoiceService->upcoming($billable);

// Pay an invoice
$payment = $invoiceService->pay($invoice);

// Download invoice as PDF
$response = $invoice->download(['company_logo' => '/path/to/logo.png']);
```

### Using Checkout

```php
use CashierBundle\Service\CheckoutService;

// Create checkout session
$checkout = $checkoutService->create($billable, [
    ['price' => 'price_xxx', 'quantity' => 1],
], [
    'success_url' => 'https://example.com/success',
    'cancel_url' => 'https://example.com/cancel',
]);

header('Location: ' . $checkout->url());
```

## Service Tags and Autowiring

All services are auto-configured and can be autowired. The bundle uses constructor injection with `private readonly` properties following Symfony 8 best practices.

## Testing

Services can be easily mocked for testing using Symfony's kernel or by mocking the StripeClient directly.
