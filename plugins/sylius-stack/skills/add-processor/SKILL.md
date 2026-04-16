---
name: add-processor
description: Add custom logic on Create/Update/Delete operations — send an email, dispatch a command, publish an event, push to a queue.
argument-hint: "[ModelName] [Operation]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Custom Processor — Sylius Stack

Ask the user if not provided:
- The resource (`ModelName`) and the target operation (`Create`, `Update`, `Delete`, `BulkDelete`, …).
- The processor class name. Default: `{Operation}{ModelName}Processor` (e.g. `CreateCustomerProcessor`).
- Whether the processor should **decorate the default** (keep persist/remove behavior) or **fully replace it**. Default: decorate for Create/Update (keeps `PersistProcessor`), decorate for Delete/BulkDelete (keeps `RemoveProcessor`), full replace for DDD resources not backed by Doctrine.

If the user only needs to disable persistence (e.g. a preview operation), **do not create a processor** — suggest `write: false` on the operation instead:

```php
new Update(shortName: 'update_preview', write: false)
```

---

## 1. Create the processor class

### Decorating the default Doctrine processor

`src/Processor/{ProcessorName}.php` — use this when you want the default persistence to still run, and add side effects on top (email, event, queue…):

```php
<?php

declare(strict_types=1);

namespace App\Processor;

use App\Entity\{ModelName};
use Sylius\Resource\Context\Context;
use Sylius\Resource\Doctrine\Common\State\PersistProcessor;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProcessorInterface;
use Webmozart\Assert\Assert;

final class {ProcessorName} implements ProcessorInterface
{
    public function __construct(
        private PersistProcessor $decorated,
        // TODO: inject your side-effect dependencies (Mailer, CommandBus, MessageBus…)
    ) {
    }

    public function process(mixed $data, Operation $operation, Context $context): mixed
    {
        Assert::isInstanceOf($data, {ModelName}::class);

        $this->decorated->process($data, $operation, $context);

        // TODO: add your business logic (send email, dispatch command, publish event…)

        return null;
    }
}
```

For a `Delete` / `BulkDelete` operation, replace `PersistProcessor` with `RemoveProcessor`:

```php
use Sylius\Resource\Doctrine\Common\State\RemoveProcessor;

public function __construct(
    private RemoveProcessor $decorated,
) {}
```

### Fully replacing the default (no Doctrine)

Use this when the resource is not a Doctrine entity, or you want to bypass the default persistence entirely:

```php
<?php

declare(strict_types=1);

namespace App\Processor;

use App\Entity\{ModelName};
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ProcessorInterface;
use Webmozart\Assert\Assert;

final class {ProcessorName} implements ProcessorInterface
{
    public function __construct(
        // TODO: inject your dependencies (CommandBus, domain services…)
    ) {
    }

    public function process(mixed $data, Operation $operation, Context $context): mixed
    {
        Assert::isInstanceOf($data, {ModelName}::class);

        // TODO: dispatch your command / call your domain service

        return null;
    }
}
```

## 2. Wire the processor on the operation

Edit `src/Entity/{ModelName}.php`:

```php
use App\Processor\{ProcessorName};

new Create(processor: {ProcessorName}::class),
```

## 3. Clear the cache

```bash
bin/console cache:clear
```

## 4. Verify

```bash
bin/console sylius:debug:resource 'App\Entity\{ModelName}'
```

The operation's `processor` row should display your class FQCN.

---

## Related

- Need to also replace data loading? → `/sylius-stack:add-provider`
- Need to disable data reading on this operation (e.g. custom delete that does not need the entity)? Combine with `read: false` on the operation.
- Need to disable persistence on this operation (e.g. preview)? Use `write: false` and no processor is required.