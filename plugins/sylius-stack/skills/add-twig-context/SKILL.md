---
name: add-twig-context
description: Add extra variables to the Twig template of an operation — related data, stats, preloaded references.
argument-hint: "[ModelName] [Operation]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Twig Context Factory — Sylius Stack

Ask the user if not provided:
- The resource (`ModelName`) and the target operation (typically `Show`, `Index`, `Create`, `Update`).
- The variable(s) to add and where the value comes from (related entities, repository call, aggregated counts, external service…).
- The factory class name. Default: `{Operation}{ModelName}ContextFactory` (e.g. `ShowBookContextFactory`).

If the user only needs to change the template path of an operation, **do not create a factory** — use the `template:` option on the operation instead:

```php
new Show(template: 'book/custom_show.html.twig')
```

---

## 1. Create the context factory

`src/Twig/Context/Factory/{FactoryName}.php`

```php
<?php

declare(strict_types=1);

namespace App\Twig\Context\Factory;

use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\Twig\Context\Factory\ContextFactoryInterface;

final class {FactoryName} implements ContextFactoryInterface
{
    public function __construct(
        private ContextFactoryInterface $decorated,
        // TODO: inject the services used to build your extra variables (repositories, services…)
    ) {
    }

    public function create(mixed $data, Operation $operation, Context $context): array
    {
        return array_merge($this->decorated->create($data, $operation, $context), [
            // TODO: add your extra variables here, e.g.:
            // 'related_books' => $this->bookRepository->findRelatedTo($data),
            // 'stats' => $this->statsService->for($data),
        ]);
    }
}
```

The decorated default factory is kept in the constructor so the existing context (`resource`, `{model_snake}`, `form`, `operation`, `resource_metadata`, …) remains available.

## 2. Wire the factory on the operation

Edit `src/Entity/{ModelName}.php`:

```php
use App\Twig\Context\Factory\{FactoryName};

new Show(twigContextFactory: {FactoryName}::class),
```

## 3. Use the new variables in the template

If the operation uses the default `@SyliusAdminUi/crud` templates, surface the new variables through a Twig Hook hookable — see `/sylius-stack:add-template`. Example, inside a hookable:

```twig
{% set related_books = hookable_metadata.context.related_books %}

<ul>
    {% for book in related_books %}
        <li>{{ book.title }}</li>
    {% endfor %}
</ul>
```

If the operation uses a custom template (`template: '...'`), the variables are accessible directly by name in that Twig file.

## 4. Clear the cache

```bash
bin/console cache:clear
```

## 5. Verify

```bash
bin/console sylius:debug:resource 'App\Entity\{ModelName}'
```

The operation's `twigContextFactory` row should display your class FQCN.

---

## Related

- To change the template path only → `template:` option on the operation, no factory needed.
- To override blocks of the default admin template → `/sylius-stack:add-template`.
- To return a non-HTML response (file, PDF, stream) → `/sylius-stack:add-responder`.