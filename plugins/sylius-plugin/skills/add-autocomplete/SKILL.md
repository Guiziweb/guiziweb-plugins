---
name: add-autocomplete
description: Add an admin autocomplete form type for a Sylius Resource (translatable or not), optionally injected into another form via an extension
argument-hint: "[ModelName] [TargetModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add an Autocomplete Type for a Sylius Resource

Ask the user for:
- **ModelName**: the resource to make searchable (e.g. `Article`)
- **TargetModelName**: the existing Sylius entity whose form should receive the field (e.g. `Product`). Optional — skip steps 3–5 if not provided.

Read `composer.json` and `src/Entity/{ModelName}.php` to detect the plugin namespace, plugin alias, and whether the entity is translatable (uses `TranslatableTrait`).

**Prerequisites:** `add-model` (and `add-translatable-model` if applicable) must have been run first.

---

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
- Inject the model class via constructor (`%{plugin_alias}.model.{model_snake}.class%`)
- Register explicitly in `services.xml` with both `form.type` and `ux.entity_autocomplete_field` tags — PHP attributes alone are not enough without autoconfigure
- `filter_query` is a PHP callable — it can NOT be passed via `extra_options` (only scalars/arrays travel via URL). Define it inside `configureOptions` instead.

---

## 1. Create the AutocompleteType

### Translatable entity

`src/Form/Type/{ModelName}AutocompleteType.php`:

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Form\Type;

use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\AdminBundle\Form\Type\TranslatableAutocompleteType;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;

#[AsEntityAutocompleteField(
    alias: '{plugin_alias}_{model_snake}',
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
        return '{plugin_alias}_{model_snake}_autocomplete';
    }

    public function getParent(): string
    {
        return TranslatableAutocompleteType::class;
    }
}
```

### Non-translatable entity

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField(
    alias: '{plugin_alias}_{model_snake}',
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
        return '{plugin_alias}_{model_snake}_autocomplete';
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
```

> Replace `{label_field}` with the property used to display and search (e.g. `title`, `name`, `code`).

---

## 2. Register in `config/services.xml`

```xml
<service id="{plugin_alias}.form.type.{model_snake}_autocomplete"
         class="{Namespace}\Form\Type\{ModelName}AutocompleteType">
    <argument>%{plugin_alias}.model.{model_snake}.class%</argument>
    <tag name="form.type" />
    <tag name="ux.entity_autocomplete_field" alias="{plugin_alias}_{model_snake}" />
</service>
```

---

## 3. Inject the autocomplete into the target form (optional)

If a `TargetModelName` was given, expose the autocomplete as a field on that Sylius form. Run:

```
/sylius-plugin:add-form-extension {TargetModelName} {model_snake}
```

Pass `{ModelName}AutocompleteType` as the field type when prompted. For ManyToMany relations, also pass `multiple: true` in the form options.

`/sylius-plugin:add-form-extension` handles the `TypeExtension` class, `services.xml` registration, Twig template, Twig hook and translation.

## 4. Add the `select_*` translation

In `translations/messages.en_US.yaml` add the placeholder / select-a-* label used by the autocomplete UI:

```yaml
{plugin_alias}:
    ui:
        select_{model_snake}: 'Select a {ModelName}'
```

---

## 5. Verify

```bash
docker compose exec php vendor/bin/console cache:clear
docker compose exec php vendor/bin/console debug:container '{plugin_alias}.form.type.{model_snake}_autocomplete'
```

The service should resolve to `{Namespace}\Form\Type\{ModelName}AutocompleteType` and its `Tags` row should include `form.type` and `ux.entity_autocomplete_field` (with alias `{plugin_alias}_{model_snake}`).

---

## Known framework gotchas

| Symptom | Cause | Fix |
|---------|-------|-----|
| `has no field named code` | `TranslatableAutocompleteType` defaults `entity_fields` to `['code']` — most Sylius entities have a code, custom ones often don't | Set `entity_fields: []` in `extra_options` |
| Empty results despite existing data | `TranslatableAutocompleteType` uses INNER JOIN on translations filtered by current locale — entities without a translation for that locale are excluded | Ensure entities have at least one translation in the active locale |
| `filter_query` option has no effect when passed at usage | `extra_options` is serialized and sent via URL — only scalars and arrays are supported, PHP callables are not | Define `filter_query` inside `configureOptions` of your autocomplete type, never at usage site |