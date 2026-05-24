# sylius-stack

Skills for building Symfony apps with the **Sylius Stack** (Resource Bundle, Grid Bundle, Bootstrap Admin UI, Twig Hooks) from scratch — no full Sylius e-commerce required.

## Skills

| Skill | Description |
|-------|-------------|
| [`sylius-stack:init`](./skills/init/SKILL.md) | Bootstrap a new Symfony project with the Sylius Stack |
| [`sylius-stack:dev-commands`](./skills/dev-commands/SKILL.md) | Daily development commands reference |
| [`sylius-stack:add-resource`](./skills/add-resource/SKILL.md) | Add a Doctrine entity as a Sylius Resource (entity, repository, FormType, migration) |
| [`sylius-stack:add-operation`](./skills/add-operation/SKILL.md) | Add CRUD operations (Index, Show, Create, Update, Delete, BulkDelete, ApplyStateMachineTransition) to a Resource |
| [`sylius-stack:add-grid`](./skills/add-grid/SKILL.md) | Create an admin grid class (fields, filters, actions) |
| [`sylius-stack:add-grid-export`](./skills/add-grid-export/SKILL.md) | Add a CSV export action to an existing admin grid |
| [`sylius-stack:add-menu`](./skills/add-menu/SKILL.md) | Add an admin sidebar menu entry for a Resource |
| [`sylius-stack:add-template`](./skills/add-template/SKILL.md) | Customize admin templates via Twig Hooks (override, add or disable a block) |
| [`sylius-stack:add-autocomplete`](./skills/add-autocomplete/SKILL.md) | Add an autocomplete (simple or entity AJAX) as a FormType field or a grid filter |
| [`sylius-stack:add-security`](./skills/add-security/SKILL.md) | Secure the admin panel with a User entity, firewall and access control |
| [`sylius-stack:add-provider`](./skills/add-provider/SKILL.md) | Load a resource from a non-Doctrine source (external API, command bus, DDD) |
| [`sylius-stack:add-processor`](./skills/add-processor/SKILL.md) | Add custom logic on Create/Update/Delete (email, command, event, queue) |
| [`sylius-stack:add-responder`](./skills/add-responder/SKILL.md) | Return a non-standard HTTP response (PDF, XLSX, stream, binary) |
| [`sylius-stack:add-factory`](./skills/add-factory/SKILL.md) | Control how a resource is instantiated on Create (defaults, creator, relations) |
| [`sylius-stack:add-twig-context`](./skills/add-twig-context/SKILL.md) | Add extra variables to the Twig template of an operation |

## Local development

This plugin lives inside the [`guiziweb-plugins`](../../README.md) marketplace repo. To test without installing through the marketplace:

```bash
claude --plugin-dir /path/to/guiziweb-plugins/plugins/sylius-stack/
```