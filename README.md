# Guiziweb Plugins

Claude Code plugins for Sylius plugin development.

## Installation

```bash
claude plugin marketplace add Guiziweb/guiziweb-plugins
```

Then install the plugin:

```bash
claude plugin install sylius-plugin
```

## Update

```bash
claude plugin marketplace update guiziweb-plugins
claude plugin update sylius-plugin
```

## Available Skills

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
| [`sylius-plugin:extends-model`](./plugins/sylius-plugin/skills/extends-model/SKILL.md) | Extend an existing Sylius entity (Product, Taxon, Channel…) to add custom fields        |
| [`sylius-plugin:add-stimulus-controller`](./plugins/sylius-plugin/skills/add-stimulus-controller/SKILL.md) | Add a Stimulus JS controller to a plugin                                                |
| [`sylius-plugin:add-behat`](./plugins/sylius-plugin/skills/add-behat/SKILL.md) | Add Behat UI tests (admin + JavaScript) to an existing plugin                           |

## Local Development

```bash
claude --plugin-dir /path/to/guiziweb-plugins/plugins/sylius-plugin/
```
