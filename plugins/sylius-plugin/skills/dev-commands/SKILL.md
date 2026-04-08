---
name: dev-commands
description: Daily development commands for Sylius plugins (Docker, database, assets, QA)
allowed-tools: Bash, Read
---

# Dev Commands — Sylius Plugin

All commands go through `make`. The `ENV` variable determines which environment (and database) is used — it defaults to `dev`.

```bash
make <command>            # → ENV=dev (default), sylius_dev database
ENV=test make <command>   # → sylius_test database
```

Only specify `ENV=test` explicitly when targeting the test environment.

---

## Docker

| Action | Command |
|--------|---------|
| Start | `make up` |
| Stop | `make down` |
| Stop + remove volumes | `make clean` |

---

## Symfony Console

For commands not covered by a make target, run via docker compose:

```bash
docker compose run --rm php vendor/bin/console <cmd>
```

Common examples:
```bash
docker compose run --rm php vendor/bin/console cache:clear
docker compose run --rm php vendor/bin/console doctrine:migrations:migrate --no-interaction
docker compose run --rm php vendor/bin/console doctrine:migrations:diff --namespace=DoctrineMigrations
docker compose run --rm php vendor/bin/console debug:container <service>
docker compose run --rm php vendor/bin/console debug:router
```

---

## Database

| Action | Command |
|--------|---------|
| Create + migrate | `make database-init` |
| Drop + create + migrate | `make database-reset` |
| Load fixtures | `make load-fixtures` |

For the test database (required before running Behat):
```bash
ENV=test make database-init
```

---

## Assets (Stimulus / Webpack)

Standard build (after JS changes only):
```bash
docker compose run --rm nodejs
```

Forced build (after `package.json` changes or adding a controller):
```bash
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn install --force && yarn build"
```

> `--force` is needed when `package.json` changes: yarn won't re-copy a package if the version hasn't changed.

Watch (dev):
```bash
docker compose run --rm -i nodejs "cd vendor/sylius/test-application && yarn watch"
```

> `make node-watch` uses `npm run watch` — does not work. Use the command above instead.

---

## SQL Queries

Via MySQL container (recommended for exploration):
```bash
docker compose exec mysql mysql -uroot sylius_dev -e "SELECT * FROM my_table LIMIT 5;"
```

For the test database:
```bash
docker compose exec mysql mysql -uroot sylius_test -e "SELECT * FROM my_table LIMIT 5;"
```

Via Doctrine (tabular output):
```bash
docker compose run --rm php vendor/bin/console doctrine:query:sql "SELECT * FROM my_table LIMIT 5"
```

> Credentials: user `root`, no password.

---

## QA

```bash
make phpstan        # Static analysis
make ecs            # Coding standards
make phpunit        # Unit tests
ENV=test make behat # Functional tests (requires test database)
```