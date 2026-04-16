---
name: add-operation
description: Add one or more operations (Index, Show, Create, Update, Delete, BulkDelete, ApplyStateMachineTransition) to an existing Sylius Resource
argument-hint: "[ModelName] [Operation...]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add Operations — Sylius Stack

Ask the user for the ModelName and the list of operations they want if not provided. Several operations can be added in one run.

Supported operations:

| Operation | Route | Method | Notes |
|-----------|-------|--------|-------|
| `Index` | `/{plural}` | GET | requires a grid (run `/sylius-stack:add-grid` first) |
| `Show` | `/{plural}/{id}` | GET | also scaffolds a minimal Twig Hook template |
| `Create` | `/{plural}/new` | GET, POST | needs `formType` on the resource |
| `Update` | `/{plural}/{id}/edit` | GET, PUT, PATCH | needs `formType` on the resource |
| `Delete` | `/{plural}/{id}` | DELETE | |
| `BulkDelete` | `/{plural}/bulk_delete` | DELETE | |
| `ApplyStateMachineTransition` | `/{plural}/{id}/{transition}` | GET | requires a transition name |

---

## 1. Read the entity

Read `src/Entity/{ModelName}.php` to locate the `#[AsResource(...)]` attribute and find:
- Existing imports from `Sylius\Resource\Metadata\*`
- Existing `operations: [...]` array (may be missing if this is the first call)

## 2. Ordering rule

When writing the `operations` array, place `Create` **before** any operation whose path starts with `{id}` (`Show`, `Update`, `Delete`). The Symfony router matches routes in the order they are registered, so a path like `/{id}` declared before `/new` can swallow the literal `new`.

Recommended order when all are present:

```php
operations: [
    new Index(grid: Admin{ModelName}Grid::class),
    new Create(),
    new Update(),
    new Delete(),
    new BulkDelete(),
    new Show(),
],
```

## 3. Add imports

For each operation added, add its `use` statement if not already present:

```php
use Sylius\Resource\Metadata\ApplyStateMachineTransition;
use Sylius\Resource\Metadata\BulkDelete;
use Sylius\Resource\Metadata\Create;
use Sylius\Resource\Metadata\Delete;
use Sylius\Resource\Metadata\Index;
use Sylius\Resource\Metadata\Show;
use Sylius\Resource\Metadata\Update;
```

For `Index`, also add:

```php
use App\Grid\Admin{ModelName}Grid;
```

## 4. Insert / merge operations

- If `operations: [...]` does not exist in `#[AsResource(...)]`, add it.
- If it exists, merge the new operations into the array, preserving existing ones and respecting the ordering rule above.

## 5. Per-operation specifics

### `Index`

- Ask the user for the grid class name. Default: `Admin{ModelName}Grid`.
- If the grid class does not exist yet (`src/Grid/Admin{ModelName}Grid.php`), tell the user to run `/sylius-stack:add-grid` first and stop.
- Add the operation as:

```php
new Index(grid: Admin{ModelName}Grid::class),
```

### `Show`

Add the operation:

```php
new Show(),
```

Then scaffold the Twig Hook and template so the page renders out of the box.

Edit `config/packages/sylius_bootstrap_admin_ui.yaml`:

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.{model_snake}.show.content':
            body:
                template: '{model_snake}/show/content/body.html.twig'
```

Create `templates/{model_snake}/show/content/body.html.twig`:

```twig
{% set {model_snake} = hookable_metadata.context.{model_snake} %}

<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <div class="col-12">
                {# Access any field from the resource #}
                <p>{{ {model_snake}.id }}</p>
            </div>
        </div>
    </div>
</div>
```

If a grid exists, also ask the user whether to add the `ShowAction` item action:

```php
use Sylius\Bundle\GridBundle\Builder\Action\ShowAction;

ItemActionGroup::create(
    ShowAction::create(),
    UpdateAction::create(),
    DeleteAction::create(),
),
```

### `Create` / `Update`

Add directly:

```php
new Create(),
new Update(),
```

Both rely on `formType` declared at the `#[AsResource]` level. If `formType` is missing, tell the user and stop.

Validation via `symfony/validator` constraints on the entity runs automatically on `Create` and `Update`. To disable it on a specific operation:

```php
new Update(validate: false),
```

### `Delete` / `BulkDelete`

Add directly:

```php
new Delete(),
new BulkDelete(),
```

### `ApplyStateMachineTransition`

Ask the user for the transition name (e.g. `publish`). Add:

```php
new ApplyStateMachineTransition(stateMachineTransition: 'publish'),
```

The resulting route will be `/{plural}/{id}/publish`.

## 6. Clear the cache

```bash
bin/console cache:clear
```

## 7. Verify

```bash
bin/console sylius:debug:resource 'App\Entity\{ModelName}'
bin/console debug:router | grep {model_snake}
```

Each operation should appear with its expected path.

---

## Next steps

- If you added `Index` and have no grid yet, run `/sylius-stack:add-grid`
- Run `/sylius-stack:add-menu` to expose the resource in the admin sidebar
- Run `/sylius-stack:add-template` to customize the rendered blocks (override, add or disable a block)