# SFCashier - Micro Ecommerce

## Stack

- **API** : Symfony 8 + API Platform 4 + SQLite
- **Web** : React 19 + Vite + shadcn/ui + Tailwind CSS

## Commandes

### API (port 8000)

```bash
cd api
composer install
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load
symfony server:start --port=8000 --no-tls
```

### Web (port 5173)

```bash
cd web
bun install
bunx shadcn@latest init
bunx shadcn@latest add button card input dialog table
bun run dev
```

## Règles Auto-chargées

| Règle        | Trigger           | Fichier                         |
| ------------ | ----------------- | ------------------------------- |
| API Platform | Entités, CRUD     | `.claude/rules/api-platform.md` |
| React SPA    | Composants, pages | `.claude/rules/react-spa.md`    |
| Symfony .env | .env, config api  | `.claude/rules/env-symfony.md`  |
| API Response | Contrat JSON API  | `.claude/rules/api-response-contract.md` |

## Note importante

- Sur l'API Symfony, les contrôleurs custom doivent utiliser `App\Api\ApiResponseHelper` pour un contrat de réponse uniforme (Hydra + erreurs).
