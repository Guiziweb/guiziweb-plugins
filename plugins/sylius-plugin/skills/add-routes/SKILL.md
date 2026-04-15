---
name: add-routes
description: Add Sylius admin routes for an existing Resource with a Grid
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add admin Routes for a Sylius Resource

Ask the user for the ModelName if not provided. Read `composer.json` to detect the plugin alias.

**Prerequisites:**
- Model exists as a Sylius Resource (`add-model`)
- Grid is configured (`add-grid`)

---

## 1. Add routes to `config/routes/admin.yaml`

Create the file if it doesn't exist, otherwise add the new resource routes:

```yaml
{plugin_alias}_admin_{model_snake}:
    resource: |
        alias: {plugin_alias}.{model_snake}
        section: admin
        templates: "@SyliusAdmin\\shared\\crud"
        except: ['show']
        redirect: update
        grid: {plugin_alias}_admin_{model_snake}
        vars:
            all:
                subheader: {plugin_alias}.ui.managing_{model_snake_plural}
            index:
                icon: 'tabler:list'
    type: sylius.resource
    prefix: /admin
```

> The route name `{plugin_alias}_admin_{model_snake}` must match the grid name defined in `add-grid`.

---

## 2. Verify

```bash
docker compose exec php vendor/bin/console cache:clear
docker compose exec php vendor/bin/console debug:router | grep {model_snake}
```

Expected routes:
```
{plugin_alias}_admin_{model_snake}_index
{plugin_alias}_admin_{model_snake}_create
{plugin_alias}_admin_{model_snake}_update
{plugin_alias}_admin_{model_snake}_delete
```

---

## Next steps

- Add admin menu entry → run `/sylius-plugin:add-menu`
