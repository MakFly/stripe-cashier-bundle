# CashierBundle - Model Layer

This directory contains all Value Objects (VOs) for the CashierBundle. These VOs provide a type-safe, immutable interface to Stripe objects.

## Value Objects

### Core Payment VOs

#### Payment
Wrapper around Stripe `PaymentIntent` objects. Provides methods for checking payment status and performing actions like capture and cancel.

**Key methods:**
- `amount()`, `rawAmount()`, `currency()` - Amount information
- `status()`, `isSucceeded()`, `isCanceled()` - Status checks
- `capture()`, `cancel()` - Actions
- `requiresPaymentMethod()`, `requiresAction()`, `requiresConfirmation()` - State checks

#### PaymentMethod
Wrapper around Stripe `PaymentMethod` objects. Supports both card and SEPA debit payment methods.

**Key methods:**
- `type()`, `isDefault()` - Basic info
- `brand()`, `lastFour()`, `expiryMonth()`, `expiryYear()` - Card details
- `bank()` - SEPA debit info

#### SetupIntent
Wrapper around Stripe `SetupIntent` objects used for setting up future payments.

**Key methods:**
- `clientSecret()`, `paymentMethodId()` - Setup details
- `isSucceeded()`, `requiresAction()` - Status checks
- `cancel()` - Cancel the setup intent

### Invoice VOs

#### Invoice
Wrapper around Stripe `Invoice` objects. Represents a complete invoice with line items, taxes, and payments.

**Key methods:**
- `total()`, `subtotal()`, `tax()` - Amount information
- `date()`, `dueDate()` - Date information
- `items()`, `taxes()`, `payments()`, `discounts()` - Collections
- `download()` - Generate PDF invoice
- `pay()` - Pay the invoice

#### InvoiceLineItem
Wrapper around Stripe `InvoiceLineItem` objects. Represents a single line on an invoice.

**Key methods:**
- `description()`, `quantity()` - Item details
- `amount()`, `rawAmount()` - Amount information
- `priceId()` - Associated price

#### InvoicePayment
Value object representing a payment made against an invoice.

**Key methods:**
- `paymentIntentId()`, `amount()`, `status()` - Payment details
- `isSucceeded()` - Status check

### Discount VOs

#### Coupon
Wrapper around Stripe `Coupon` objects. Represents a discount coupon.

**Key methods:**
- `percentOff()`, `amountOff()` - Discount type
- `duration()`, `durationInMonths()` - Duration info
- `isPercentage()`, `isFixedAmount()` - Type checks

#### Discount
Wrapper around Stripe `Discount` objects. Links a coupon to a customer.

**Key methods:**
- `coupon()` - Get associated Coupon
- `start()`, `end()` - Validity period

#### PromotionCode
Wrapper around Stripe `PromotionCode` objects. Represents a customer-facing code.

**Key methods:**
- `code()`, `active()` - Code info
- `coupon()` - Get associated Coupon
- `maxRedemptions()`, `timesRedeemed()` - Redemption info

### Tax VOs

#### Tax
Value object representing a tax amount on an invoice.

**Key methods:**
- `name()`, `percent()` - Tax details
- `amount()`, `rawAmount()` - Amount information
- `inclusive()` - Whether tax is inclusive

#### TaxRate
Wrapper around Stripe `TaxRate` objects. Represents a tax rate configuration.

**Key methods:**
- `displayName()`, `percentage()` - Rate details
- `country()`, `state()` - Jurisdiction
- `active()`, `inclusive()` - Configuration

### Customer VOs

#### CustomerBalanceTransaction
Wrapper around Stripe `CustomerBalanceTransaction` objects. Represents balance transactions.

**Key methods:**
- `amount()`, `rawAmount()`, `currency()` - Amount info
- `type()`, `description()` - Transaction details
- `isCredit()`, `isDebit()` - Transaction type

### Checkout VOs

#### Checkout
Wrapper around Stripe Checkout `Session` objects. Represents a checkout session.

**Key methods:**
- `url()` - Checkout URL
- `paymentIntentId()`, `setupIntentId()`, `subscriptionId()` - Related objects
- `isComplete()`, `isExpired()`, `isOpen()` - Status checks

## Helper Classes

#### Cashier
Static utility class for formatting amounts.

**Key methods:**
- `formatAmount()` - Format an amount for display
- `useCurrency()`, `useLocale()` - Configuration
- `getCurrency()`, `getLocale()` - Get current settings

## Design Patterns

All Value Objects follow these principles:
- **Immutability**: All properties are `readonly`
- **Type Safety**: Strict types with PHP 8.2+ syntax
- **Encapsulation**: Private properties with public getter methods
- **Fluent Interface**: Methods that modify state return `$this` for chaining
- **Stripe Wrapper**: Each VO wraps a corresponding Stripe object

## Usage Examples

```php
use CashierBundle\Model\Payment;

// Create from Stripe PaymentIntent
$payment = new Payment($stripePaymentIntent);

// Check status
if ($payment->isSucceeded()) {
    echo $payment->amount(); // e.g., "$29.99"
}

// Capture payment
$payment->capture();

// Get raw Stripe object
$stripeIntent = $payment->asStripePaymentIntent();
```

```php
use CashierBundle\Model\Cashier;

// Configure
Cashier::useCurrency('eur');
Cashier::useLocale('fr_FR');

// Format amounts
echo Cashier::formatAmount(2999); // "29.99 €"
echo Cashier::formatAmount(2999, 'usd'); // "$29.99"
```
