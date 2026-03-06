# API Response Contract (Hydra + ApiResponseHelper)

## Objectif

Garantir un contrat de réponse cohérent entre `api/` et `web/`.

## Règles obligatoires

1. Dans les contrôleurs Symfony custom, utiliser `App\Api\ApiResponseHelper` pour tous les retours JSON (`apiResponse`, `apiResource`, `apiCollection`, `apiError`).
2. Pour les collections, respecter Hydra (`hydra:member`, `hydra:totalItems`).
3. Côté front, normaliser les collections via `web/src/lib/api.ts` avant usage UI.
4. Ne jamais mélanger des formats concurrents dans le rendu (ex: `member` sans fallback Hydra).

## Checklist rapide

- Collection = `hydra:member` + `hydra:totalItems`.
- Ressource = `@id`, `@type`, données.
- Erreur = payload `hydra:Error` cohérent.
- Pages React lisent uniquement le format normalisé par le client API.
