# Migration vers `v1.0.0`

Cette note couvre la migration depuis une version `v0.x` de `makfly/stripe-cashier-bundle` vers `v1.0.0`.

## Résumé

`v1.0.0` stabilise le contrat public du bundle:

- configuration `cashier.*`
- commandes documentées
- contrats publics documentés
- entités Doctrine du bundle documentées
- événements Symfony documentés

Le bundle reste utilisable sans `ext-intl`, mais l'extension est recommandée pour un rendu de facture plus propre.

## Installation / update

```bash
composer require makfly/stripe-cashier-bundle:^1.0.0
php bin/console cashier:install
```

La commande `cashier:install` est idempotente. Elle complète uniquement ce qui manque.

## Ce qu'il faut vérifier

### 1. Configuration générée

Les fichiers attendus sont:

- `config/packages/cashier.yaml`
- `config/packages/cashier_doctrine.yaml`
- `config/routes/cashier.yaml`

### 2. Variables d'environnement

Les variables Stripe suivantes doivent être présentes:

- `STRIPE_KEY`
- `STRIPE_SECRET`
- `STRIPE_WEBHOOK_SECRET`

### 3. Stockage local des factures

Les répertoires attendus sont:

- `var/data`
- `var/data/invoices`

Les factures archivées sont stockées localement dans `var/data/invoices`.

### 4. Entité billable

Votre entité applicative doit implémenter `CashierBundle\Contract\BillableEntityInterface` et utiliser `BillableTrait`.

### 5. Doctrine

Le mapping Doctrine du bundle doit être déclaré explicitement.

La table d'archivage facture attendue est `cashier_generated_invoices`.

## Changements importants par rapport à `v0.x`

### Installation fiable sans bricolage manuel

Le flux officiel est maintenant:

```bash
composer require makfly/stripe-cashier-bundle
php bin/console cashier:install
```

### Factures archivées en local

Le bundle peut maintenant:

- lire l'invoice Stripe
- générer un PDF
- l'archiver localement
- persister une trace `GeneratedInvoice`

### Liaison métier via metadata Stripe

Les sessions Checkout créées par le bundle propagent les `metadata` vers l'invoice Stripe générée. Cela permet de rapprocher plus proprement:

- une commande applicative
- une session Checkout
- un payment intent
- une invoice Stripe
- un PDF archivé

### `ext-intl` non bloquante

Le bundle ne force pas `ext-intl`.

Sans `intl`:

- pas de crash runtime
- fallback monétaire sûr
- dates de facture en ISO

Avec `intl`:

- meilleur rendu FR/EN
- formatage date/devise plus naturel

### Messenger désormais optionnel au runtime

`v1.0.0` ne casse plus l'installation d'un projet Symfony vierge si `symfony/messenger` n'est pas installé.

Si Messenger est présent, les handlers du bundle sont enregistrés.
Sinon, le bundle continue à booter normalement.

## Checklist de validation après migration

```bash
php bin/console about
php bin/console cashier:install
php bin/console debug:router | grep cashier
php bin/console doctrine:mapping:info
php bin/console doctrine:schema:validate --skip-sync
```

Si votre application consomme réellement Checkout/Webhooks/Invoices, ajoutez aussi un test réel:

```bash
php bin/console cashier:webhook:listen --forward-to --base-url http://localhost:8000
```

Puis vérifiez:

- création de session Checkout
- réception des webhooks Stripe
- archivage du PDF dans `var/data/invoices`

## Ruptures à connaître

Aucune rupture volontaire n'est introduite sur la surface publique documentée.

En revanche, si vous dépendiez de comportements non documentés ou de classes internes, vous devez réaligner votre intégration sur:

- la configuration documentée
- les contrats documentés
- les événements documentés

## Références

- `README.md`
- `docs/app/docs/installation/page.mdx`
- `docs/app/docs/configuration/page.mdx`
- `docs/app/docs/invoices/page.mdx`
- `docs/app/docs/webhooks/page.mdx`
