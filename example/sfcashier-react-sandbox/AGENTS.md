# SFCashier – Agent rules

## Scope

- **API** : `api/` — Symfony 8, API Platform 4, SQLite (dev).
- **Web** : `web/` — React 19, Vite, shadcn/ui, Tailwind.

## Règles auto-chargées

| Règle        | Trigger           | Fichier                         |
| ------------ | ----------------- | ------------------------------- |
| API Platform | Entités, CRUD     | `.claude/rules/api-platform.md` |
| React SPA    | Composants, pages | `.claude/rules/react-spa.md`    |
| Symfony .env | .env, config api  | `.claude/rules/env-symfony.md`  |
| API Response | Contrat JSON API  | `.claude/rules/api-response-contract.md` |
| SOLID/SRP    | Controller, Service | `.claude/rules/solid-symfony.md` |

## Env (api)

Une seule source : **`api/.env`**. Pas de `.env.local` / `.env.dev` — tout est dans `.env`. Voir `.claude/rules/env-symfony.md`.

## Commandes rapides

```bash
# API
cd api && composer install && php bin/console doctrine:database:create && php bin/console doctrine:schema:create && php bin/console doctrine:fixtures:load && symfony server:start --port=8000 --no-tls

# Web
cd web && bun install && bun run dev
```

## Référence

- Stack et commandes détaillées : `CLAUDE.md`.

## Note checkout/orders

- Toutes les réponses JSON des contrôleurs custom (`OrderController`, `CartController`, `Auth*`) doivent passer par `App\Api\ApiResponseHelper`.
- Les collections API doivent toujours retourner `hydra:member` et `hydra:totalItems`.
