---
name: add-template
description: Customize admin templates via Twig Hooks — override, add or disable a block on any admin page
argument-hint: "[ModelName?] [Operation?]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Customize a Template — Sylius Stack

Ask the user what they want to customize:
- **Override** an existing block (replace its template)
- **Add** a new block on a page
- **Disable** an existing block

To create a new **Show page** for a resource, use `/sylius-stack:add-operation Show` instead — it scaffolds the hook and a minimal template.

---

## How Twig Hooks work

Sylius admin pages are composed of nested hookable blocks. Each block is attached to a hook name. The admin templates declare **two hook names on the same spot** — a resource-specific one and a `common` one:

```twig
{% hook ['sylius_admin.book.show.content', 'sylius_admin.common.show.content'] %}
```

A hookable defined on the resource-specific hook takes priority, so you can target either:
- `sylius_admin.{model_snake}.{operation}.{block}` → applies only to this resource
- `sylius_admin.common.{operation}.{block}` → applies to every resource that has no specific override

Hook names can be deeply nested (`content.header.title_block.actions`) and some hooks use a `#fragment` notation (`sylius_admin.common.create#title`). There are also non-CRUD hooks: `sylius_admin.base#stylesheets`, `sylius_admin.common.component.sidebar`, `sylius_admin.dashboard.index.content`, etc.

### Discovering hooks

Use `sylius:debug:twig-hooks` as the source of truth — never guess a hook name:

```bash
# List all hooks in the application
bin/console sylius:debug:twig-hooks

# Filter by prefix (all admin hooks, or all hooks for one resource)
bin/console sylius:debug:twig-hooks sylius_admin
bin/console sylius:debug:twig-hooks sylius_admin.{model_snake}

# Inspect one hook: lists its hookables, their template and priority
bin/console sylius:debug:twig-hooks sylius_admin.{model_snake}.show.content

# Add -c to also print context, configuration and props passed to hookables
bin/console sylius:debug:twig-hooks -c sylius_admin.{model_snake}.show.content

# Tree view of the whole hook graph under a prefix
bin/console sylius:debug:twig-hooks -t sylius_admin.{model_snake}
```

The `-c` flag is how you find the variables available inside a hookable template (what the hook passes via `with { ... }` — typically `{model_snake}`, `resource`, `resources`, `form`, `operation`, `resource_metadata`).

---

## Where the YAML lives

Any file under `config/packages/` that uses the `sylius_twig_hooks:` extension key is merged by Symfony. Two common conventions:

1. **Single file** — put everything in `config/packages/sylius_twig_hooks.yaml` (default recommendation from the Twig Hooks docs).
2. **Per-resource** — let `config/packages/sylius_twig_hooks.yaml` import split files, e.g. `config/sylius/twig_hooks/{model_snake}/{operation}.yaml`. Useful once the config grows.

Check what already exists in the project before choosing where to write:

```bash
ls config/packages | grep -E 'twig_hooks|bootstrap_admin_ui'
grep -rn 'sylius_twig_hooks' config/
```

If both conventions are present, follow the existing one. Otherwise default to option 1.

---

## Case 1 — Override an existing block

Reuse the **same hookable name** with a new template.

```yaml
# config/packages/sylius_twig_hooks.yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.{model_snake}.create.content.form.sections':
            general:
                template: '{model_snake}/create/form/sections/general.html.twig'
```

```twig
{# templates/{model_snake}/create/form/sections/general.html.twig #}
{% set form = hookable_metadata.context.form %}

{{ form_row(form.title) }}
{{ form_row(form.description) }}
```

Verify the override is picked up:

```bash
bin/console sylius:debug:twig-hooks sylius_admin.{model_snake}.create.content.form.sections
```

The `general` hookable should point to your template.

---

## Case 2 — Add a new block on an existing page

Add a hookable with a **new name** on an existing hook.

```yaml
# config/packages/sylius_twig_hooks.yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.{model_snake}.index.content':
            custom_panel:
                template: '{model_snake}/index/content/custom_panel.html.twig'
                priority: -10  # lower priority = rendered after default blocks
```

Priority is optional. Higher values render first.

---

## Case 3 — Disable an existing block

Set `enabled: false` on the hookable you want to hide.

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.{model_snake}.show.content':
            header:
                enabled: false
```

To confirm the block is off:

```bash
bin/console sylius:debug:twig-hooks -a sylius_admin.{model_snake}.show.content
```

The `-a` flag includes disabled hookables so you can check the final state.

---

## After editing

```bash
bin/console cache:clear
```