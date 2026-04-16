---
name: add-responder
description: Return a non-standard HTTP response (PDF, XLSX, stream, binary) instead of the default HTML template.
argument-hint: "[ModelName] [Operation]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Custom Responder — Sylius Stack

Ask the user if not provided:
- The resource (`ModelName`) and the target operation.
- The responder class name. Default: `{Purpose}Responder` (e.g. `ExportGridToXlsxResponder`, `BookPdfResponder`).
- The format to return (file stream, PDF, XLSX, raw JSON with a specific structure, redirect, …). This drives the body.

If the user only needs a custom redirect after a Create/Update/Delete, **do not create a responder** — suggest the operation flags:

```php
new Create(
    redirectToRoute: 'app_admin_author_show',
    redirectArguments: ['id' => 'resource.getAuthor().getId()'],
)
```

If the user only needs extra variables in the default Twig template, use `/sylius-stack:add-twig-context` instead.

---

## 1. Create the responder class

`src/Responder/{ResponderName}.php`

```php
<?php

declare(strict_types=1);

namespace App\Responder;

use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ResponderInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class {ResponderName} implements ResponderInterface
{
    public function __construct(
        // TODO: inject the services needed to build the response (renderer, http client, serializer…)
    ) {
    }

    public function respond(mixed $data, Operation $operation, Context $context): mixed
    {
        // TODO: transform $data into a Symfony Response and return it
        //       examples: new Response($body), new StreamedResponse(...), new BinaryFileResponse(...)
        return new Response();
    }
}
```

For a streaming / download response, follow this pattern (see also `add-grid-export` for a concrete CSV example):

```php
use Symfony\Component\HttpFoundation\StreamedResponse;

$response = new StreamedResponse(function () use ($data) {
    // write bytes to php://output
});
$response->headers->set('Content-Type', 'application/pdf');
$response->headers->set('Content-Disposition', 'attachment; filename="export.pdf"');

return $response;
```

## 2. Wire the responder on the operation

Edit `src/Entity/{ModelName}.php`:

```php
use App\Responder\{ResponderName};

new Index(
    shortName: 'pdf',
    responder: {ResponderName}::class,
),
```

Use a distinct `shortName` when the responder coexists with the default operation (e.g. HTML index + PDF export on the same resource).

## 3. Clear the cache

```bash
bin/console cache:clear
```

## 4. Verify

```bash
bin/console sylius:debug:resource 'App\Entity\{ModelName}'
bin/console debug:router | grep {model_snake}
```

The operation's `responder` row should display your class FQCN and the corresponding route should appear.

---

## Related

- CSV grid export → `/sylius-stack:add-grid-export` (specialization of this pattern)
- Add variables to the default Twig template → `/sylius-stack:add-twig-context`
- Custom redirect only → use `redirectToRoute` / `redirectArguments` flags on the operation, no responder required.