# Contributing

## Pré-requis
- PHP 8.2+
- Composer

## Workflow recommandé

```bash
composer install
composer run build-data
composer run check-data
composer run doctor
composer run analyse
composer test
```

## Règles
- Ne pas modifier manuellement les fichiers générés sans relancer le pipeline.
- Vérifier que `git diff --exit-code` reste propre après `composer run build-data`.
- Ajouter des tests de non-régression quand le dataset change.
