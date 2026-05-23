---
name: add-menu
description: Add an admin menu entry for an existing Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add an admin Menu entry for a Sylius Resource

Ask the user for the ModelName if not provided. Ask also:
- **Submenu label key** — the group's identifier when the app exposes several related resources under one parent. Default: `{model_snake_plural}` (works when there's only one resource).
- **Submenu icon** — Tabler icon name. Optional.
- **Item icon** — Tabler icon name.

**Prerequisite:** admin routes exist (`/sylius-app:add-routes`).

Sylius admin menu customization is done via an event listener on `sylius.menu.admin.main` (the left sidebar) — this is the idiomatic approach per the Sylius customizing-menus guide.

## 1. Create or extend the AdminMenuListener

**One listener per project**, not one per model. If `src/Menu/AdminMenuListener.php` already exists, append a new `addChild()` call under the right submenu — don't create a new listener class.

`src/Menu/AdminMenuListener.php`:

```php
<?php

declare(strict_types=1);

namespace App\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function __invoke(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $submenu = $menu
            ->addChild('{submenu_key}')
            ->setLabel('app.ui.{submenu_key}')
            ->setLabelAttribute('icon', '{submenu_icon}')   // remove this line if no icon
        ;

        $submenu
            ->addChild('app_admin_{model_snake}_index', [
                'route' => 'app_admin_{model_snake}_index',
            ])
            ->setLabel('app.ui.{model_snake_plural}')
            ->setLabelAttribute('icon', '{item_icon}')
        ;
    }
}
```

When extending: keep the `$submenu = $menu->addChild('{submenu_key}')...` block once, then append extra `$submenu->addChild(...)` blocks — each resource becomes a new item under the same submenu.

## 2. Register the listener

Add to `config/services.yaml`:

```yaml
services:
    app.listener.admin.menu:
        class: App\Menu\AdminMenuListener
        tags:
            - { name: kernel.event_listener, event: sylius.menu.admin.main }
```

The listener is invokable (`__invoke`), so no `method:` attribute is needed on the tag.

## 3. Add translations

Get the project's default locale:

```bash
bin/console debug:container --parameter=kernel.default_locale
```

Add to `translations/messages.{locale}.yaml`:

```yaml
app:
    ui:
        {submenu_key}: '{Submenu label}'
        {model_snake}: '{ModelName}'                        # singular — Sylius auto-uses it for create/show titles
        {model_snake_plural}: '{ModelName}s'                 # plural — Sylius auto-uses it for breadcrumbs
        manage_{model_snake_plural}: 'Manage {ModelName}s'   # subheader (also used by /sylius-app:add-routes)
```

The singular `app.ui.{model_snake}` is auto-resolved by Sylius's admin templates for titles like "New {ModelName}". Must stay a scalar — never nest anything under it.

## 4. Clear cache

```bash
bin/console cache:clear
```

## 5. Verify

 - [ ] `bin/console debug:event-dispatcher sylius.menu.admin.main` lists `App\Menu\AdminMenuListener` among the registered listeners
- [ ] `bin/console debug:router app_admin_{model_snake}_index` resolves the target route
- [ ] `bin/console debug:translation {locale} --domain=messages 2>&1 | grep 'app.ui.'` lists every ui key you added.
