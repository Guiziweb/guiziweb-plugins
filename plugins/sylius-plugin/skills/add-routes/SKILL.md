---
name: add-routes
description: Add Sylius admin routes for an existing Resource with a Grid
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add admin Routes for a Sylius Resource

Ask the user for the ModelName if not provided.

**Prerequisites:**
- Model exists as a Sylius Resource (`/sylius:add-model`)
- Grid is configured (`/sylius:add-grid`)

## 1. Add the routes

Edit `config/routes/admin.yaml` (create if missing). Append a new resource block:

```yaml
${SYLIUS_PREFIX}_admin_{model_snake}:
    resource: |
        alias: ${SYLIUS_PREFIX}.{model_snake}
        section: admin
        templates: '@SyliusAdmin/shared/crud'
        except: ['show']
        redirect: update
        grid: ${SYLIUS_PREFIX}_admin_{model_snake}
        vars:
            all:
                subheader: ${SYLIUS_PREFIX}.ui.manage_{model_snake_plural}
            index:
                icon: 'tabler:list'
    type: sylius.resource
    prefix: /admin
```

The top-level key and the `grid:` value must match the grid name from `/sylius:add-grid`. Remove `except: ['show']` to enable the show route (also requires a show template).

## 2. Ensure the file is imported

`config/routes/*.yaml` files are auto-loaded by Symfony. Usually nothing to do — verify via §3 before adding any import.

## 3. Clear cache

```bash
$SYLIUS_CONSOLE cache:clear
```

## 4. Verify

- [ ] `$SYLIUS_CONSOLE debug:router | grep {model_snake}` lists 4 routes (`_index`, `_create`, `_update`, `_delete`) — plus `_show` if the show action was enabled

## Next steps

- Add admin menu entry → `/sylius:add-menu`
- Add translations for `${SYLIUS_PREFIX}.ui.manage_{model_snake_plural}` in the project's default translations file (run `$SYLIUS_CONSOLE debug:container --parameter=kernel.default_locale` to get the locale)
