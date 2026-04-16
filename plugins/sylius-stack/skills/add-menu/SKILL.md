---
name: add-menu
description: Add an admin sidebar menu entry for an existing Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Menu Entry — Sylius Stack

Ask the user for the ModelName if not provided.

Ask the user:
- The section key (e.g. `library`) — used as `app.ui.library` translation key
- The icon for the section (tabler icon name, e.g. `tabler:books`)
- The icon for the item (e.g. `book`)
- Whether the section should always be expanded (`always_open`)

---

## 1. Create or update `src/Menu/MenuBuilder.php`

Check if `src/Menu/MenuBuilder.php` exists.

- **If it does not exist** — create it with the dashboard entry and the new section method.
- **If it exists** — add a call to the new section method in `createMenu()` and append the private method.

```php
<?php

declare(strict_types=1);

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Sylius\AdminUi\Knp\Menu\MenuBuilderInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: 'sylius_admin_ui.knp.menu_builder')]
final readonly class MenuBuilder implements MenuBuilderInterface
{
    public function __construct(
        private FactoryInterface $factory,
    ) {
    }

    public function createMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $menu
            ->addChild('dashboard', ['route' => 'sylius_admin_ui_dashboard'])
            ->setLabel('sylius.ui.dashboard')
            ->setLabelAttribute('icon', 'tabler:dashboard')
        ;

        $this->add{Section}SubMenu($menu);

        return $menu;
    }

    private function add{Section}SubMenu(ItemInterface $menu): void
    {
        $section = $menu
            ->addChild('{section_key}')
            ->setLabel('app.ui.{section_key}')
            ->setLabelAttribute('icon', '{section_icon}')
            // ->setExtra('always_open', true) // uncomment to expand by default
        ;

        $section
            ->addChild('{model_snake_plural}', ['route' => 'app_admin_{model_snake}_index'])
            ->setLabel('app.ui.{model_snake_plural}')
            ->setLabelAttribute('icon', '{item_icon}')
        ;
    }
}
```

---

## 2. Add translation keys

Create or update `translations/messages.en.yaml` with the keys used in the menu:

```yaml
app:
    ui:
        {section_key}: '{Section label}'
        {model_snake_plural}: '{Item label}'
```

---

## 3. Clear the cache

```bash
bin/console cache:clear
```

## 4. Verify

The menu entry links to the resource's index route — confirm it exists:

```bash
bin/console debug:router app_admin_{model_snake}_index
```

If the route is missing, the index operation has not been added yet — run `/sylius-stack:add-operation Index` (and `/sylius-stack:add-grid` before it if the grid does not exist).

Confirm the `MenuBuilder` decorator is wired:

```bash
bin/console debug:container sylius_admin_ui.knp.menu_builder
```

The service should resolve to `App\Menu\MenuBuilder` and the `Tags` row should include `container.decorator (id: sylius_admin_ui.knp.menu_builder, ...)`.