# Guiziweb Plugins

Claude Code plugins for Sylius plugin development.

## Installation

```bash
claude plugin marketplace add Guiziweb/guiziweb-plugins
```

Then install a plugin:

```bash
claude plugin install sylius-plugin
claude plugin install sylius-stack
```

## Update

```bash
claude plugin marketplace update guiziweb-plugins
claude plugin update sylius-plugin
claude plugin update sylius-stack
```

## sylius-plugin — Skills

| Skill | Description                                                                             |
|------------------------------------------|-----------------------------------------------------------------------------------------|
| [`sylius-plugin:init`](./plugins/sylius-plugin/skills/init/SKILL.md) | Create a new Sylius plugin from scratch - Docker setup, rename, database initialization |
| [`sylius-plugin:dev-commands`](./plugins/sylius-plugin/skills/dev-commands/SKILL.md) | Daily development commands (Docker, database, assets, QA)                               |
| [`sylius-plugin:add-model`](./plugins/sylius-plugin/skills/add-model/SKILL.md) | Add a new Doctrine entity as a Sylius Resource                                          |
| [`sylius-plugin:add-translatable-model`](./plugins/sylius-plugin/skills/add-translatable-model/SKILL.md) | Make an existing Resource translatable, with TranslationType and Twig hooks             |
| [`sylius-plugin:add-form`](./plugins/sylius-plugin/skills/add-form/SKILL.md) | Add an admin FormType for an existing Resource                                          |
| [`sylius-plugin:add-grid`](./plugins/sylius-plugin/skills/add-grid/SKILL.md) | Add a Sylius admin grid for an existing Resource                                        |
| [`sylius-plugin:add-routes`](./plugins/sylius-plugin/skills/add-routes/SKILL.md) | Add Sylius admin routes for an existing Resource with a Grid                            |
| [`sylius-plugin:add-menu`](./plugins/sylius-plugin/skills/add-menu/SKILL.md) | Add an admin menu entry for an existing Resource                                        |
| [`sylius-plugin:add-images`](./plugins/sylius-plugin/skills/add-images/SKILL.md) | Add a multiple images collection (OneToMany) to an existing Resource                    |
| [`sylius-plugin:add-autocomplete`](./plugins/sylius-plugin/skills/add-autocomplete/SKILL.md) | Add an admin autocomplete form type, usable in other forms via a form extension         |
| [`sylius-plugin:add-form-extension`](./plugins/sylius-plugin/skills/add-form-extension/SKILL.md) | Expose a custom field in an existing Sylius admin form (Product, Taxon, Channel…) |
| [`sylius-plugin:extends-model`](./plugins/sylius-plugin/skills/extends-model/SKILL.md) | Extend an existing Sylius entity (Product, Taxon, Channel…) to add custom fields        |
| [`sylius-plugin:add-stimulus-controller`](./plugins/sylius-plugin/skills/add-stimulus-controller/SKILL.md) | Add a Stimulus JS controller to a plugin                                                |
| [`sylius-plugin:add-behat`](./plugins/sylius-plugin/skills/add-behat/SKILL.md) | Add Behat UI tests (admin + JavaScript) to an existing plugin                           |

## sylius-stack — Skills

Build Symfony apps with the Sylius Stack (Resource, Grid, Bootstrap Admin UI, Twig Hooks) from scratch.

| Skill | Description |
|-------|-------------|
| [`sylius-stack:init`](./plugins/sylius-stack/skills/init/SKILL.md) | Bootstrap a new Symfony project with the Sylius Stack |
| [`sylius-stack:dev-commands`](./plugins/sylius-stack/skills/dev-commands/SKILL.md) | Daily development commands reference |
| [`sylius-stack:add-resource`](./plugins/sylius-stack/skills/add-resource/SKILL.md) | Add a Doctrine entity as a Sylius Resource (entity, repository, FormType, migration) |
| [`sylius-stack:add-operation`](./plugins/sylius-stack/skills/add-operation/SKILL.md) | Add CRUD operations (Index, Show, Create, Update, Delete, BulkDelete, ApplyStateMachineTransition) to a Resource |
| [`sylius-stack:add-grid`](./plugins/sylius-stack/skills/add-grid/SKILL.md) | Create an admin grid class (fields, filters, actions) |
| [`sylius-stack:add-grid-export`](./plugins/sylius-stack/skills/add-grid-export/SKILL.md) | Add a CSV export action to an existing admin grid |
| [`sylius-stack:add-menu`](./plugins/sylius-stack/skills/add-menu/SKILL.md) | Add an admin sidebar menu entry for a Resource |
| [`sylius-stack:add-template`](./plugins/sylius-stack/skills/add-template/SKILL.md) | Customize admin templates via Twig Hooks (override, add or disable a block) |
| [`sylius-stack:add-autocomplete`](./plugins/sylius-stack/skills/add-autocomplete/SKILL.md) | Add an autocomplete (simple or entity AJAX) as a FormType field or a grid filter |
| [`sylius-stack:add-security`](./plugins/sylius-stack/skills/add-security/SKILL.md) | Secure the admin panel with a User entity, firewall and access control |
| [`sylius-stack:add-provider`](./plugins/sylius-stack/skills/add-provider/SKILL.md) | Load a resource from a non-Doctrine source (external API, command bus, DDD) |
| [`sylius-stack:add-processor`](./plugins/sylius-stack/skills/add-processor/SKILL.md) | Add custom logic on Create/Update/Delete (email, command, event, queue) |
| [`sylius-stack:add-responder`](./plugins/sylius-stack/skills/add-responder/SKILL.md) | Return a non-standard HTTP response (PDF, XLSX, stream, binary) |
| [`sylius-stack:add-factory`](./plugins/sylius-stack/skills/add-factory/SKILL.md) | Control how a resource is instantiated on Create (defaults, creator, relations) |
| [`sylius-stack:add-twig-context`](./plugins/sylius-stack/skills/add-twig-context/SKILL.md) | Add extra variables to the Twig template of an operation |

## Local Development

```bash
claude --plugin-dir /path/to/guiziweb-plugins/plugins/sylius-plugin/
claude --plugin-dir /path/to/guiziweb-plugins/plugins/sylius-stack/
```
