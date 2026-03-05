# CashierBundle

`makfly/stripe-cashier-bundle` brings Stripe billing primitives to Symfony applications.

## Official install flow

```bash
composer require makfly/stripe-cashier-bundle
php bin/console cashier:install
```

The installer is idempotent and prepares:

- bundle config files
- Stripe env vars
- local invoice storage directories

## `ext-intl`

`ext-intl` is optional.

The bundle keeps running without it by falling back to:

- ISO invoice dates
- non-ICU monetary formatting

If `intl` is available, invoice formatting becomes locale-aware for money and dates.

## Public customization points

- `CashierBundle\Contract\InvoiceRendererInterface`
- `CashierBundle\Contract\InvoiceStorageInterface`
- `CashierBundle\Contract\InvoiceLocaleResolverInterface`
- `CashierBundle\Contract\InvoiceTranslationProviderInterface`
- `CashierBundle\Contract\WebhookHandlerInterface`

## Invoice locale resolution

Locale resolution order:

1. explicit `locale` / `invoice_locale`
2. Stripe customer `preferred_locales`
3. `cashier.invoices.default_locale`

## Storage

Archived invoices are stored by default in:

- `%kernel.project_dir%/var/data/invoices`

The installer creates the directories if they do not already exist.

## Checkout metadata propagation

When the bundle creates Stripe Checkout payment sessions, checkout `metadata` is copied into invoice creation metadata. This gives the consumer application a deterministic bridge between business resources and archived invoices.
