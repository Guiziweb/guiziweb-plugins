# sylius-plugin

Skills for developers building a **distributable Sylius plugin** — a project with `composer.json` `type: sylius-plugin`, the `PluginSkeleton` layout, and a `tests/Application/` test app.

## Environment variables

A `SessionStart` hook reads `composer.json` and the filesystem to export environment variables consumed by the skills. Bails silently when the project is not a Sylius plugin.

| Variable | Description |
|----------|-------------|
| `SYLIUS_NAMESPACE` | Root PHP namespace from PSR-4 (e.g. `Acme\SyliusExamplePlugin`) |
| `SYLIUS_PREFIX` | DI extension alias in snake_case (e.g. `acme_sylius_example`) — used for resource aliases, service IDs, config keys |
| `SYLIUS_TEMPLATE_NS` | Twig template namespace prefix from bundle class (e.g. `@AcmeSyliusExamplePlugin/`) |

## Skills

| Skill | Description |
|-------|-------------|
| [`sylius-plugin:plugin-init`](./skills/plugin-init/SKILL.md) | Create a new Sylius plugin from scratch — Docker setup, rename, database initialization |
| [`sylius-plugin:add-model`](./skills/add-model/SKILL.md) | Add a new Doctrine entity as a Sylius Resource |
| [`sylius-plugin:add-translatable-model`](./skills/add-translatable-model/SKILL.md) | Make an existing Resource translatable, with TranslationType and Twig hooks |
| [`sylius-plugin:add-form`](./skills/add-form/SKILL.md) | Add an admin FormType for an existing Resource |
| [`sylius-plugin:add-grid`](./skills/add-grid/SKILL.md) | Add a Sylius admin grid for an existing Resource |
| [`sylius-plugin:add-routes`](./skills/add-routes/SKILL.md) | Add Sylius admin routes for an existing Resource with a Grid |
| [`sylius-plugin:add-menu`](./skills/add-menu/SKILL.md) | Add an admin menu entry for an existing Resource |
| [`sylius-plugin:add-images`](./skills/add-images/SKILL.md) | Add a multiple images collection (OneToMany) to an existing Resource |
| [`sylius-plugin:add-autocomplete`](./skills/add-autocomplete/SKILL.md) | Add an admin autocomplete form type, usable in other forms via a form extension |
| [`sylius-plugin:extends-model`](./skills/extends-model/SKILL.md) | Extend an existing Sylius entity (Product, Taxon, Channel…) to add custom fields |
| [`sylius-plugin:extends-form`](./skills/extends-form/SKILL.md) | Expose a custom field in an existing Sylius admin form (Product, Taxon, Channel…) |
| [`sylius-plugin:extends-grid`](./skills/extends-grid/SKILL.md) | Customize an existing Sylius admin grid (hide/reorder/modify/add fields, filters, actions) |
| [`sylius-plugin:plugin-add-stimulus-controller`](./skills/plugin-add-stimulus-controller/SKILL.md) | Add a Stimulus JS controller to a plugin |

## Local development

This plugin lives inside the [`guiziweb-plugins`](../../README.md) marketplace repo. To test without installing through the marketplace:

```bash
claude --plugin-dir /path/to/guiziweb-plugins/plugins/sylius-plugin/
```