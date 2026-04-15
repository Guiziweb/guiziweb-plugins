---
name: add-menu
description: Add an admin menu entry for an existing Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add an admin Menu entry for a Sylius Resource

Ask the user for the ModelName if not provided. Read `composer.json` to detect the plugin namespace and plugin alias.

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
            ->addChild('{plugin_alias}_{model_snake}')
            ->setLabel('{plugin_alias}.ui.{model_snake_plural}')
        ;

        $submenu
            ->addChild('{plugin_alias}_admin_{model_snake}_index', [
                'route' => '{plugin_alias}_admin_{model_snake}_index',
            ])
            ->setLabel('{plugin_alias}.ui.{model_snake_plural}')
            ->setLabelAttribute('icon', 'tabler:list')
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
        {model_snake}: '{ModelName}'
        {model_snake_plural}: '{ModelName}s'
        managing_{model_snake_plural}: 'Managing {ModelName}s'
```

> `managing_{model_snake_plural}` is used as the `subheader` in the routes vars.

---

## 4. Verify

```bash
docker compose exec php vendor/bin/console cache:clear
```

Navigate to `/admin` and check the sidebar. The new entry should appear.
