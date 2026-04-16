---
name: add-autocomplete
description: Add an autocomplete (simple ChoiceType or entity AJAX autocomplete) as a FormType field or as a grid filter
argument-hint: "[target: form|grid]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add an Autocomplete — Sylius Stack

The `sylius/bootstrap-admin-ui` package ships with Symfony UX Autocomplete already configured. There are two flavours:

- **Simple** — turn any `ChoiceType` into an autocomplete with `'autocomplete' => true`. No AJAX, no route, no dedicated form type.
- **Entity** — fetch options via AJAX from a Doctrine entity. Requires a dedicated route and an `AsEntityAutocompleteField` form type.

Ask the user:
1. **Kind**: simple or entity.
2. **Where to use it**: as a FormType field, as a grid filter, or both.
3. For entity: the related entity class, the label property (e.g. `fullName`, `title`) and — if used as a grid filter — the relation field used in the query (e.g. `speakers.id`).

---

## Simple autocomplete (ChoiceType)

Nothing to install. Just add the option on the `ChoiceType` field.

### In a FormType field

Edit the target `src/Form/{ModelName}Type.php`:

```php
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

$builder->add('{fieldName}', ChoiceType::class, [
    'choices' => [
        'Label A' => 'value_a',
        'Label B' => 'value_b',
    ],
    'placeholder' => 'sylius.ui.all',
    'autocomplete' => true,
]);
```

### In a grid filter

Create a custom form type extending `ChoiceType` and set `'autocomplete' => true` in `configureOptions()`:

```php
<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class {FilterName}Type extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                'Label A' => 'value_a',
                'Label B' => 'value_b',
            ],
            'placeholder' => 'sylius.ui.all',
            'autocomplete' => true,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
```

Then reference it in the grid's `withFilters()` via `Filter::create(name: '...', type: {FilterName}Type::class)`.

For a filter backed by a related Doctrine entity (dynamic list loaded via AJAX), prefer the entity autocomplete below.

---

## Entity autocomplete (AJAX)

### 1. Configure the autocomplete route (once per project)

Check if `ux_entity_autocomplete_admin` is already defined:

```bash
bin/console debug:router ux_entity_autocomplete_admin
```

If missing, add it. YAML:

```yaml
# config/routes/ux_autocomplete.yaml
ux_entity_autocomplete_admin:
    path: '/admin/autocomplete/{alias}'
    controller: 'ux.autocomplete.entity_autocomplete_controller'
```

PHP alternative:

```php
// config/routes/ux_autocomplete.php
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes
        ->add('ux_entity_autocomplete_admin', '/admin/autocomplete/{alias}')
        ->controller('ux.autocomplete.entity_autocomplete_controller')
    ;
};
```

### 2. Create the AutocompleteType (once per related entity)

Check if `src/Form/{RelatedEntity}AutocompleteType.php` already exists — this skill may have been run before for the same entity. If missing, create it:

```php
<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\{RelatedEntity};
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField(
    alias: 'app_admin_{related_snake}',
    route: 'ux_entity_autocomplete_admin',
)]
final class {RelatedEntity}AutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => {RelatedEntity}::class,
            'choice_label' => '{choiceLabel}',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
```

### 3a. Use as a FormType field

Edit `src/Form/{ModelName}Type.php`:

```php
use App\Form\{RelatedEntity}AutocompleteType;

// in buildForm():
$builder->add('{fieldName}', {RelatedEntity}AutocompleteType::class);
```

### 3b. Use as a grid filter

Create `src/Grid/Filter/{RelatedEntity}Filter.php`:

```php
<?php

declare(strict_types=1);

namespace App\Grid\Filter;

use App\Form\{RelatedEntity}AutocompleteType;
use Sylius\Component\Grid\Attribute\AsFilter;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Filter\EntityFilter;
use Sylius\Component\Grid\Filtering\FilterInterface;

#[AsFilter(
    formType: {RelatedEntity}AutocompleteType::class,
    template: '@SyliusBootstrapAdminUi/shared/grid/filter/select.html.twig',
)]
final class {RelatedEntity}Filter implements FilterInterface
{
    public function __construct(
        private readonly EntityFilter $entityFilter,
    ) {
    }

    public function apply(DataSourceInterface $dataSource, string $name, mixed $data, array $options): void
    {
        $this->entityFilter->apply($dataSource, $name, $data, $options);
    }
}
```

Edit the target `src/Grid/Admin{ModelName}Grid.php` and register the filter in `withFilters()`:

```php
use App\Grid\Filter\{RelatedEntity}Filter;
use Sylius\Bundle\GridBundle\Builder\Filter\Filter;

Filter::create(name: '{related_snake}', type: {RelatedEntity}Filter::class)
    ->setLabel('app.ui.{related_snake}')
    ->setOptions(['fields' => ['{relation_field}']]),
```

Add the translation key in `translations/messages.en.yaml`:

```yaml
app:
    ui:
        {related_snake}: '{Related entity label}'
```

---

## Clear the cache

```bash
bin/console cache:clear
```

## Verify

```bash
bin/console debug:router ux_entity_autocomplete_admin
bin/console debug:form 'App\Form\{RelatedEntity}AutocompleteType'
```

For a grid filter, also check:

```bash
bin/console sylius:debug:grid 'App\Grid\Admin{ModelName}Grid'
```

The filter you added should appear in the grid definition.