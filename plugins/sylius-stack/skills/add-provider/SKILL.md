---
name: add-provider
description: Load a resource from a non-Doctrine source (external API, command bus, DDD repository) for Show/Index/Update/Delete operations.
argument-hint: "[ModelName] [Operation]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Custom Provider — Sylius Stack

Ask the user if not provided:
- The resource (`ModelName`) and the target operation (`Index`, `Show`, `Update`, etc.)
- The provider class name. Default: `{ModelName}{Operation}Provider` (e.g. `BookShowProvider`).
- The namespace / path. Default: `src/Provider/` (namespace `App\Provider`). For a DDD layout, ask for the infrastructure path (e.g. `src/BoardGameBlog/Infrastructure/Sylius/State/Provider/`).

If the user only needs a different repository method, **do not create a provider** — suggest the operation flags instead:

```php
new Show(
    repositoryMethod: 'findOneBySlug',
    repositoryArguments: ['slug' => "request.attributes.get('slug')"],
)
```

---

## 1. Create the provider class

`src/Provider/{ProviderName}.php`

```php
<?php

declare(strict_types=1);

namespace App\Provider;

use Sylius\Resource\Context\Context;
use Sylius\Resource\Context\Option\RequestOption;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProviderInterface;
use Webmozart\Assert\Assert;

final class {ProviderName} implements ProviderInterface
{
    public function __construct(
        // TODO: inject the dependencies you need (QueryBus, HttpClient, Repository…)
    ) {
    }

    public function provide(Operation $operation, Context $context): object|array|null
    {
        $request = $context->get(RequestOption::class)?->request();
        Assert::notNull($request);

        // TODO: build and return the resource (or a collection for Index operations)
        //       return null if the resource cannot be found
        return null;
    }
}
```

Adjust the constructor and the `provide()` body with the real lookup logic. For an `Index` operation, return an iterable/paginator; for item operations (`Show`, `Update`, `Delete`), return a single object or `null`.

## 2. Wire the provider on the operation

Edit `src/Entity/{ModelName}.php` — add the `provider:` argument on the target operation:

```php
use App\Provider\{ProviderName};

new Show(provider: {ProviderName}::class),
```

If the resource is not a Doctrine entity, also ensure the operation does not rely on the default Doctrine processor — see `/sylius-stack:add-processor` to pair it with a custom processor.

## 3. Clear the cache

```bash
bin/console cache:clear
```

## 4. Verify

```bash
bin/console sylius:debug:resource 'App\Entity\{ModelName}'
```

The operation's `provider` row should display your class FQCN.

---

## Related

- Need to also replace the persistence logic? → `/sylius-stack:add-processor`
- Just need to disable data reading (e.g. custom delete)? → add `read: false` on the operation, no provider required.