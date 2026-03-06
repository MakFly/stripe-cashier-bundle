# Symfony .env (api/)

## Fichier utilisé

Dans `api/`, une **seule** source de vérité : **`.env`**. Pas de `.env.local` ni `.env.dev` — tout est fusionné dans `.env`.

## Ordre de priorité (si tu réintroduis des fichiers plus tard)

Symfony charge dans cet ordre, le dernier écrase :

1. `.env` (base)
2. `.env.local` (ignoré en env `test`)
3. `.env.{APP_ENV}` (ex. `.env.dev`)
4. `.env.{APP_ENV}.local`

Pour savoir quelles variables sont effectivement chargées :

```bash
cd api && php bin/console debug:dotenv
```

## Règle

- Ne pas créer `.env.local` / `.env.dev` sans raison (ex: secrets locaux non versionnés). Par défaut, tout va dans `api/.env`.
- Les variables custom app (APP*URL, FRONTEND_URL, BETTER_AUTH*\*, etc.) vivent dans la section `###> app custom ###` / `###< app custom ###` dans `.env`.
