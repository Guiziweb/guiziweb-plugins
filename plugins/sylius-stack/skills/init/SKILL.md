---
name: init
description: Bootstrap a new Symfony project with the Sylius Stack (Bootstrap Admin UI, Resource, Grid, Twig Hooks)
argument-hint: "[project_directory]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Init — Sylius Stack

Ask the user for the project directory name if not provided.

## 1. Create the Symfony project

```bash
composer create-project symfony/skeleton:"8.0.*" {project_directory}
cd {project_directory}
composer require webapp
```

## 2. Allow contrib recipes

```bash
composer config extra.symfony.allow-contrib true
```

## 3. Install Sylius Stack packages

```bash
composer require -W \
  doctrine/orm \
  doctrine/doctrine-bundle \
  pagerfanta/doctrine-orm-adapter \
  symfony/asset-mapper \
  sylius/bootstrap-admin-ui \
  sylius/ui-translations
```

When prompted by Symfony Flex, type `a` or `p` to apply all recipes automatically.

## 4. Configure the app secret

In `.env`, set a real secret:

```dotenv
APP_SECRET=UseYourOwnSecretPlease
```

## 5. Configure AssetMapper

Since `sylius/bootstrap-admin-ui` auto-initializes Stimulus and injects its own stylesheets, you need to take control of that to avoid duplicate Ajax calls.

Disable the default Stimulus app and Symfony UX stylesheets, and register your own JS hook in `config/packages/sylius_bootstrap_admin_ui.yaml` :

```yaml
# config/packages/sylius_bootstrap_admin_ui.yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.base#stylesheets':
            symfony_ux:
                enabled: false
        'sylius_admin.base#javascripts':
            app:
                priority: 200
                template: 'base/javascripts/app.html.twig'
            symfony_ux:
                enabled: false
```

Create the template that loads your AssetMapper entry point:

```twig
{# templates/base/javascripts/app.html.twig #}
{{ importmap('app') }}
```

Make sure your Stimulus app is started in `assets/bootstrap.js`:

```js
// assets/bootstrap.js
import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
```

And imported in `assets/app.js`:

```js
// assets/app.js
import './bootstrap.js';
```

## 6. Start the stack and configure the database URL

```bash
docker compose up -d
```

Retrieve the dynamic port assigned by Docker and write the correct `DATABASE_URL` to `.env.local`:

```bash
docker compose port database 5432
```

Create or update `.env.local` with the correct port:

```dotenv
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:{port}/app?serverVersion=16&charset=utf8"
```

Then create the database and start the web server:

```bash
bin/console doctrine:database:create --if-not-exists
symfony serve -d
```

## 7. Display setup summary

| Service  | URL                             |
|----------|---------------------------------|
| App      | https://127.0.0.1:8000          |
| Admin    | https://127.0.0.1:8000/admin    |
| Database | PostgreSQL via `docker compose` |

Next steps:
- Run `/sylius-stack:add-resource` to create your first resource
- Run `/sylius-stack:add-menu` to add it to the admin sidebar