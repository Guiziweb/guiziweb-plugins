# Sylius Plugin Skills

Skills for developing Sylius plugins with Docker.

## Installation

From terminal:
```bash
claude plugin marketplace add Guiziweb/guiziweb-plugins
```

Or inside Claude Code:
```
/plugin marketplace add Guiziweb/guiziweb-plugins
```

## Available Skills

### `sylius-plugin:init`

Create a new Sylius plugin from the official skeleton — includes Docker setup, GitHub repo, `.claude/rules/`, and Release Please workflow out of the box.

Ask Claude:
- "Create a new Sylius plugin"
- "Initialize a Sylius plugin project"

---

### `sylius-plugin:dev-commands`

Daily development commands (Docker, database, assets, QA).

Ask Claude:
- "How do I build assets in this plugin?"
- "How do I access the database?"

---

### `sylius-plugin:add-model`

Add a new Doctrine entity as a Sylius Resource.

Ask Claude:
- "Add a model Foo to this plugin"

---

### `sylius-plugin:add-translatable-model`

Add a translatable Doctrine entity as a Sylius Resource.

Ask Claude:
- "Add a translatable model Foo to this plugin"

---

### `sylius-plugin:override-model`

Override an existing Sylius model in a plugin.

---

### `sylius-plugin:add-stimulus-controller`

Add a Stimulus JS controller to a plugin.

Ask Claude:
- "Add a Stimulus controller MyController to the admin"

---

### `sylius-plugin:add-behat`

Add Behat UI tests (admin + Panther) to an existing plugin.

Ask Claude:
- "Add Behat tests to this plugin"
- "Create a Behat scenario for the admin orders grid"
