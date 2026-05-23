# sylius-app

Skills for integrators working **inside a Sylius application** — a Sylius-Standard install with `require: sylius/sylius`, namespace `App\`, customizations living in `src/`.

No environment variables to detect: conventions are constant.

| Convention | Value |
|------------|-------|
| Root namespace | `App\` |
| Resource/service prefix | `app` |
| Twig template namespace | _(empty — templates live in `templates/`)_ |

## Skills

_Skills are being introduced incrementally. See the [project board](https://github.com/Guiziweb/guiziweb-plugins/issues) for progress._

## Local development

This plugin lives inside the [`guiziweb-plugins`](../../README.md) marketplace repo. To test without installing through the marketplace:

```bash
claude --plugin-dir /path/to/guiziweb-plugins/plugins/sylius-app/
```