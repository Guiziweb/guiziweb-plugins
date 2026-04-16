---
name: add-factory
description: Control how a resource is instantiated on Create — set defaults (timestamps, status), inject the creator, pre-fill relations from the URL.
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Custom Factory — Sylius Stack

Ask the user if not provided:
- The resource (`ModelName`).
- What the factory should do: set defaults (dates, status), inject the logged-in user, preload a related entity from the URL, …
- The factory class name. Default: `{ModelName}Factory`.
- Whether it replaces `createNew()` only, or also exposes a **custom method** (e.g. `createForAuthor(string $authorId)`) to be called via `factoryMethod` on the operation.

---

## 1. Create the factory class

`src/Factory/{ModelName}Factory.php`

```php
<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\{ModelName};
use Sylius\Resource\Factory\FactoryInterface;

final class {ModelName}Factory implements FactoryInterface
{
    public function __construct(
        // TODO: inject your dependencies (Security, related repositories, clocks…)
    ) {
    }

    public function createNew(): {ModelName}
    {
        $resource = new {ModelName}();
        // TODO: set defaults (timestamps, status, owner…)

        return $resource;
    }
}
```

### Add a custom factory method (optional)

If the user needs a method that receives arguments (e.g. a route parameter), add it alongside `createNew()`:

```php
public function createForAuthor(string $authorId): {ModelName}
{
    $resource = $this->createNew();
    // TODO: resolve the Author via an injected repository and attach it
    return $resource;
}
```

## 2. Register the factory

Two options — pick one:

### (a) Decorate the default factory (default `createNew()` is used for every Create)

`config/services.yaml`:

```yaml
services:
    App\Factory\{ModelName}Factory:
        decorates: 'app.factory.{model_snake}'
```

Your factory replaces the default for this resource everywhere it is auto-wired or used.

### (b) Wire directly on the operation (no decoration needed)

```php
use App\Factory\{ModelName}Factory;

new Create(
    factory: {ModelName}Factory::class,
    factoryMethod: 'createForAuthor',                                    // optional
    factoryArguments: ['authorId' => "request.attributes.get('authorId')"], // optional, for custom methods with args
),
```

`factoryArguments` uses Symfony Expression Language. Available variables:
- `request` — `Symfony\Component\HttpFoundation\Request`
- `token` — authentication token
- `user` — current user

## 3. Clear the cache

```bash
bin/console cache:clear
```

## 4. Verify

For option (a) — confirm the decorator is applied:

```bash
bin/console debug:container app.factory.{model_snake}
```

The resolved class should be `App\Factory\{ModelName}Factory` and the `Tags` row should contain `container.decorator`.

For option (b) — confirm the operation wiring:

```bash
bin/console sylius:debug:resource 'App\Entity\{ModelName}'
```

The `Create` operation's `factory` / `factoryMethod` / `factoryArguments` rows should show your configuration.

---

## Related

- Only needed on `Create` operations. Other operations do not use a factory.
- For loading / fetching a resource (not creating), see `/sylius-stack:add-provider`.
- For side effects after creation (email, event, queue), see `/sylius-stack:add-processor`.