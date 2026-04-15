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
| `sylius-plugin:init` | Create a new Sylius plugin from scratch - Docker setup, rename, database initialization |
| `sylius-plugin:dev-commands` | Daily development commands (Docker, database, assets, QA)                               |
| `sylius-plugin:add-model` | Add a new Doctrine entity as a Sylius Resource                                          |
| `sylius-plugin:add-translatable-model` | Make an existing Resource translatable, with TranslationType and Twig hooks             |
| `sylius-plugin:add-form` | Add an admin FormType for an existing Resource                                          |
| `sylius-plugin:add-grid` | Add a Sylius admin grid for an existing Resource                                        |
| `sylius-plugin:add-routes` | Add Sylius admin routes for an existing Resource with a Grid                            |
| `sylius-plugin:add-menu` | Add an admin menu entry for an existing Resource                                        |
| `sylius-plugin:add-images` | Add a multiple images collection (OneToMany) to an existing Resource                    |
| `sylius-plugin:add-autocomplete` | Add an admin autocomplete form type, usable in other forms via a form extension         |
| `sylius-plugin:extends-model` | Extend an existing Sylius entity (Product, Taxon, Channel…) to add custom fields        |
| `sylius-plugin:add-stimulus-controller` | Add a Stimulus JS controller to a plugin                                                |
| `sylius-plugin:add-behat` | Add Behat UI tests (admin + JavaScript) to an existing plugin                           |

## Local Development

```bash
claude --plugin-dir /path/to/guiziweb-plugins/plugins/sylius-plugin/
```
