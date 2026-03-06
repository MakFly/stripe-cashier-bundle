# Plan : Enrichir le copywriting de la landing page

## Contexte

La landing page (`docs/app/[lang]/page.tsx`) n'affiche que 3 features (abonnements, facturation, webhooks) alors que le package en propose bien plus. On veut ajouter des features supplémentaires pour mieux représenter l'étendue du bundle.

## Modification

**Fichier** : `docs/app/[lang]/page.tsx`

### Ajouter 3 features supplémentaires dans `dict.fr.features` et `dict.en.features`

Passer de 3 à 6 features dans la grille (numérotées 01-06) :

| #      | FR                       | EN                      | Source code                                                         |
| ------ | ------------------------ | ----------------------- | ------------------------------------------------------------------- |
| 01     | Abonnements récurrents   | Recurring subscriptions | `ManagesSubscriptions`                                              |
| 02     | Facturation automatique  | Automatic billing       | `ManagesInvoices`                                                   |
| 03     | Webhooks sécurisés       | Secure webhooks         | `VerifyWebhookSignatureMiddleware`                                  |
| **04** | **Paiements & Checkout** | **Payments & Checkout** | `PerformsCharges` — charge, refund, Stripe Checkout, portail client |
| **05** | **Gestion des clients**  | **Customer management** | `ManagesCustomer` — sync Stripe, création auto, mise à jour         |
| **06** | **Méthodes de paiement** | **Payment methods**     | `ManagesPaymentMethods` — ajout, défaut, listing par type           |

### Adapter la grille CSS

Passer de `grid-template-columns: repeat(3, 1fr)` à une grille 3x2 (déjà compatible, la grille wrap naturellement avec 6 items et le `repeat(3, 1fr)` existant).

Ajouter `border-bottom` entre les deux rangées de features (les 3 premiers items de la grille auront un `borderBottom`).

## Copywriting proposé

### FR

- **04 — Paiements & Checkout** : "Encaissez des paiements uniques ou redirigez vers Stripe Checkout. Remboursements et portail client en une ligne."
- **05 — Gestion des clients** : "Synchronisez vos utilisateurs Doctrine avec Stripe. Création à la volée, mise à jour et récupération du profil client."
- **06 — Méthodes de paiement** : "Ajoutez, listez et définissez la méthode de paiement par défaut. Support multi-types via l'API Stripe."

### EN

- **04 — Payments & Checkout** : "Collect one-time payments or redirect to Stripe Checkout. Refunds and customer portal in a single line."
- **05 — Customer management** : "Sync your Doctrine users with Stripe. On-the-fly creation, updates and customer profile retrieval."
- **06 — Payment methods** : "Add, list and set the default payment method. Multi-type support through the Stripe API."

## Vérification

- `bun run dev` sur le dossier `docs/` et vérifier `/fr` et `/en`
- Vérifier le responsive mobile (grille 1 colonne)
- Vérifier que les 6 cards s'affichent correctement en 3x2
