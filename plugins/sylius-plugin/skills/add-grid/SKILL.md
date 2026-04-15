---
name: add-grid
description: Add a Sylius admin grid for an existing Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add an admin Grid for a Sylius Resource

Ask the user for the ModelName if not provided. Read `composer.json` and `src/**/Entity/{ModelName}.php` to detect the plugin namespace, plugin alias, and the entity's fields.

**Prerequisite:** the model must already exist as a Sylius Resource (run `add-model` first).

---

## 1. Configure the Grid

Create or update `config/grids.yaml`:

```yaml
sylius_grid:
    grids:
        {plugin_alias}_admin_{model_snake}:
            driver:
                name: doctrine/orm
                options:
                    class: "%{plugin_alias}.model.{model_snake}.class%"
            fields:
                # add fields based on the entity properties
                # field types: string, twig, datetime, money
                # use sylius.ui.* translation keys for common labels
            actions:
                main:
                    create:
                        type: create
                item:
                    update:
                        type: update
                    delete:
                        type: delete
```

Read the entity fields and add appropriate grid columns for each one. Common field configurations:

```yaml
# Simple string column
name:
    type: string
    label: sylius.ui.name

# Boolean with icon rendering
enabled:
    type: twig
    label: sylius.ui.enabled
    options:
        template: "@SyliusUi/Grid/Field/enabled.html.twig"

# DateTime column
createdAt:
    type: datetime
    label: sylius.ui.created_at
    options:
        format: 'd-m-Y H:i:s'
```

---

## 2. Verify

```bash
docker compose exec php vendor/bin/console cache:clear
docker compose exec php vendor/bin/console debug:config sylius_grid | grep {model_snake}
```

---

## Next steps

- Add admin routes referencing this grid → run `/sylius-plugin:add-routes`
- Add admin menu entry → run `/sylius-plugin:add-menu`
