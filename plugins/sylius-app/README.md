# sylius-app

Skills for integrators working **inside a Sylius application** — a Sylius-Standard install with `require: sylius/sylius`, namespace `App\`, customizations living in `src/`.

No environment variables to detect: conventions are constant.

| Convention | Value |
|------------|-------|
| Root namespace | `App\` |
| Resource/service prefix | `app` |
| Twig template namespace | _(empty — templates live in `templates/`)_ |

## Skills

| Skill | Description |
|-------|-------------|
| [`sylius-app:add-model`](./skills/add-model/SKILL.md) | Add a new Doctrine entity as a Sylius Resource |
| [`sylius-app:add-translatable-model`](./skills/add-translatable-model/SKILL.md) | Make an existing Resource translatable, with TranslationType and Twig hooks |
| [`sylius-app:add-form`](./skills/add-form/SKILL.md) | Add an admin FormType for an existing Resource |
| [`sylius-app:add-grid`](./skills/add-grid/SKILL.md) | Add a Sylius admin grid for an existing Resource |
| [`sylius-app:add-routes`](./skills/add-routes/SKILL.md) | Add Sylius admin routes for an existing Resource with a Grid |
| [`sylius-app:add-menu`](./skills/add-menu/SKILL.md) | Add an admin menu entry for an existing Resource |
| [`sylius-app:add-images`](./skills/add-images/SKILL.md) | Add a multiple images collection (OneToMany) to an existing Resource |
| [`sylius-app:add-autocomplete`](./skills/add-autocomplete/SKILL.md) | Add an admin autocomplete form type, usable in other forms via a form extension |
| [`sylius-app:extends-model`](./skills/extends-model/SKILL.md) | Extend an existing Sylius entity (Product, Taxon, Channel…) to add custom fields |
| [`sylius-app:extends-form`](./skills/extends-form/SKILL.md) | Expose a custom field in an existing Sylius admin form (Product, Taxon, Channel…) |
| [`sylius-app:extends-grid`](./skills/extends-grid/SKILL.md) | Customize an existing Sylius admin grid (hide/reorder/modify/add fields, filters, actions) |

## Local development

This plugin lives inside the [`guiziweb-plugins`](../../README.md) marketplace repo. To test without installing through the marketplace:

```bash
claude --plugin-dir /path/to/guiziweb-plugins/plugins/sylius-app/
```