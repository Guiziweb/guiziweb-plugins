---
name: extends-grid
description: Customize an existing Sylius admin grid (hide/reorder/modify/add fields, filters, actions)
argument-hint: "[grid_alias]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Extend an existing Sylius Grid

Customize a Sylius core grid (e.g. `sylius_admin_product`, `sylius_admin_customer`, `sylius_admin_product_review`) without replacing it. Sylius merges the override on top of the inherited definition — only declare the keys you change.

**Not for new grids** — use `/sylius:add-grid` instead.

## 1. Identify the target grid

Ask the user for the grid alias if not provided. Inspect the current definition:

```bash
$SYLIUS_CONSOLE sylius:debug:grid {alias}
```

Run without an argument to get an interactive list of all registered grids — useful when the exact alias is unknown.

Read the output before writing: override keys must exactly match existing field/filter/action names. Setting `enabled: false` on a key that does not exist is a silent no-op.

## 2. Write the override

File location matches the rest of the project: `config/packages/grids/{alias}.yaml` (e.g. `sylius_admin_product.yaml`). Naming the file after the alias keeps overrides self-documenting.

Assumes `config/packages/_sylius.yaml` already imports `grids/*.yaml` at its top. If not, run `/sylius:add-grid` once to scaffold it.

Only include the keys being modified — everything else stays inherited.

### Hide a field / filter / action

```yaml
sylius_grid:
    grids:
        sylius_admin_product_review:
            fields:
                title:
                    enabled: false
            filters:
                title:
                    enabled: false
            actions:
                item:
                    delete:
                        enabled: false
```

### Reorder fields

`position: N` controls display order (lower = earlier). Declare it on the fields to reorder.

```yaml
sylius_grid:
    grids:
        sylius_admin_product_review:
            fields:
                status:
                    position: 1
                rating:
                    position: 2
                author:
                    position: 3
```

### Change a label

```yaml
sylius_grid:
    grids:
        sylius_admin_product_review:
            fields:
                date:
                    label: ${SYLIUS_PREFIX}.ui.product_review.date
```

### Override an action's route or options

```yaml
sylius_grid:
    grids:
        sylius_admin_product:
            actions:
                item:
                    show:
                        type: show
                        label: sylius.ui.show_in_shop
                        options:
                            link:
                                route: sylius_shop_product_show
                                parameters:
                                    slug: resource.slug
```

### Add a new field (or filter, or action)

Declare it as in `add-grid` — Sylius merges the new entry into the inherited grid. Typical case: a plugin has run `/sylius:extends-model` to add a column to a core entity, and wants to expose it on the core grid.

```yaml
sylius_grid:
    grids:
        sylius_admin_product:
            fields:
                myFlag:
                    type: twig
                    label: ${SYLIUS_PREFIX}.ui.product.my_flag
                    path: myFlag
                    options:
                        template: '@SyliusUi/Grid/Field/enabled.html.twig'
                    position: 50
```

If the property lives on a trait / extension added via `extends-model`, access it through its getter — the grid uses PropertyAccess like any other field.

## 3. PHP event listener (dynamic cases only)

YAML covers hide / reorder / modify / add. Use an event listener only when:

- The modification depends on runtime state (environment, channel, user context, feature flag)
- The decision requires reading the grid's current state (e.g. act only if a given field is present)
- The change is conditional in a way that cannot be expressed statically

Sylius dispatches `sylius.grid.{stripped_alias}` during grid conversion, where `{stripped_alias}` is the grid alias with the `sylius_` prefix removed (`ArrayToDefinitionConverter::getEventName()`). For `sylius_admin_product` the event is `sylius.grid.admin_product`.

`src/Grid/AdminProductGridListener.php`:

```php
<?php

declare(strict_types=1);

namespace $SYLIUS_NAMESPACE\Grid;

use Sylius\Component\Grid\Definition\Field;
use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;

final class AdminProductGridListener
{
    public function __invoke(GridDefinitionConverterEvent $event): void
    {
        $grid = $event->getGrid();

        $grid->removeField('image');

        $field = Field::fromNameAndType('variantSelectionMethod', 'string');
        $field->setLabel('${SYLIUS_PREFIX}.ui.product.variant_selection');
        $grid->addField($field);
    }
}
```

Register it in `config/services.yaml`:

```yaml
services:
    ${SYLIUS_PREFIX}.listener.grid.{stripped_alias}:
        class: $SYLIUS_NAMESPACE\Grid\{ListenerClassName}
        tags:
            - { name: kernel.event_listener, event: sylius.grid.{stripped_alias} }
```

`{stripped_alias}` is the grid alias with the `sylius_` prefix removed (e.g. `admin_product` for `sylius_admin_product`). `{ListenerClassName}` is the listener class (e.g. `AdminProductGridListener`).

`$grid->addField()` throws `LogicException` if the field already exists — check with `$grid->hasField()` first when another bundle may have added it.

## 4. Clear cache

```bash
$SYLIUS_CONSOLE cache:clear
```

## 5. Verify

- [ ] `$SYLIUS_CONSOLE sylius:debug:grid {alias}` reflects all overrides (new fields present, hidden ones marked disabled, reordered positions applied)

## Next steps

- Paired with `/sylius:extends-model` when adding a field to a core entity and exposing it here
- For a brand-new grid on a plugin resource, use `/sylius:add-grid`
