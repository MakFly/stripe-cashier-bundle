# CashierBundle Views Documentation

This directory contains Twig templates for the CashierBundle.

## Templates Structure

```
views/
├── base.html.twig           # Base template for Cashier pages
├── payment/
│   └── show.html.twig      # Payment confirmation page (SCA)
└── invoice/
    └── default.html.twig   # Default invoice template
```

## Usage

### Payment Template (`payment/show.html.twig`)

This template is used for Strong Customer Authentication (SCA) payments that require additional confirmation.

**Variables:**
- `stripeKey` (string) - Stripe publishable key
- `clientSecret` (string) - PaymentIntent client secret
- `paymentIntentId` (string) - PaymentIntent ID
- `return_url` (string) - URL to redirect after confirmation
- `amount` (int) - Payment amount in cents
- `currency` (string) - Payment currency code

**Example:**
```php
return $this->render('@Cashier/payment/show.html.twig', [
    'stripeKey' => $this->getParameter('cashier.key'),
    'clientSecret' => $paymentIntent->client_secret,
    'paymentIntentId' => $paymentIntentId,
    'return_url' => $this->generateUrl('home', [], UrlGeneratorInterface::ABSOLUTE_URL),
    'amount' => $paymentIntent->amount,
    'currency' => $paymentIntent->currency,
]);
```

### Invoice Template (`invoice/default.html.twig`)

This template is used to generate PDF invoices.

**Variables:**
- `invoice` (object) - Invoice object with properties:
  - `number` (string) - Invoice number
  - `id` (string) - Invoice ID
  - `date` (DateTime) - Invoice date
  - `dueDate` (DateTime, optional) - Due date
  - `status` (string) - Invoice status (paid, pending, draft)
  - `subtotal` (int) - Subtotal amount in cents
  - `tax` (int, optional) - Tax amount in cents
  - `total` (int) - Total amount in cents
  - `currency` (string) - Currency code
  - `items` (array) - Invoice line items
  - `discounts` (array, optional) - Applied discounts
- `customer` (object, optional) - Customer information
- `company_*` (various, optional) - Company information overrides

**Example:**
```php
return $this->render('@Cashier/invoice/default.html.twig', [
    'invoice' => $invoice,
    'customer' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'address' => '123 Main St',
    ],
    'company_name' => 'Your Company',
    'company_address' => "456 Business Ave\nCity, State 12345",
]);
```

## Twig Filters

The bundle provides a custom Twig filter for formatting Stripe amounts:

### `stripe_amount`

Formats a Stripe amount (in cents) to a human-readable currency format.

```twig
{{ 1000|stripe_amount('usd') }}  {# $10.00 #}
{{ 1000|stripe_amount('eur') }}  {# 10,00 € #}
{{ 1000|stripe_amount('gbp') }}  {# £10.00 #}
```

## Customization

You can override any template in your own application by creating a file with the same path in your `templates/` directory:

```
templates/
└── cashier/
    ├── base.html.twig
    ├── payment/
    │   └── show.html.twig
    └── invoice/
        └── default.html.twig
```

## PDF Generation

The invoice template is designed to work with Dompdf for PDF generation:

```php
use Dompdf\Dompdf;

$html = $this->renderView('@Cashier/invoice/default.html.twig', [
    'invoice' => $invoice,
    'customer' => $customer,
]);

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->render();
$pdfContent = $dompdf->output();
```

## Styling

All templates use responsive design and work well on mobile devices. The invoice template is print-optimized and includes `@media print` rules for proper formatting when printing or generating PDFs.
