---
name: add-menu
description: Add an admin menu entry for an existing Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add an admin Menu entry for a Sylius Resource

Ask the user for the ModelName if not provided. Ask also:
- **Submenu label key** — the group's identifier when the plugin ships several related resources under one parent. Default: `{model_snake_plural}` (works when there's only one resource).
- **Submenu icon** — Tabler icon name. Optional.
- **Item icon** — Tabler icon name.

**Prerequisite:** admin routes exist (`/sylius:add-routes`).

Sylius admin menu customization is done via an event listener on `sylius.menu.admin.main` (the left sidebar) — this is the idiomatic approach per the Sylius customizing-menus guide.

## 1. Create or extend the AdminMenuListener

**One listener per project**, not one per model. If `src/Menu/AdminMenuListener.php` already exists, append a new `addChild()` call under the right submenu — don't create a new listener class.

`src/Menu/AdminMenuListener.php`:

```php
<?php

declare(strict_types=1);

namespace $SYLIUS_NAMESPACE\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function __invoke(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $submenu = $menu
            ->addChild('{submenu_key}')
            ->setLabel('${SYLIUS_PREFIX}.ui.{submenu_key}')
            ->setLabelAttribute('icon', '{submenu_icon}')   // remove this line if no icon
        ;

        $submenu
            ->addChild('${SYLIUS_PREFIX}_admin_{model_snake}_index', [
                'route' => '${SYLIUS_PREFIX}_admin_{model_snake}_index',
            ])
            ->setLabel('${SYLIUS_PREFIX}.ui.{model_snake_plural}')
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
    ${SYLIUS_PREFIX}.listener.admin.menu:
        class: $SYLIUS_NAMESPACE\Menu\AdminMenuListener
        tags:
            - { name: kernel.event_listener, event: sylius.menu.admin.main }
```

The listener is invokable (`__invoke`), so no `method:` attribute is needed on the tag.

## 3. Add translations

Get the project's default locale:

```bash
$SYLIUS_CONSOLE debug:container --parameter=kernel.default_locale
```

Add to `translations/messages.{locale}.yaml`:

```yaml
${SYLIUS_PREFIX}:
    ui:
        {submenu_key}: '{Submenu label}'
        {model_snake}: '{ModelName}'                        # singular — Sylius auto-uses it for create/show titles
        {model_snake_plural}: '{ModelName}s'                 # plural — Sylius auto-uses it for breadcrumbs
        manage_{model_snake_plural}: 'Manage {ModelName}s'   # subheader (also used by /sylius:add-routes)
```

The singular `${SYLIUS_PREFIX}.ui.{model_snake}` is auto-resolved by Sylius's admin templates for titles like "New {ModelName}". Must stay a scalar — never nest anything under it.

## 4. Clear cache

```bash
$SYLIUS_CONSOLE cache:clear
```

## 5. Verify

 - [ ] `$SYLIUS_CONSOLE debug:event-dispatcher sylius.menu.admin.main` lists `$SYLIUS_NAMESPACE\Menu\AdminMenuListener` among the registered listeners
- [ ] `$SYLIUS_CONSOLE debug:router ${SYLIUS_PREFIX}_admin_{model_snake}_index` resolves the target route
- [ ] `$SYLIUS_CONSOLE debug:translation {locale} --domain=messages 2>&1 | grep '${SYLIUS_PREFIX}.ui.'` lists every ui key you added.
