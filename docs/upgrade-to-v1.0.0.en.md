# Upgrade to `v1.0.0`

This note covers the upgrade path from any `v0.x` release of `makfly/stripe-cashier-bundle` to `v1.0.0`.

## Summary

`v1.0.0` stabilizes the documented public surface of the bundle:

- `cashier.*` configuration
- documented console commands
- documented public contracts
- documented Doctrine entities
- documented Symfony events

The bundle still works without `ext-intl`, although the extension is recommended for cleaner invoice rendering.

## Install / update

```bash
composer require makfly/stripe-cashier-bundle:^1.0.0
php bin/console cashier:install
```

`cashier:install` is idempotent and only creates missing files or directories.

## What to verify

### 1. Generated config

Expected files:

- `config/packages/cashier.yaml`
- `config/packages/cashier_doctrine.yaml`
- `config/routes/cashier.yaml`

### 2. Environment variables

Expected Stripe env vars:

- `STRIPE_KEY`
- `STRIPE_SECRET`
- `STRIPE_WEBHOOK_SECRET`

### 3. Local invoice storage

Expected directories:

- `var/data`
- `var/data/invoices`

Archived invoices are stored in `var/data/invoices`.

### 4. Billable entity

Your application entity must implement `CashierBundle\Contract\BillableEntityInterface` and use `BillableTrait`.

### 5. Doctrine

Doctrine mapping for the bundle must be declared explicitly.

The invoice archive table is `cashier_generated_invoices`.

## Important changes from `v0.x`

### Reliable installation flow

The official flow is now:

```bash
composer require makfly/stripe-cashier-bundle
php bin/console cashier:install
```

### Local invoice archiving

The bundle can now:

- read the Stripe invoice
- render a PDF
- archive it locally
- persist a `GeneratedInvoice` record

### Metadata propagation for business linking

Checkout sessions created by the bundle propagate checkout `metadata` into the generated Stripe invoice metadata. This gives consuming applications a cleaner bridge between:

- a local order or booking
- a checkout session
- a payment intent
- a Stripe invoice
- an archived PDF

### `ext-intl` remains optional

The bundle does not force `ext-intl`.

Without `intl`:

- no runtime crash
- safe money formatting fallback
- invoice dates fall back to ISO format

With `intl`:

- cleaner FR/EN output
- more natural date/currency formatting

### Messenger is now optional at runtime

`v1.0.0` no longer breaks installation on a fresh Symfony app when `symfony/messenger` is not installed.

If Messenger is present, the bundle registers its message handlers.
If not, the bundle still boots correctly.

## Post-upgrade validation checklist

```bash
php bin/console about
php bin/console cashier:install
php bin/console debug:router | grep cashier
php bin/console doctrine:mapping:info
php bin/console doctrine:schema:validate --skip-sync
```

If your application really uses Checkout/Webhooks/Invoices, add one real integration check:

```bash
php bin/console cashier:webhook:listen --forward-to --base-url http://localhost:8000
```

Then verify:

- checkout session creation
- Stripe webhook reception
- archived PDF creation under `var/data/invoices`

## Breaking-change note

No intentional breaking change is introduced on the documented public API surface.

If your integration relied on undocumented internals, align it with:

- documented configuration
- documented contracts
- documented events

## References

- `README.md`
- `docs/app/docs/installation/page.mdx`
- `docs/app/docs/configuration/page.mdx`
- `docs/app/docs/invoices/page.mdx`
- `docs/app/docs/webhooks/page.mdx`
