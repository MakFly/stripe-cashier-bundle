# Plan : Mise à jour complète de la documentation `docs/`

## Contexte

Après une passe complète sur le code source (`src/`) et un audit de chaque page MDX dans `docs/app/docs/`, plusieurs catégories de problèmes ont été identifiées :

1. **Informations incorrectes** — méthodes dépréciées, bugs dans les exemples, nommage incohérent
2. **Features non documentées** — Setup Intents, Messenger, multi-prix, CustomerBalanceTransaction, PaymentIntentService, etc.
3. **Documentation incomplète** — comportements manquants, types de retour absents, injection non montrée
4. **Incohérences transversales** — `BillableInterface` vs `BillableEntityInterface`, syntaxe `--forward-to`, version PHP dans la commande intl

Le site est un Next.js/MDX. Toutes les pages sont dans `docs/app/docs/`. Les pages sont en **français**.

---

## Règles transversales à appliquer sur TOUTES les pages

- **Nommage d'interface** : utiliser systématiquement `BillableEntityInterface` (pas `BillableInterface`) quand on parle de l'entité de l'application (l'entité `User`). `BillableInterface` est réservé aux exemples de signature de méthode (services, paramètres génériques).
- **Montants en centimes** : toujours mentionner « en centimes » quand un paramètre `int $amount` est utilisé.
- **Injection de services** : toujours montrer le constructeur avec injection dans les exemples de controller.
- **Version PHP** : utiliser `php8.x-intl` avec une note générique (`8.2`, `8.3`, ou `8.4` selon la version installée).

---

## Page 1 : `introduction/page.mdx`

### Corrections

- Ajouter `dompdf/dompdf ^3.1` dans la liste des prérequis (renderer par défaut).
- Ajouter `symfony/messenger` dans les dépendances (optionnelle, pour les messages async).

### Ajouts

- Mentionner la feature d'archivage automatique des factures PDF (`GeneratedInvoice`).
- Mentionner l'intégration Messenger comme feature optionnelle.
- Ajouter des badges Packagist (version, PHP, licence) — URLs à dériver du `composer.json`.

---

## Page 2 : `installation/page.mdx`

### Corrections

- **Ligne intl** : remplacer `php8.4-intl` par `php8.x-intl` avec note explicative sur la version.
- **`--forward-to`** : la syntaxe actuelle `cashier:webhook:listen --forward-to --base-url` est incorrecte. La vraie syntaxe est :
  ```
  php bin/console cashier:webhook:listen --forward-to=http://127.0.0.1:8000/cashier/webhook
  ```
  (la valeur est la route Symfony complète, ou sans flag avec `--base-url` pour construire l'URL automatiquement).
- Ajouter mention de `dompdf` comme dépendance installée via `composer`.

### Ajouts

- Mentionner la dépendance optionnelle `symfony/messenger` et ce qu'elle active.
- Ajouter note : la Stripe CLI doit être installée localement pour `cashier:webhook:listen`.

---

## Page 3 : `configuration/page.mdx`

### Corrections

- Expliquer `cashier.path` : c'est le préfixe de la route webhook (`/cashier/webhook`). Si modifié, le secret Stripe doit correspondre à la nouvelle URL.
- Ajouter `events:` dans l'exemple de configuration YAML (cohérence avec la page webhooks).

### Ajouts

- Documenter `default_subscription_type: 'default'` : le type utilisé par les webhooks `customer.subscription.*` pour associer les abonnements Stripe aux entités locales. Si plusieurs types d'abonnements coexistent, ce paramètre détermine lequel est créé automatiquement.
- Documenter les options Dompdf (`paper`, `remote_enabled`) avec leurs valeurs.
- Ajouter section « Renderer alternatif » avec `SnappyInvoiceRenderer` (nécessite `knp-snappy` + `wkhtmltopdf`) :
  ```yaml
  cashier:
    invoices:
      renderer: CashierBundle\Service\InvoiceRenderer\SnappyInvoiceRenderer
  ```
- Documenter les valeurs acceptées pour `currency_locale` (codes BCP 47 : `fr`, `en`, `de`, etc.).

---

## Page 4 : `customers/page.mdx`

### Corrections

- Utiliser `BillableEntityInterface` (et non `BillableInterface`) dans les exemples de controller.
- Ajouter méthodes manquantes dans l'entité : `getPhone(): ?string`, `getAddress(): ?array` — préciser qu'elles peuvent être ajoutées optionnellement.

### Ajouts

- Documenter la synchronisation depuis Stripe : `CustomerService::sync()` et `syncByStripeId()` (utile dans un handler webhook `customer.updated`).
- Documenter le comportement sur suppression Stripe : `CustomerDeletedHandler` supprime le `StripeCustomer` local (pas le `User` applicatif).
- Documenter les champs synchronisés automatiquement dans `StripeCustomer` : `name`, `email`, `phone`, `currency`, `balance`, `address`, `pm_type`, `pm_last_four`, `invoice_prefix`, `tax_exempt`.
- **Gestion du solde customer** : ajouter section Balance :
  - `$user->creditBalance(int $amount, string $description)` — crédite le solde (montant négatif chez Stripe)
  - `$user->debitBalance(int $amount, string $description)` — débite le solde
  - `$user->balance(): string` — solde formaté
  - Mentionner `CustomerBalanceTransaction` (champs : `id`, `amount`, `currency`, `type`, `description`, `isCredit()`, `isDebit()`).

---

## Page 5 : `subscriptions/page.mdx`

### Corrections

- Ajouter exemple complet d'injection de `SubscriptionService` dans un controller.
- Documenter l'effet de `swap()` : proration par défaut (`create_prorations`), pas de `trialDays` maintenu.

### Ajouts

**Cycle de vie complet** :

- `active()` : statut `active` ou `trialing`, pas paused.
- `valid()` : `active` OU `onTrial` OU `onGracePeriod` — le cas d'usage courant pour « l'abonnement donne accès au service ».
- `onGracePeriod()` : annulé mais `ends_at` dans le futur — l'accès doit encore être accordé.
- `paused()` / `notPaused()` / `onPausedGracePeriod()` : abonnement Stripe paused.
- `ended()` : annulé ET `ends_at` dans le passé.
- `incomplete()` / `pastDue()` : statuts d'échec de paiement (configurer `Cashier::$deactivatePastDue` et `$deactivateIncomplete`).
- `recurring()` : actif, pas en trial, pas en grace period.
- Ajouter tableau récapitulatif des méthodes de statut.

**Abonnements multi-prix** :

```php
$user->newSubscription('default')
    ->price('price_monthly_base')
    ->price('price_add_on', 1)
    ->create('pm_card_visa');
```

- Expliquer `SubscriptionItem` (champs : `stripe_price`, `quantity`, `meter_id`, `meter_event_name`).
- Documenter `$subscription->items` (collection de `SubscriptionItem`).

**Annulation différée vs. immédiate** :

```php
$subscriptionService->cancel($subscription); // fin de période
$subscriptionService->cancel($subscription, immediately: true); // immédiat
```

**Reprise** : `resume()` exige que `onGracePeriod()` soit `true`.

**Paramètres `SubscriptionBuilder` complets** :

- `withBillingThresholds(array $thresholds)` — seuils de facturation
- `anchorBillingCycleOn(\DateTimeInterface $date)` — ancrage du cycle
- `withPaymentBehavior(string $behavior)` — défaut `'default_incomplete'`
- `noProrate()` / `prorate()` — gestion des prorations
- `withOptions(array $options)` — options Stripe brutes

---

## Page 6 : `payments/page.mdx`

### Corrections

- Préciser partout que les montants sont **en centimes** (`1999` = 19,99 €).
- Ajouter exemple d'injection de `PaymentService`.

### Ajouts

**Gestion SCA / 3DS** :

- `charge()` et `pay()` lancent `IncompletePaymentException` si le paiement nécessite une action (3DS).
- `$e->payment()` retourne l'objet `Payment` avec `clientSecret()`.
- Rediriger vers `/cashier/payment/{paymentIntentId}` (route `cashier_payment_show`) pour que le client complète le paiement.

**Gestion des erreurs Stripe** :

```php
try {
    $payment = $paymentService->charge($user, 1999, $pmId);
} catch (IncompletePaymentException $e) {
    return new RedirectResponse('/checkout/confirm/' . $e->payment()->id());
} catch (\Stripe\Exception\CardException $e) {
    // Carte refusée
}
```

**`PaymentIntentService`** (section avancée) :

- Création bas niveau avec `capture_method: manual` (paiement en deux temps).
- `authorize()` — retourne `['id', 'client_secret', 'status', 'amount', 'currency']`.
- `capture()` / `cancel()` — capture ou annulation d'un payment intent autorisé.
- `confirm()` — confirmation avec gestion `IncompletePaymentException`.

---

## Page 7 : `payment-methods/page.mdx`

### Corrections

- Remplacer `BillableInterface` par `BillableEntityInterface` dans les exemples.
- **Supprimer** `sofort` et `giropay` de la liste des types supportés (dépréciés par Stripe en 2024).
- Compléter la liste avec les types actuels : `sepa_debit`, `us_bank_account`, `link`, `paypal`.

### Ajouts

**Setup Intents** (nouvelle section) :

- Utilisés pour sauvegarder une méthode de paiement **sans** paiement immédiat.
- `SetupIntentService::create(array $options = []): SetupIntent` — défaut `payment_method_types: ['card']`.
- Le `clientSecret` est passé au frontend pour Stripe Elements.
- Une fois confirmé, `addPaymentMethod()` est appelé avec l'ID retourné.
- Exemple : formulaire de configuration de PM pour un abonnement futur.

---

## Page 8 : `invoices/page.mdx`

### Corrections

- Clarifier que `$invoiceService->list($user)` retourne `Collection<int, Invoice>` (objets `CashierBundle\Model\Invoice`).
- Clarifier que `$invoice->download()` retourne une `Symfony\Component\HttpFoundation\Response` (disposition `attachment`), `$invoice->stream()` retourne une `Response` inline.

### Ajouts

**One-time invoices** (section manquante) :

```php
// Ajouter un item à la prochaine invoice
$user->tab('Service premium', 4999);
// Créer une invoice pour un item précis immédiatement
$invoice = $user->invoiceFor('Frais de configuration', 9900);
```

**Servir les PDF archivés** (section pratique) :

- Les PDF sont stockés dans `var/data/invoices/`.
- Exemple de controller pour servir le fichier via `BinaryFileResponse`.
- `GeneratedInvoice::$relativePath` est le chemin relatif depuis la racine du projet.

**Méthodes de `Invoice`** : ajouter la liste complète avec types de retour :

- `items(): array<InvoiceLineItem>`, `taxes(): array<Tax>`, `payments(): array<InvoicePayment>`, `discounts(): array<Discount>`.

**`InvoiceArchiveService`** : lien direct depuis la page (l'archive se déclenche automatiquement sur `PaymentSucceededEvent`).

**Conventions de metadata** pour le linkage métier :

```yaml
metadata:
  app_resource_type: "order"
  app_resource_id: "123"
  plan_code: "premium"
```

Ces clés sont extraites par `InvoiceArchiveService` et stockées dans `GeneratedInvoice` (`resource_type`, `resource_id`, `plan_code`).

---

## Page 9 : `checkout/page.mdx`

### Corrections

- Préciser que `CheckoutService::create()` retourne `CashierBundle\Model\Checkout`.
- Préciser que `findSession()` retourne `?Checkout`.

### Ajouts

**Modes Checkout** :

- `create()` → mode `payment` (paiement unique)
- `createSubscription()` → mode `subscription` (abonnement)
- `SetupIntentService` pour mode `setup` (collecter un PM sans paiement)

**Propagation des metadata** : documenter le comportement automatique :

- Pour `create()` : les metadata sont propagées vers `invoice_creation.invoice_data.metadata`.
- Pour `createSubscription()` : propagées vers `subscription_data.metadata`.
- Utile pour `InvoiceArchiveService` (linkage `GeneratedInvoice`).

**Options disponibles** : montrer les options Stripe passables (`success_url`, `cancel_url`, `payment_intent_data`, `subscription_data`, `customer_email`, `allow_promotion_codes`).

---

## Page 10 : `webhooks/page.mdx`

### Corrections

- Corriger la syntaxe `--forward-to` (voir page installation).
- Vérifier la liste des handlers intégrés vs. la liste YAML `events:` (cohérence).

### Ajouts

**Configuration production** :

- Créer le webhook via `cashier:webhook --url=https://monsite.com/cashier/webhook --show-secret`.
- Copier le secret dans la variable `STRIPE_WEBHOOK_SECRET`.
- Vérifier que le path correspond à `cashier.path` dans `cashier.yaml`.

**Idempotence** :

- Les handlers Stripe peuvent être appelés plusieurs fois avec le même event ID.
- Utiliser `$event->id` comme guard : stocker les IDs traités, rejeter les doublons.

**Gestion des exceptions** :

- Si un handler lance une exception, Stripe retentera le webhook (3 jours, intervalles croissants).
- Capturer les exceptions métier et retourner 200 si l'événement peut être ignoré.

**Handlers placeholder** :

- `InvoicePaymentActionRequiredHandler` et `CheckoutSessionCompletedHandler` sont des points d'extension vides — implémenter via un handler custom.

---

## Page 11 : `events/page.mdx`

### Corrections

- Ajouter un tableau récapitulatif des événements avec **toutes** leurs propriétés.

### Ajouts

**Tableau des propriétés de chaque Event** :

| Event                      | Méthodes disponibles                                                                                                                                                              |
| -------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `PaymentSucceededEvent`    | `getCustomerId()`, `getPaymentIntentId()`, `getAmount()` (centimes), `getCurrency()`, `getInvoiceId(): ?string`, `getCheckoutSessionId(): ?string`, `getAmountInDecimal(): float` |
| `PaymentFailedEvent`       | `getCustomerId()`, `getPaymentIntentId()`, `getAmount()`, `getCurrency()`, `getAmountInDecimal(): float`                                                                          |
| `SubscriptionCreatedEvent` | `getSubscription(): Subscription`                                                                                                                                                 |
| `SubscriptionUpdatedEvent` | `getSubscription(): Subscription`                                                                                                                                                 |
| `SubscriptionDeletedEvent` | `getSubscription(): Subscription`                                                                                                                                                 |
| `WebhookReceivedEvent`     | `getEvent(): \Stripe\Event`                                                                                                                                                       |
| `WebhookHandledEvent`      | `getEvent(): \Stripe\Event`                                                                                                                                                       |

**Intégration Messenger** (nouvelle section) :

- Les events Symfony peuvent déclencher des messages async.
- Messages disponibles : `CancelSubscriptionMessage`, `ProcessInvoiceMessage`, `RetryPaymentMessage`, `SyncCustomerDetailsMessage`, `UpdateSubscriptionQuantityMessage`.
- Nécessite `symfony/messenger` dans `composer.json`.
- Exemple : déclencher `SyncCustomerDetailsMessage` depuis un listener `CustomerUpdatedEvent`.

---

## Page 12 : `coupons/page.mdx`

### Corrections

- **Bug dans l'exemple `CouponValidator`** : la méthode `code()` n'existe que sur `PromotionCode`, pas sur `Coupon`. Corriger l'exemple pour utiliser `$coupon->id()` ou basculer vers `PromotionCode`.
- **Injection de `SubscriptionBuilder`** : `SubscriptionBuilder` n'est pas un service Symfony singleton — il est créé via `$user->newSubscription()` ou `SubscriptionService::newSubscription()`. Corriger les exemples en conséquence.

### Ajouts

- Documenter l'application d'un coupon à un abonnement **existant** (via `SubscriptionService::update()` avec `coupon: 'COUPON_ID'`).
- Documenter la vérification d'un coupon avant application (`$coupon->valid()`).

---

## Page 13 : `taxes/page.mdx`

### Corrections

- **`TaxService::setAutomaticTaxEnabled()`** : clarifier que cette méthode configure uniquement la valeur locale utilisée lors de la création des sessions Checkout et PaymentIntents via ce bundle — ce n'est pas un paramètre global Stripe Dashboard. La taxation automatique Stripe se configure séparément dans le Dashboard Stripe.
- Clarifier que `calculate()` retourne le tableau brut de l'API Stripe Tax (lien vers la doc Stripe pour la structure).

### Ajouts

- Ajouter section **Stripe Tax vs. Tax Rates manuels** : deux approches distinctes.
  - Stripe Tax (automatique, basé sur l'adresse) : `automatic_tax: { enabled: true }` dans les options Checkout.
  - Tax Rates manuels : `createTaxRate()` + `attachTaxRatesToPrice()`.
- Documenter `automatic_tax` dans le contexte Checkout :
  ```php
  $checkoutService->createSubscription($user, $items, [
      'automatic_tax' => ['enabled' => true],
  ]);
  ```

---

## Page 14 : `commands/page.mdx`

### Corrections

- **`cashier:webhook:listen --forward-to`** : corriger la syntaxe (l'option prend une URL complète).
- **`cashier:report-usage 1`** : préciser que `1` est l'ID **local** (clé primaire) de l'entité `SubscriptionItem` en base.

### Ajouts

- Ajouter `cashier:cleanup-sessions` dans la liste (même si placeholder, la commande existe) avec un avertissement « non fonctionnelle sans entité `CheckoutSession` ».
- Documenter les événements Stripe par défaut enregistrés par `cashier:webhook` (liste complète).

---

## Page 15 : `twig/page.mdx`

### Ajouts

- Documenter comment surcharger le template de facture par défaut (`@Cashier/invoice/default.html.twig`) :
  ```
  templates/bundles/CashierBundle/invoice/default.html.twig
  ```
- Documenter l'appel PHP direct `Cashier::formatAmount(int $amount, string $currency, string $locale)`.
- Lister les variables disponibles dans le template de facture (issues de `InvoiceViewFactory::create()`) :
  - `meta` : locale, labels de traduction
  - `invoice` : id, number, status, dates, montants, items, discounts, taxes
  - `customer` : name, email, adresse
  - `company` : name, address, email, phone
  - `footer` : texte de pied de page
- Documenter les locales supportées (`en`, `fr`) et la possibilité d'implémenter `InvoiceTranslationProviderInterface`.

---

## Page 16 : `api/page.mdx`

### Corrections

- Ajouter `PaymentMethodService` et `TaxService` à la liste des services.

### Ajouts

- Ajouter `PaymentIntentService` (service bas niveau) avec ses méthodes clés.
- Ajouter `SetupIntentService` avec ses méthodes clés.
- Ajouter `InvoiceArchiveService` avec sa méthode principale.
- Ajouter les **modèles** manquants : `CustomerBalanceTransaction`, `InvoicePayment`, `InvoiceLineItem`, `Tax`, `TaxRate`, `StoredInvoice`.
- Ajouter section **Value Objects** : `StoredInvoice`.
- Ajouter section **Messages Messenger** avec leurs constructeurs.
- Ajouter des liens vers les pages thématiques dans chaque section.
- Ajouter une note sur la **politique de stabilité** : les services publics, interfaces, entités et events sont l'API stable. Les classes dans `src/Webhook/Handler/`, `src/Infrastructure/`, `src/DependencyInjection/` sont internes.

---

## Fichier : `upgrade-to-v1.0.0.fr.md` (nouveau)

Le guide de migration existant (`upgrade-to-v1.0.0.en.md`) est en anglais — toute la doc est en français. Créer la version française avec :

- Traduction complète du guide anglais
- Ajout : liste des migrations Doctrine nécessaires (table `cashier_generated_invoices` est nouvelle en v1.0.0)
- Ajout : mention de la suppression éventuelle de dépendances v0.x

---

## Fichiers critiques à modifier

```
docs/app/docs/introduction/page.mdx
docs/app/docs/installation/page.mdx
docs/app/docs/configuration/page.mdx
docs/app/docs/customers/page.mdx
docs/app/docs/subscriptions/page.mdx
docs/app/docs/payments/page.mdx
docs/app/docs/payment-methods/page.mdx
docs/app/docs/invoices/page.mdx
docs/app/docs/checkout/page.mdx
docs/app/docs/webhooks/page.mdx
docs/app/docs/events/page.mdx
docs/app/docs/coupons/page.mdx
docs/app/docs/taxes/page.mdx
docs/app/docs/commands/page.mdx
docs/app/docs/twig/page.mdx
docs/app/docs/api/page.mdx
docs/upgrade-to-v1.0.0.fr.md  (nouveau)
```

Sources de référence (ne pas modifier) :

```
src/Contract/BillableInterface.php
src/Contract/BillableEntityInterface.php
src/Service/SubscriptionBuilder.php
src/Concerns/ManagesInvoices.php
src/DependencyInjection/Configuration.php
composer.json
```

---

## Stratégie d'exécution (TeamCreate)

16 pages à modifier en parallèle → 5 agents thématiques :

| Agent            | Pages                                     |
| ---------------- | ----------------------------------------- |
| A1 — Setup       | introduction, installation, configuration |
| A2 — Entités     | customers, subscriptions                  |
| A3 — Paiements   | payments, payment-methods                 |
| A4 — Facturation | invoices, checkout, coupons               |
| A5 — Async/Infra | webhooks, events, commands, twig, api     |

## Vérification finale

```bash
# Vérifier que le site doc compile sans erreur
cd docs && bun run build
```

Objectif : zéro régression sur le build, couverture complète de toutes les features publiques du package.
