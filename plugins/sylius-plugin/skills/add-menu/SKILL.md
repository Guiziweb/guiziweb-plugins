---
name: add-menu
description: Add an admin menu entry for an existing Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add an admin Menu entry for a Sylius Resource

Ask the user for the ModelName if not provided. Read `composer.json` to detect the plugin namespace and plugin alias.

Ask the user:
- The **submenu label key** (e.g. `library` for a plugin with multiple resources). Default: `{model_snake_plural}` (same as the item — works fine for a single-resource plugin).
- The **submenu icon** (Tabler name, e.g. `tabler:books`). Optional — defaults to no icon.
- The **item icon** (Tabler name, e.g. `tabler:list`).

If a `{ModelName}AdminMenuListener` already exists for the same submenu (e.g. a previous resource of the same plugin), **extend it** with a new `addChild()` call instead of creating a new listener — that way multiple resources group under the same parent submenu.

**Prerequisite:** admin routes must exist (`add-routes`).

---

## 1. Create the Menu Listener

`src/Menu/{ModelName}AdminMenuListener.php`:

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class {ModelName}AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $submenu = $menu
            ->addChild('{submenu_key}')
            ->setLabel('{plugin_alias}.ui.{submenu_key}')
            ->setLabelAttribute('icon', '{submenu_icon}')  // remove this line if no icon chosen
        ;

        $submenu
            ->addChild('{plugin_alias}_admin_{model_snake}_index', [
                'route' => '{plugin_alias}_admin_{model_snake}_index',
            ])
            ->setLabel('{plugin_alias}.ui.{model_snake_plural}')
            ->setLabelAttribute('icon', '{item_icon}')
        ;
    }
}
```

---

## 2. Register the listener in `config/services.xml`

```xml
<service id="{plugin_alias}.listener.admin.menu.{model_snake}"
         class="{Namespace}\Menu\{ModelName}AdminMenuListener">
    <tag name="kernel.event_listener"
         event="sylius.menu.admin.main"
         method="addAdminMenuItems" />
</service>
```

---

## 3. Add translations

Create or update `translations/messages.en_US.yaml`:

```yaml
{plugin_alias}:
    ui:
        {submenu_key}: '{Submenu label}'             # only if different from the item
        {model_snake}: '{ModelName}'
        {model_snake_plural}: '{ModelName}s'
        managing_{model_snake_plural}: 'Managing {ModelName}s'
```

> `managing_{model_snake_plural}` is used as the `subheader` in the routes vars.

---

## 4. Verify

```bash
docker compose exec php vendor/bin/console cache:clear
docker compose exec php vendor/bin/console debug:router {plugin_alias}_admin_{model_snake}_index
docker compose exec php vendor/bin/console debug:container '{Namespace}\Menu\{ModelName}AdminMenuListener'
```

The route should exist, and the listener should be tagged with `kernel.event_listener` on event `sylius.menu.admin.main`.
