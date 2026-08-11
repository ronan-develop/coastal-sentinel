# Commandes utiles

## Base de données (Docker MariaDB)

```bash
docker compose up -d database                          # démarrer le conteneur
docker compose down                                     # arrêter le conteneur
php bin/console doctrine:database:create --if-not-exists            # créer la base dev
php bin/console doctrine:database:create --env=test --if-not-exists # créer la base test
php bin/console doctrine:migrations:migrate              # appliquer les migrations
php bin/console make:migration                           # générer une migration (après modif d'entité)
```

Connexion dev : `127.0.0.1:3307`, base `coastal_sentinel`, user `app` — voir
`compose.yaml`. Identifiants réels dans `.env.local` / `.env.test.local`
(non versionnés), reportés dans `.secrets`.

## JWT

```bash
php bin/console lexik:jwt:generate-keypair --skip-if-exists
```

## Cache

```bash
php bin/console cache:clear
```

## Tests

```bash
php bin/phpunit                        # tous les tests
php bin/phpunit --filter NomDuTest    # test ciblé
```

## Divers

```bash
php bin/console about   # infos environnement (version Symfony, PHP, cache...)
composer install        # installer les dépendances
```
