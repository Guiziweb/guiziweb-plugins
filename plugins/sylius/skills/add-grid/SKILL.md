---
name: add-grid
description: Add a Sylius admin grid for an existing Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add an admin Grid for a Sylius Resource

Ask the user for the ModelName if not provided. Read `src/**/Entity/{ModelName}/{ModelName}.php` to detect the entity's fields and relations.

**Prerequisite:** the model must already exist as a Sylius Resource (run `add-model` first). To customize an existing Sylius core grid (e.g. `sylius_admin_product`), use `/sylius:extends-grid` instead.

## 1. Setup (one-time per project)

One grid file per resource at a unified path: `config/packages/grids/{model_snake}.yaml`.

Add (or extend) an `imports:` block at the top of `config/packages/_sylius.yaml` (shipped by Sylius-Standard, user-editable) so the split files are loaded:

```yaml
imports:
    - { resource: "grids/*.yaml" }
    # and "twig_hooks/**/*.yaml" if twig hooks are used by this project

sylius_addressing:
    # ... existing content untouched
```

Plugin context: same pattern in `tests/TestApplication/config/packages/_sylius.yaml` (create if missing).

## 2. Ask the user

Based on the entity, ask:
- Which properties to display as columns
- Which filters to include
- Whether to include a `show` action (requires a show route — run `/sylius:add-routes` with a `show` operation if missing)

## 3. Grid skeleton

Grid name convention: `${SYLIUS_PREFIX}_admin_{model_snake}`.

```yaml
sylius_grid:
    grids:
        ${SYLIUS_PREFIX}_admin_{model_snake}:
            driver:
                name: doctrine/orm
                options:
                    class: "%${SYLIUS_PREFIX}.model.{model_snake}.class%"
            sorting:
                # Must reference a field declared under `fields:` (the sort key is resolved against field names, not entity columns).
                # If you want to sort by id but not display it, declare the `id` field and set `enabled: false`.
            limits: [10, 25, 50]
            fields:
                # see §4
            filters:
                # see §5
            actions:
                # see §6
```

## 4. Fields

The only valid grid field types are `string`, `datetime`, `enum`, `twig`, and `callable`. 

| Doctrine type / use case | Grid type | Notes |
|---|---|---|
| `string`, `text`, `integer` | `string` | plain display — `integer` on the entity still maps to grid type `string` |
| `datetime`, `date` | `datetime` | option `format` (default `Y:m:d H:i:s`) |
| `boolean` | `twig` | template `@SyliusUi/Grid/Field/enabled.html.twig` |
| Relation property (ManyToOne) | `string` with `path: relation.property` | e.g. `author.username` |
| Complex / multi-property render | `twig` with `path: .` | full object available as `{{ data.* }}` in template |

**Common options on every field:**
- `label: ${SYLIUS_PREFIX}.grid.{model_snake}.{field}` — translation key
- `sortable: ~` — enable sorting using this field's name as the sort path; use `sortable: some.path` to sort by a different column (useful with `type: twig`)
- `position: N` — display order (lower = earlier)

Examples:

```yaml
fields:
    title:
        type: string
        label: ${SYLIUS_PREFIX}.grid.{model_snake}.title
        sortable: ~
    enabled:
        type: twig
        label: ${SYLIUS_PREFIX}.grid.{model_snake}.enabled
        options:
            template: '@SyliusUi/Grid/Field/enabled.html.twig'
    author:
        type: string
        label: ${SYLIUS_PREFIX}.grid.{model_snake}.author
        path: author.username
    createdAt:
        type: datetime
        label: ${SYLIUS_PREFIX}.grid.{model_snake}.created_at
        sortable: ~
        options:
            format: 'd-m-Y H:i'
```

## 5. Filters

| Use case | YAML |
|---|---|
| Single text field | `type: string` |
| Multi-field search | `type: string` + `options.fields: [field1, field2]` |
| Force one operator | `form_options.type: contains` (see note below) |
| Boolean flag | `type: boolean` |
| Date range | `type: date` |
| Related entity (small table) | `type: entity` + `form_options.class: '%…model…class%'` |
| Enum / choice list | `type: select` + `form_options.choices: { label: value, … }` |
| Field has any value | `type: exists` + `options.field: {field}` |
| Money range | `type: money` + `options.currency_field: currencyCode` |

String filter operators (for `form_options.type:`): `contains`, `not_contains`, `equal`, `not_equal`, `starts_with`, `ends_with`, `empty`, `not_empty`, `in`, `not_in`, `member_of`.

Useful options:
- `default_value: true` — pre-fill the filter (e.g. enabled-only view by default)
- `enabled: false` — hide a filter inherited from a parent grid

For an AJAX autocomplete filter on a related entity, Sylius ships two ready-to-use filter types — you do **not** need a custom filter class or to call `/sylius:add-autocomplete`:

- `ux_translatable_autocomplete` — for entities with a translatable label (e.g. `Taxon.name`, `Library.name`)
- `ux_autocomplete` — for non-translatable entities

```yaml
filters:
    library:
        type: ux_translatable_autocomplete
        label: ${SYLIUS_PREFIX}.grid.{model_snake}.library
        form_options:
            multiple: false
            extra_options:
                class: '%${SYLIUS_PREFIX}.model.library.class%'
                translation_fields: [name]
                choice_label: name
        options:
            fields: [library.id]   # DQL path used by the filter, usually `{relation}.id`
```

Reserve `/sylius:add-autocomplete` for exposing an autocomplete in a **form** — grid filters do not need it.

Examples:

```yaml
filters:
    search:
        type: string
        label: ${SYLIUS_PREFIX}.grid.{model_snake}.search
        options:
            fields: [title, description]
    enabled:
        type: boolean
        label: ${SYLIUS_PREFIX}.grid.{model_snake}.enabled
        default_value: true
    createdAt:
        type: date
        label: ${SYLIUS_PREFIX}.grid.{model_snake}.created_at
    author:
        type: entity
        label: ${SYLIUS_PREFIX}.grid.{model_snake}.author
        form_options:
            class: '%sylius.model.admin_user.class%'
```

## 6. Actions

```yaml
actions:
    main:
        create:
            type: create
    item:
        update:
            type: update
        delete:
            type: delete
        # show:   # uncomment if a show route exists
        #     type: show
        #     options:
        #         link:
        #             route: ${SYLIUS_PREFIX}_admin_{model_snake}_show
        #             parameters:
        #                 id: resource.id
    bulk:
        delete:
            type: delete
```

Other built-in types (use when relevant):
- `show` (item) — read-only page; requires a show route
- `archive` (item, bulk) — soft-delete workflow; entity must implement `ArchiveInterface`, supports `restore_label`
- `apply_transition` (item, bulk) — workflow state machine transition

For a per-row dropdown grouping several links (e.g. "Manage children"), use a `subitem` action group with `type: links` and a list of `links:` (each with `route`, `parameters`, optional `visible: resource.hasXxx` expression).

## 7. Translation keys

Get the project's default locale:

```bash
$SYLIUS_CONSOLE debug:container --parameter=kernel.default_locale
```

Add labels to `translations/messages.{locale}.yaml`. Grid field labels use the `grid.{model_snake}.{field}` namespace (keeps `${SYLIUS_PREFIX}.ui.*` free for Sylius's auto-generated scalar keys like `${SYLIUS_PREFIX}.ui.{model_snake}` used for page titles/breadcrumbs).

```yaml
${SYLIUS_PREFIX}:
    grid:
        {model_snake}:
            title: Title
            enabled: Enabled
            created_at: Created at
            search: Search
```

## 8. Clear cache

```bash
$SYLIUS_CONSOLE cache:clear
```

## 9. Verify

- [ ] `$SYLIUS_CONSOLE sylius:debug:grid ${SYLIUS_PREFIX}_admin_{model_snake}` prints the grid definition (fields, filters, actions as declared)
- [ ] `$SYLIUS_CONSOLE debug:translation {locale} --domain=messages 2>&1 | grep '${SYLIUS_PREFIX}.grid.{model_snake}'` lists every label you added.

## Next steps

- Add admin routes referencing this grid → `/sylius:add-routes`
- Add admin menu entry → `/sylius:add-menu`
