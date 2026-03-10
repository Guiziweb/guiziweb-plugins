---
name: dev-commands
description: Commandes du quotidien pour développer sur un plugin Sylius (Docker, BDD, assets, QA)
allowed-tools: Bash, Read
---

# Commandes de développement — Plugin Sylius

Toutes les commandes passent par Docker. Le container PHP s'appelle `php`.

---

## Containers

| Action | Commande |
|--------|----------|
| Démarrer | `make up` |
| Arrêter | `make down` |
| Arrêter + supprimer volumes | `make clean` |
| Shell PHP | `make php-shell` |
| Shell Node | `make node-shell` |

---

## Symfony Console

```bash
docker compose exec php vendor/bin/console <cmd>
```

Exemples courants :
```bash
docker compose exec php vendor/bin/console cache:clear
docker compose exec php vendor/bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php vendor/bin/console doctrine:migrations:diff --namespace=DoctrineMigrations
docker compose exec php vendor/bin/console messenger:consume <transport> --limit=10 -vv
docker compose exec php vendor/bin/console debug:container <service>
docker compose exec php vendor/bin/console debug:router
```

---

## Base de données

| Action | Commande |
|--------|----------|
| Créer + migrer | `make database-init` |
| Drop + créer + migrer | `make database-reset` |
| Charger les fixtures | `make load-fixtures` |

---

## Assets (Stimulus / Webpack)

Build standard (après modif JS uniquement) :
```bash
docker compose run --rm nodejs
```

Build forcé (après modif `package.json`, ajout d'un controller) :
```bash
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn install --force && yarn build"
```

> `--force` est nécessaire quand `package.json` change : yarn ne re-copie pas le package si la version n'a pas changé.

Watch (dev) :
```bash
docker compose run --rm -i nodejs "cd vendor/sylius/test-application && yarn watch"
```

> `make node-watch` dans le Makefile utilise `npm run watch` — ne fonctionne pas. Utiliser la commande ci-dessus.

---

## Base de données — Requêtes SQL

Via le container MySQL (recommandé pour explorer) :
```bash
docker compose exec mysql mysql -uroot sylius_dev -e "SELECT * FROM ma_table LIMIT 5;"
```

Via Doctrine (affichage tabulaire) :
```bash
docker compose exec php vendor/bin/console doctrine:query:sql "SELECT * FROM ma_table LIMIT 5"
```

> Credentials : user `root`, pas de mot de passe. Base : `sylius_dev` (dev) ou `sylius_test` (test).

---

## QA

```bash
make phpstan    # Analyse statique
make ecs        # Coding standards
make phpunit    # Tests unitaires
make behat      # Tests fonctionnels
```