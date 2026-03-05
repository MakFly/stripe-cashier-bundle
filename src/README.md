# CashierBundle

> Stripe Cashier pour Symfony 8.X - Gestion complète des abonnements Stripe

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-purple)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-8.x-black)](https://symfony.com)
[![Stripe](https://img.shields.io/badge/Stripe-16.x-blue)](https://stripe.com)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

CashierBundle est un portage de [Laravel Cashier Stripe](https://github.com/laravel/cashier-stripe) pour Symfony 8.X. Il fournit une interface expressive et élégante pour gérer les abonnements Stripe, les factures, les coupons, et plus encore.

## 📚 Documentation

La documentation complète est disponible sur **[docs.cashierbundle.dev](https://docs.cashierbundle.dev)**

## ⚡ Installation

```bash
composer require makfly/stripe-cashier-bundle
```

Sans Symfony Flex, lancez ensuite :

```bash
php bin/console cashier:install
```

## 🔧 Configuration

### 1. Variables d'environnement

```env
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
```

### 2. Configuration du bundle

```yaml
# config/packages/cashier.yaml
cashier:
    key: '%env(STRIPE_KEY)%'
    secret: '%env(STRIPE_SECRET)%'
    webhook:
        secret: '%env(STRIPE_WEBHOOK_SECRET)%'
        tolerance: 300
    currency: 'usd'
    currency_locale: 'en'
    default_subscription_type: 'default'
    invoices:
        renderer: 'CashierBundle\Service\InvoiceRenderer\DompdfInvoiceRenderer'
        options:
            paper: 'letter'
```

### 3. Doctrine

Ajoutez une configuration Doctrine explicite :

```yaml
# config/packages/cashier_doctrine.yaml
doctrine:
    orm:
        mappings:
            CashierBundle:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/vendor/makfly/stripe-cashier-bundle/src/Entity'
                prefix: 'CashierBundle\Entity'
                alias: CashierBundle
```

### 4. Entité User

Faites implémenter `BillableEntityInterface` à votre entité User :

```php
use CashierBundle\Concerns\BillableTrait;
use CashierBundle\Contract\BillableEntityInterface;

class User implements BillableEntityInterface
{
    use BillableTrait;
}
```

`BillableTrait` fonctionne sans méthode custom supplémentaire dans votre entité.

### 5. Base de données

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## 🚀 Utilisation rapide

### Créer un abonnement

```php
$user = $userRepository->find(1);

$subscription = $user->newSubscription('default', 'price_premium')
    ->trialDays(14)
    ->create($paymentMethodId);
```

### Vérifier un abonnement

```php
if ($user->subscribed('default')) {
    // L'utilisateur est abonné
}

if ($user->onTrial('default')) {
    // L'utilisateur est en période d'essai
}
```

### Gérer les paiements

```php
// Paiement ponctuel
$payment = $user->charge(1000, $paymentMethodId); // 10.00 EUR

// Facturer à la prochaine facture
$user->invoiceFor('Service supplémentaire', 500);

// Rembourser
$refund = $user->refund($paymentIntentId);
```

### Checkout Stripe

```php
$checkout = $user->checkout([
    ['price' => 'price_product', 'quantity' => 2],
]);

return $this->redirect($checkout->url());
```

### Webhooks

Les webhooks sont gérés automatiquement. Configurez l'URL dans Stripe :

```
https://your-domain.com/cashier/webhook
```

```bash
# Créer le webhook automatiquement
php bin/console cashier:webhook --url=https://your-domain.com/cashier/webhook
```

## 📋 Fonctionnalités

| Fonctionnalité | Description |
|---------------|-------------|
| **Abonnements** | Création, annulation, reprise, changement de plan |
| **Périodes d'essai** | Essai gratuit configurable |
| **Paiements** | Charges ponctuels et récurrents |
| **Factures** | Génération PDF automatique |
| **Coupons** | Codes promotionnels |
| **Checkout** | Sessions Stripe Checkout |
| **Webhooks** | 10 handlers intégrés |
| **Metered Billing** | Facturation à l'usage |

## 🔗 Liens

- **[Documentation complète](https://cashier-symfony.vercel.app)**
- **[GitHub](https://github.com/MakFly/stripe-cashier-bundle)**
- **[Stripe Documentation](https://stripe.com/docs)**

## 📝 License

MIT License - voir [LICENSE](LICENSE)

---

Développé avec ❤️ pour la communauté Symfony
