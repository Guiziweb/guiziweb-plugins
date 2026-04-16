---
name: dev-commands
description: Daily development commands for Sylius Stack apps (database, cache, assets, QA)
allowed-tools: Bash, Read
---

# Dev Commands — Sylius Stack

Docker is used **only for the PostgreSQL database**. PHP runs locally via `symfony serve`.

## Database (Docker)

| Action | Command |
|--------|---------|
| Start | `docker compose up -d` |
| Stop | `docker compose down` |
| Stop + remove volumes | `docker compose down -v` |

## Web server

```bash
symfony serve -d    # start in background
symfony serve:stop  # stop
```

## Symfony console

```bash
bin/console cache:clear
bin/console debug:router
bin/console debug:container <service>
bin/console sylius:debug:resource 'App\Entity\{ModelName}'
```

## Database migrations

| Action | Command |
|--------|---------|
| Generate migration | `bin/console make:migration` |
| Apply migrations | `bin/console doctrine:migrations:migrate --no-interaction` |
| Drop + create + migrate | `bin/console doctrine:database:drop --force && bin/console doctrine:database:create && bin/console doctrine:migrations:migrate --no-interaction` |
| Load fixtures | `bin/console doctrine:fixtures:load --no-interaction` |

## Assets (AssetMapper)

```bash
bin/console importmap:install       # install JS packages
bin/console asset-map:compile       # production build
```

## QA

```bash
vendor/bin/phpstan analyse    # static analysis
vendor/bin/ecs check          # coding standards
vendor/bin/phpunit            # unit tests
```