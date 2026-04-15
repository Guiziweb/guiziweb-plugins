---
name: add-autocomplete
description: Add an admin autocomplete form type for a Sylius Resource (translatable or not), usable in other forms via a form extension
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

## 3. Create a FormType extension for the target entity (optional)

If a `TargetModelName` was given, inject the field into an existing Sylius form via an extension.

First, identify the target FormType FQCN:
```bash
docker compose exec php vendor/bin/console debug:form 2>&1 | grep -i "{TargetModelName}Type"
```

`src/Form/Extension/{TargetModelName}TypeExtension.php`:

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Form\Extension;

use {Namespace}\Form\Type\{ModelName}AutocompleteType;
use Sylius\Bundle\{TargetBundle}\Form\Type\{TargetModelName}Type;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

final class {TargetModelName}TypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('{model_snake}', {ModelName}AutocompleteType::class, [
            'label' => '{plugin_alias}.form.{target_model_snake}.{model_snake}',
            'required' => false,
            // 'multiple' => true,  // uncomment for ManyToMany
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [{TargetModelName}Type::class];
    }
}
```

Register in `config/services.xml`:

```xml
<service id="{plugin_alias}.form.extension.{target_model_snake}_type"
         class="{Namespace}\Form\Extension\{TargetModelName}TypeExtension">
    <tag name="form.type_extension" />
</service>
```

> The autocomplete field also supports `'multiple' => true` for selecting several entities (ManyToMany relations).

---

## 4. Add Twig template + hook (optional)

Create `templates/admin/{target_model_snake}/form/sections/general/{model_snake}.html.twig`:

```twig
{{ form_row(hookable_metadata.context.form.{model_snake}) }}
```

Identify available section hook names:
```bash
docker compose exec php vendor/bin/console debug:config sylius_twig_hooks 2>&1 | grep "{target_model_snake}.*create\|{target_model_snake}.*update" | head -20
```

Create or update `config/twig_hooks/admin/{target_model_snake}.yaml`:

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.{target_model_snake}.create.content.form.sections.general':
            {model_snake}:
                template: '@{BundleName}/admin/{target_model_snake}/form/sections/general/{model_snake}.html.twig'
                priority: -100

        'sylius_admin.{target_model_snake}.update.content.form.sections.general':
            {model_snake}:
                template: '@{BundleName}/admin/{target_model_snake}/form/sections/general/{model_snake}.html.twig'
                priority: -100
```

> Use a negative priority to place the field at the bottom without renumbering existing entries.

---

## 5. Add translation keys

In `translations/messages.en_US.yaml`:

```yaml
{plugin_alias}:
    form:
        {target_model_snake}:
            {model_snake}: 'Related {ModelName}'
    ui:
        select_{model_snake}: 'Select a {ModelName}'
```

---

## 6. Verify

```bash
docker compose exec php vendor/bin/console cache:clear
docker compose exec php vendor/bin/console debug:container --tag=ux.entity_autocomplete_field 2>&1 | grep {model_snake}
```

Expected:
```
{plugin_alias}.form.type.{model_snake}_autocomplete   {plugin_alias}_{model_snake}   {Namespace}\Form\Type\{ModelName}AutocompleteType
```

Then open the target entity's admin form and type in the autocomplete field to confirm results appear.

---

## Known framework gotchas

| Symptom | Cause | Fix |
|---------|-------|-----|
| `has no field named code` | `TranslatableAutocompleteType` defaults `entity_fields` to `['code']` — most Sylius entities have a code, custom ones often don't | Set `entity_fields: []` in `extra_options` |
| Empty results despite existing data | `TranslatableAutocompleteType` uses INNER JOIN on translations filtered by current locale — entities without a translation for that locale are excluded | Ensure entities have at least one translation in the active locale |
| `filter_query` option has no effect when passed at usage | `extra_options` is serialized and sent via URL — only scalars and arrays are supported, PHP callables are not | Define `filter_query` inside `configureOptions` of your autocomplete type, never at usage site |