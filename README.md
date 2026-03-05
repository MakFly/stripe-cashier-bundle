# CashierBundle

[![CI](https://github.com/MakFly/stripe-cashier-bundle/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/MakFly/stripe-cashier-bundle/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/makfly/stripe-cashier-bundle.svg?style=flat-square)](https://packagist.org/packages/makfly/stripe-cashier-bundle)
[![PHP](https://img.shields.io/badge/PHP-8.2%20|%208.3%20|%208.4%20|%208.5-777BB4?style=flat-square)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

Stripe billing for Symfony 7.x and 8.x, inspired by Laravel Cashier.

## Installation

```bash
composer require makfly/stripe-cashier-bundle
php bin/console cashier:install
```

The installer creates the missing bundle files and directories:

- `config/packages/cashier.yaml`
- `config/packages/cashier_doctrine.yaml`
- `config/routes/cashier.yaml`
- Stripe env vars in `.env`
- `var/data`
- `var/data/invoices`

## Runtime requirements

| Dependency | Version |
|------------|---------|
| PHP | `^8.2` |
| Symfony | `^7.0` or `^8.0` |
| Doctrine ORM | `^3.0` |
| Stripe PHP SDK | `^16.0` |

### `ext-intl`

`ext-intl` is recommended, but not required.

Without `intl`, the bundle still works:

- currency formatting falls back safely
- invoice dates fall back to ISO format
- invoice translations still work for shipped locales

With `intl`, invoice rendering is cleaner and more locale-aware.

## Minimal configuration

```yaml
cashier:
    key: '%env(STRIPE_KEY)%'
    secret: '%env(STRIPE_SECRET)%'
    path: cashier
    webhook:
        secret: '%env(STRIPE_WEBHOOK_SECRET)%'
        tolerance: 300
    currency: usd
    currency_locale: en
    default_subscription_type: default
    invoices:
        renderer: CashierBundle\Service\InvoiceRenderer\DompdfInvoiceRenderer
        default_locale: en
        supported_locales: ['en', 'fr']
        storage:
            driver: local
            path: '%kernel.project_dir%/var/data/invoices'
```

## Invoice pipeline

The bundle can:

- read Stripe invoices
- render a PDF through the configured renderer
- archive paid invoice PDFs to `var/data/invoices`
- persist invoice archive metadata in `cashier_generated_invoices`

Stripe Checkout sessions created by the bundle propagate checkout metadata to invoice creation metadata. That makes it easier for consumer applications to link:

- checkout session
- payment intent
- Stripe invoice
- archived PDF
- local order or booking

## Invoice customization

You can customize invoices at four levels:

- override `templates/bundles/CashierBundle/invoice/default.html.twig`
- replace `cashier.invoices.renderer`
- replace `CashierBundle\Contract\InvoiceLocaleResolverInterface`
- replace `CashierBundle\Contract\InvoiceTranslationProviderInterface`

## Webhooks

The bundle exposes `POST /cashier/webhook` and ships a local CLI helper:

```bash
php bin/console cashier:webhook:listen --forward-to --base-url http://localhost:8000
```

This command forwards Stripe CLI events, persists the signing secret, and colorizes incoming events and HTTP responses.

## Documentation

Source documentation lives in `docs/` and the main sections are:

- introduction
- installation
- configuration
- customers
- payments
- subscriptions
- checkout
- invoices
- webhooks
- events
- commands
- twig
- API reference

## Compatibility policy

The documented configuration under `cashier.*`, documented commands, documented public contracts, and documented events are the supported public API surface.

Breaking changes should only be released in a new major version.
