---
name: add-grid
description: Create an admin grid class (fields, filters, actions) for an existing Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Grid — Sylius Stack

Ask the user for the ModelName if not provided.

This skill creates a standalone Grid class. It does not attach the grid to the resource — run `/sylius-stack:add-operation Index` afterwards to expose it as the index page.

---

## 1. Read the entity

Read `src/Entity/{ModelName}.php` to retrieve the fields with their names and Doctrine types.

## 2. Ask the user which fields, filters and actions to include

Based on the entity fields, ask the user:
- Which fields to display as columns
- Which fields to add as filters
- Whether to include a `Show` action on items

## 3. Create the Grid class

Create `src/Grid/Admin{ModelName}Grid.php`.

**Grid name convention:** `app_admin_{model_snake}` (e.g. `Book` → `app_admin_book`)

### Fields

| Doctrine type            | Grid field                        | Notes                                                           |
|--------------------------|-----------------------------------|-----------------------------------------------------------------|
| `string`, `text`         | `StringField`                     | add `->setSortable(true)` when useful                           |
| `datetime`, `date`       | `DateTimeField`                   | default format `Y-m-d H:i:s`                                   |
| `integer`, `float`       | `StringField`                     | plain display                                                   |
| `boolean`                | `TwigField`                       | use `@SyliusBootstrapAdminUi/shared/grid/field/boolean.html.twig` |
| complex / multi-property | `TwigField` with `->setPath('.')` | gives access to full object in template via `{{ data.xxx }}`   |

**StringField:**
```php
StringField::create('title')
    ->setLabel('app.ui.{model_snake}.title')
    ->setSortable(true),
```

**DateTimeField:**
```php
DateTimeField::create('createdAt', 'Y-m-d', null)
    ->setLabel('app.ui.{model_snake}.created_at'),
```

**TwigField — boolean:**
```php
TwigField::create('enabled', '@SyliusBootstrapAdminUi/shared/grid/field/boolean.html.twig')
    ->setLabel('app.ui.{model_snake}.enabled'),
```

**TwigField — full object (access multiple properties in template):**
```php
TwigField::create('summary', 'grid/{model_snake}/field/summary.html.twig')
    ->setLabel('app.ui.{model_snake}.summary')
    ->setPath('.'),
```

```twig
{# templates/grid/{model_snake}/field/summary.html.twig #}
<strong>{{ data.title }}</strong>
<p>{{ data.description }}</p>
```

### Filters

| Use case               | Filter                                                                              |
|------------------------|-------------------------------------------------------------------------------------|
| Single string field    | `StringFilter::create('title')`                                                     |
| Multiple string fields | `StringFilter::create('search')->setOptions(['fields' => ['title', 'description']])` |
| Boolean flag           | `BooleanFilter::create('enabled')`                                                  |
| Date range             | `DateFilter::create('createdAt')`                                                   |
| Related entity         | `EntityFilter::create('category', Category::class)`                                 |

For an AJAX autocomplete filter on a related entity (useful when the related table is large), use `/sylius-stack:add-autocomplete` instead — it creates the dedicated filter class and wires it into the grid.

### Actions

Always include by default:
```php
->addActionGroup(
    MainActionGroup::create(
        CreateAction::create(),
    )
)
->addActionGroup(
    ItemActionGroup::create(
        UpdateAction::create(),
        DeleteAction::create(),
        // ShowAction::create(), // uncomment if a show page exists
    )
)
->addActionGroup(
    BulkActionGroup::create(
        DeleteAction::create(),
    )
)
```

### Sorting and limits

```php
->setLimits([10, 25, 50]) // items per page options
// ->orderBy('fieldName', 'asc') // only use a field declared in withFields()
```

### Full example

```php
<?php

declare(strict_types=1);

namespace App\Grid;

use App\Entity\{ModelName};
use Sylius\Bundle\GridBundle\Builder\Action\CreateAction;
use Sylius\Bundle\GridBundle\Builder\Action\DeleteAction;
use Sylius\Bundle\GridBundle\Builder\Action\UpdateAction;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\BulkActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\ItemActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\MainActionGroup;
use Sylius\Bundle\GridBundle\Builder\Field\DateTimeField;
use Sylius\Bundle\GridBundle\Builder\Field\StringField;
use Sylius\Bundle\GridBundle\Builder\Field\TwigField;
use Sylius\Bundle\GridBundle\Builder\Filter\BooleanFilter;
use Sylius\Bundle\GridBundle\Builder\Filter\DateFilter;
use Sylius\Bundle\GridBundle\Builder\Filter\EntityFilter;
use Sylius\Bundle\GridBundle\Builder\Filter\StringFilter;
// Remove unused imports depending on what fields/filters are used
use Sylius\Bundle\GridBundle\Builder\GridBuilderInterface;
use Sylius\Bundle\GridBundle\Grid\AbstractGrid;
use Sylius\Component\Grid\Attribute\AsGrid;

#[AsGrid(
    name: 'app_admin_{model_snake}',
    resourceClass: {ModelName}::class,
)]
final class Admin{ModelName}Grid extends AbstractGrid
{
    public function __invoke(GridBuilderInterface $gridBuilder): void
    {
        $gridBuilder
            ->withFields(
                // add fields here
            )
            ->withFilters(
                // add filters here
            )
            ->setLimits([10, 25, 50])
            // ->orderBy('fieldName', 'asc') // only use a field declared in withFields()
            ->addActionGroup(
                MainActionGroup::create(
                    CreateAction::create(),
                )
            )
            ->addActionGroup(
                ItemActionGroup::create(
                    UpdateAction::create(),
                    DeleteAction::create(),
                )
            )
            ->addActionGroup(
                BulkActionGroup::create(
                    DeleteAction::create(),
                )
            )
        ;
    }
}
```

## 4. Add translation keys

Create or update `translations/messages.en.yaml`. Use the pattern `app.ui.{model_snake}.{field}` — one key per field declared in the grid:

```yaml
app:
    ui:
        {model_snake}:
            {field_1}: '{Field 1 label}'
            {field_2}: '{Field 2 label}'
```

Example for `Book` with fields `title`, `description`, `price`:

```yaml
app:
    ui:
        book:
            title: 'Title'
            description: 'Description'
            price: 'Price'
```

## 5. Clear the cache

```bash
bin/console cache:clear
```

## 6. Verify

```bash
bin/console sylius:debug:grid 'App\Grid\Admin{ModelName}Grid'
```

The grid's fields, filters and actions should be printed.

---

## Next steps

- Run `/sylius-stack:add-operation Index` to expose the grid as the admin index page
- Run `/sylius-stack:add-menu` once the index operation exists, to add it to the admin sidebar