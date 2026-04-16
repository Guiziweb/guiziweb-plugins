---
name: add-grid-export
description: Add a CSV export button to an existing admin grid.
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Grid Export — Sylius Stack

Ask the user for the ModelName if not provided.

> **Prerequisite:** The resource, its grid and an `Index` operation must already exist. Run `/sylius-stack:add-resource`, `/sylius-stack:add-grid` then `/sylius-stack:add-operation Index` first if needed.

---

## 1. Install league/csv

```bash
composer require league/csv
```

---

## 2. Create the Responder

Create `src/Responder/ExportGridToCsvResponder.php`:

```php
<?php

declare(strict_types=1);

namespace App\Responder;

use League\Csv\Writer;
use Pagerfanta\PagerfantaInterface;
use Sylius\Component\Grid\Definition\Field;
use Sylius\Component\Grid\Renderer\GridRendererInterface;
use Sylius\Component\Grid\View\GridViewInterface;
use Sylius\Resource\Context\Context;
use Sylius\Resource\Metadata\Operation;
use Sylius\Resource\State\ResponderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

final readonly class ExportGridToCsvResponder implements ResponderInterface
{
    public function __construct(
        #[Autowire(service: 'sylius.grid.renderer')]
        private GridRendererInterface $gridRenderer,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param GridViewInterface $data
     */
    public function respond(mixed $data, Operation $operation, Context $context): mixed
    {
        Assert::isInstanceOf($data, GridViewInterface::class);

        $response = new StreamedResponse(function () use ($data) {
            $output = fopen('php://output', 'w');

            if (false === $output) {
                throw new \RuntimeException('Unable to open output stream.');
            }

            $writer = Writer::from($output);

            $fields = $this->sortFields($data->getDefinition()->getFields());
            $this->writeHeaders($writer, $fields);
            $this->writeRows($writer, $fields, $data);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export.csv"');

        return $response;
    }

    /**
     * @param Field[] $fields
     */
    private function writeHeaders(Writer $writer, array $fields): void
    {
        $labels = array_map(fn (Field $field) => $this->translator->trans($field->getLabel()), $fields);

        $writer->insertOne($labels);
    }

    /**
     * @param Field[] $fields
     */
    private function writeRows(Writer $writer, array $fields, GridViewInterface $gridView): void
    {
        /** @var PagerfantaInterface $paginator */
        $paginator = $gridView->getData();
        Assert::isInstanceOf($paginator, PagerfantaInterface::class);

        for ($currentPage = 1; $currentPage <= $paginator->getNbPages(); ++$currentPage) {
            $paginator->setCurrentPage($currentPage);
            $this->writePageResults($writer, $fields, $gridView, $paginator->getCurrentPageResults());
        }
    }

    /**
     * @param Field[] $fields
     * @param iterable<object> $pageResults
     */
    private function writePageResults(Writer $writer, array $fields, GridViewInterface $gridView, iterable $pageResults): void
    {
        foreach ($pageResults as $resource) {
            $rows = [];
            foreach ($fields as $field) {
                $rows[] = $this->getFieldValue($gridView, $field, $resource);
            }
            $writer->insertOne($rows);
        }
    }

    private function getFieldValue(GridViewInterface $gridView, Field $field, object $data): string
    {
        $renderedData = $this->gridRenderer->renderField($gridView, $field, $data);
        $renderedData = str_replace(\PHP_EOL, '', $renderedData);

        return trim(strip_tags($renderedData));
    }

    /**
     * @param Field[] $fields
     *
     * @return Field[]
     */
    private function sortFields(array $fields): array
    {
        $sortedFields = $fields;

        uasort($sortedFields, fn (Field $fieldA, Field $fieldB) => $fieldA->getPosition() <=> $fieldB->getPosition());

        return $sortedFields;
    }
}
```

---

## 3. Add the export operation to the entity

Edit `src/Entity/{ModelName}.php` — keep the existing `Index` operation and add a second one with `shortName: 'export'` and the responder:

```php
use App\Responder\ExportGridToCsvResponder;

// in the operations array:
new Index(
    grid: Admin{ModelName}Grid::class,
),
new Index(
    shortName: 'export',
    responder: ExportGridToCsvResponder::class,
    grid: Admin{ModelName}Grid::class,
),
```

See `/sylius-stack:add-operation` for the general mechanism of adding operations to a resource.

---

## 4. Add the export action to the grid

Edit `src/Grid/Admin{ModelName}Grid.php` — add the export action in `MainActionGroup`:

```php
use Sylius\Bundle\GridBundle\Builder\Action\Action;

// in MainActionGroup:
MainActionGroup::create(
    CreateAction::create(),
    Action::create('export', 'export')
        ->setTemplate('shared/grid/action/export.html.twig'),
)
```

---

## 5. Create the export action Twig template

Create `templates/shared/grid/action/export.html.twig`:

```twig
{% set path = options.link.url|default(path(options.link.route|default(grid.requestConfiguration.getRouteName('export')), options.link.parameters|default([]))) %}

{% set message = action.label %}
{% if message is empty %}
    {% set message = 'app.ui.export' %}
{% endif %}

<a href="{{ path }}?{{ app.request.query.all()|url_encode }}" class="btn">
    {{ ux_icon(action.icon|default('iwwa:csv'), {class: 'icon dropdown-item-icon'}) }}
    {{ message|trans }}
</a>
```

> **Note:** To avoid repeating `->setTemplate(...)` in every grid, you can configure the template globally instead:
>
> ```yaml
> # config/packages/sylius_grid.yaml
> sylius_grid:
>     templates:
>         action:
>             export: 'shared/grid/action/export.html.twig'
> ```
>
> If you add this global config, remove the `->setTemplate(...)` call from the grid.

---

## 6. Add the translation key

Create or update `translations/messages.en.yaml`:

```yaml
app:
    ui:
        export: 'Export'
```

---

## 7. Clear the cache

```bash
bin/console cache:clear
```

## 8. Verify

```bash
bin/console debug:router app_admin_{model_snake}_export
bin/console sylius:debug:resource 'App\Entity\{ModelName}'
```

The route should exist and the resource metadata should list two `Index` operations (one default, one with `shortName: 'export'`).