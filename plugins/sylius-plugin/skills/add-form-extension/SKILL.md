---
name: add-form-extension
description: Expose a custom field in an existing Sylius admin form (Product, Taxon, Channel, …) via a Symfony FormTypeExtension, Twig hook and translation
argument-hint: "[TargetModel] [FieldName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Form Extension to a Sylius admin form

Ask the user if not provided:
- **TargetModel**: the Sylius entity whose admin form receives the new field (e.g. `Product`, `Taxon`, `Channel`, `Customer`).
- **FieldName**: the property name (camelCase, matches the column on the entity, e.g. `metadataTitle`).
- **FieldType**: the Symfony form type to use — a scalar like `TextType`, `TextareaType`, `IntegerType`, `CheckboxType`, or a custom type such as `{Related}AutocompleteType` for a relation.
- Whether the field is **required** (default: `false`).
- The **target section hook** (default: `general`). Identify available hooks with:
  ```bash
  docker compose exec php vendor/bin/console debug:config sylius_twig_hooks 2>&1 \
    | grep "{target_model_snake}\\.(create|update)\\.content\\.form\\.sections" | head -10
  ```

Read `composer.json` to detect the plugin namespace, plugin alias and bundle name.

**Prerequisites**:
- The field must exist on the entity. Run `/sylius-plugin:extends-model` first if the target is a Sylius core entity, or add the column on your own resource.
- For an entity-autocomplete field type, run `/sylius-plugin:add-autocomplete` first so `{Related}AutocompleteType` exists.

---

## 1. Create the FormTypeExtension

`src/Form/Extension/{TargetModel}TypeExtension.php`:

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Form\Extension;

use Sylius\Bundle\{TargetBundle}\Form\Type\{TargetModel}Type;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\{FieldType};  // or your custom type
use Symfony\Component\Form\FormBuilderInterface;

final class {TargetModel}TypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('{field_name}', {FieldType}::class, [
            'label' => '{plugin_alias}.form.{target_model_snake}.{field_name}',
            'required' => {required},
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [{TargetModel}Type::class];
    }
}
```

Identify the target FormType FQCN (to find the correct `{TargetBundle}`):

```bash
docker compose exec php vendor/bin/console debug:form | grep -i "{TargetModel}Type"
```

Common bundles: `CoreBundle`, `ProductBundle`, `TaxonomyBundle`, `ChannelBundle`, `CustomerBundle`.

## 2. Register in `config/services.xml`

```xml
<service id="{plugin_alias}.form.extension.{target_model_snake}_type"
         class="{Namespace}\Form\Extension\{TargetModel}TypeExtension">
    <tag name="form.type_extension" />
</service>
```

## 3. Create the Twig template

`templates/admin/{target_model_snake}/form/sections/{section}/{field_name}.html.twig`:

```twig
{{ form_row(hookable_metadata.context.form.{field_name}) }}
```

## 4. Register the Twig hook

Create or update `config/twig_hooks/admin/{target_model_snake}.yaml`:

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.{target_model_snake}.create.content.form.sections.{section}':
            {field_name}:
                template: '@{BundleName}/admin/{target_model_snake}/form/sections/{section}/{field_name}.html.twig'
                priority: -100

        'sylius_admin.{target_model_snake}.update.content.form.sections.{section}':
            {field_name}:
                template: '@{BundleName}/admin/{target_model_snake}/form/sections/{section}/{field_name}.html.twig'
                priority: -100
```

> A negative priority places the field at the bottom of the section without renumbering existing entries.

## 5. Add the translation

Update `translations/messages.en_US.yaml`:

```yaml
{plugin_alias}:
    form:
        {target_model_snake}:
            {field_name}: '{Field label}'
```

## 6. Clear the cache

```bash
docker compose exec php vendor/bin/console cache:clear
```

## 7. Verify

```bash
docker compose exec php vendor/bin/console debug:container '{plugin_alias}.form.extension.{target_model_snake}_type'
```

The service should resolve to `{Namespace}\Form\Extension\{TargetModel}TypeExtension` and its `Tags` row should include `form.type_extension`.

```bash
docker compose exec php vendor/bin/console debug:form 'Sylius\Bundle\{TargetBundle}\Form\Type\{TargetModel}Type'
```

The output should list `{field_name}` among the type's fields.

---

## Related

- Extending a Sylius core entity → `/sylius-plugin:extends-model`
- Using an autocomplete type as the field → `/sylius-plugin:add-autocomplete`