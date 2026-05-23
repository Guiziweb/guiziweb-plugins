---
name: add-autocomplete
description: Add an admin autocomplete form type for a Sylius Resource (translatable or not), optionally injected into another form via an extension
argument-hint: "[ModelName] [TargetModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add an Autocomplete Type for a Sylius Resource

This skill is for exposing a resource as an autocomplete **in a form**. For a grid filter, use `ux_autocomplete` / `ux_translatable_autocomplete` directly in the grid YAML (see `/sylius:add-grid`) — no custom form type needed.

Ask the user for:
- **ModelName**: the resource to make searchable (e.g. `Article`)
- **TargetModelName**: the existing Sylius entity whose form should receive the field (e.g. `Product`). Optional — skip steps 3–4 if not provided.

Read `src/Entity/{ModelName}/{ModelName}.php` to detect the entity's fields and whether it uses `TranslatableTrait`.

**Prerequisites:** `add-model` (and `add-translatable-model` if applicable) must have been run first.

## Key rules

### For translatable entities (uses `TranslatableTrait`)
- Use `TranslatableAutocompleteType` as parent
- Always set `entity_fields: []` in `extra_options` — the default is `['code']`, which crashes if the entity has no `code` field
- Set `translation_fields` to the actual translated field names (e.g. `['title']`)
- Set `choice_label` directly via `$resolver->setDefault('choice_label', fn(Options $options) => ...)` — NOT inside `extra_options`

### For non-translatable entities
- Use `BaseEntityAutocompleteType` as parent
- Set `searchable_fields` via `$resolver->setDefault('searchable_fields', fn(Options $options) => [...])`

### Always
- Inject the model class via constructor (`%${SYLIUS_PREFIX}.model.{model_snake}.class%`)
- Register explicitly in `services.yaml` with both `form.type` and `ux.entity_autocomplete_field` tags — PHP attributes alone are not enough without autoconfigure
- `filter_query` is a PHP callable — it can NOT be passed via `extra_options` (only scalars/arrays travel via URL). Define it inside `configureOptions` instead.

## 1. Create the AutocompleteType

### Translatable entity

`src/Form/Type/{ModelName}/{ModelName}AutocompleteType.php`:

```php
<?php

declare(strict_types=1);

namespace $SYLIUS_NAMESPACE\Form\Type\{ModelName};

use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\AdminBundle\Form\Type\TranslatableAutocompleteType;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;

#[AsEntityAutocompleteField(
    alias: '${SYLIUS_PREFIX}_{model_snake}',
    route: 'sylius_admin_entity_autocomplete',
)]
class {ModelName}AutocompleteType extends AbstractType
{
    public function __construct(private readonly string ${model_snake}Class)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => $this->{model_snake}Class,
            'extra_options' => [
                'entity_fields' => [],                          // ⚠️ required if entity has no 'code' field
                'translation_fields' => ['{label_field}'],     // translated fields to search in
            ],
            // filter_query must be defined here — NOT via extra_options (no callables via URL)
            // 'filter_query' => function (QueryBuilder $qb, string $query, EntityRepository $repository): void {
            //     $qb->andWhere('entity.enabled = :enabled')->setParameter('enabled', true);
            // },
        ]);

        $resolver->setDefault('choice_label', function (Options $options): string {
            return $options['extra_options']['choice_label'] ?? '{label_field}';
        });
    }

    public function getBlockPrefix(): string
    {
        return '${SYLIUS_PREFIX}_{model_snake}_autocomplete';
    }

    public function getParent(): string
    {
        return TranslatableAutocompleteType::class;
    }
}
```

### Non-translatable entity

`src/Form/Type/{ModelName}/{ModelName}AutocompleteType.php`:

```php
<?php

declare(strict_types=1);

namespace $SYLIUS_NAMESPACE\Form\Type\{ModelName};

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField(
    alias: '${SYLIUS_PREFIX}_{model_snake}',
    route: 'sylius_admin_entity_autocomplete',
)]
class {ModelName}AutocompleteType extends AbstractType
{
    public function __construct(private readonly string ${model_snake}Class)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => $this->{model_snake}Class,
        ]);

        $resolver->setDefault('choice_label', function (Options $options): string {
            return $options['extra_options']['choice_label'] ?? '{label_field}';
        });

        $resolver->setDefault('searchable_fields', function (Options $options): array {
            return $options['extra_options']['searchable_fields'] ?? ['{label_field}'];
        });
    }

    public function getBlockPrefix(): string
    {
        return '${SYLIUS_PREFIX}_{model_snake}_autocomplete';
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
```

Replace `{label_field}` with the property used to display and search (e.g. `title`, `name`, `code`).

## 2. Register the service

Add to `config/services.yaml`:

```yaml
services:
    ${SYLIUS_PREFIX}.form.type.{model_snake}_autocomplete:
        class: $SYLIUS_NAMESPACE\Form\Type\{ModelName}\{ModelName}AutocompleteType
        arguments:
            - '%${SYLIUS_PREFIX}.model.{model_snake}.class%'
        tags:
            - { name: form.type }
            - { name: ux.entity_autocomplete_field, alias: '${SYLIUS_PREFIX}_{model_snake}' }
```

## 3. Inject the autocomplete into the target form (optional)

If a `TargetModelName` was given, expose the autocomplete as a field on that Sylius form. Run:

```
/sylius:extends-form {TargetModelName} {model_snake}
```

Pass `{ModelName}AutocompleteType` as the field type when prompted. For ManyToMany relations, also pass `multiple: true` in the form options.

`/sylius:extends-form` handles the `TypeExtension` class, service registration, Twig template, Twig hook and translation.

## 4. Add the `select_*` translation

Get the project's default locale:

```bash
$SYLIUS_CONSOLE debug:container --parameter=kernel.default_locale
```

Add the placeholder label used by the autocomplete UI to `translations/messages.{locale}.yaml`:

```yaml
${SYLIUS_PREFIX}:
    ui:
        select_{model_snake}: 'Select a {ModelName}'
```

## 5. Clear cache

```bash
$SYLIUS_CONSOLE cache:clear
```

## 6. Verify

- [ ] `$SYLIUS_CONSOLE debug:form "$SYLIUS_NAMESPACE\Form\Type\{ModelName}\{ModelName}AutocompleteType"` shows the type extends the parent (`TranslatableAutocompleteType` or `BaseEntityAutocompleteType`)
- [ ] `$SYLIUS_CONSOLE debug:container --tag=ux.entity_autocomplete_field | grep '${SYLIUS_PREFIX}_{model_snake}'` finds the alias in the registered autocomplete fields
- [ ] `$SYLIUS_CONSOLE debug:translation {locale} --domain=messages 2>&1 | grep '${SYLIUS_PREFIX}.ui.select_{model_snake}'` finds the placeholder key.

---

## Known framework gotchas

| Symptom | Cause | Fix |
|---------|-------|-----|
| `has no field named code` | `TranslatableAutocompleteType` defaults `entity_fields` to `['code']` — most Sylius entities have a code, custom ones often don't | Set `entity_fields: []` in `extra_options` |
| Empty results despite existing data | `TranslatableAutocompleteType` uses INNER JOIN on translations filtered by current locale — entities without a translation for that locale are excluded | Ensure entities have at least one translation in the active locale |
| `filter_query` option has no effect when passed at usage | `extra_options` is serialized and sent via URL — only scalars and arrays are supported, PHP callables are not | Define `filter_query` inside `configureOptions` of your autocomplete type, never at usage site |