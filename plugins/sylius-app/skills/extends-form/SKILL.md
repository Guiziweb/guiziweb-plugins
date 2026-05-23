---
name: extends-form
description: Expose a custom field in an existing Sylius admin form (Product, Taxon, Channel, …) via a Symfony FormTypeExtension, Twig hook and translation
argument-hint: "[TargetModel] [FieldName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Extend a Sylius admin form

Ask the user if not provided:
- **TargetModel**: the Sylius entity whose admin form receives the new field (e.g. `Product`, `Taxon`, `Channel`, `Customer`)
- **FieldName**: the property name, camelCase — must match the column on the entity (e.g. `metadataTitle`)
- **FieldType**: the Symfony form type to use — a scalar (`TextType`, `TextareaType`, `IntegerType`, `CheckboxType`) or a custom type such as `{Related}AutocompleteType` for a relation
- Whether the field is **required** (default: `false`)
- The **target section hook** (default: `general`). To list available sections, on Sylius 2.3+:
  ```bash
  bin/console sylius:debug:twig-hooks sylius_admin.{target_model_snake}
  ```
  On older versions, fall back to `bin/console debug:config sylius_twig_hooks`.

**Prerequisites**:
- The field must exist on the entity. Run `/sylius-app:extends-model` first for a Sylius core entity, or add the column on your own resource.
- For an entity-autocomplete field type, run `/sylius-app:add-autocomplete` first so `{Related}AutocompleteType` exists.

## 1. Identify the target FormType

```bash
bin/console sylius:debug:resource sylius.{target_model_snake}
```

The `classes.form` row in the output is the FormType FQCN used by the admin. Use it directly as the `use` statement and as the `getExtendedTypes()` return. Without an argument, the command lists every resource alias.

## 2. Create the FormTypeExtension

`src/Form/Extension/{TargetModel}/{TargetModel}TypeExtension.php`:

```php
<?php

declare(strict_types=1);

namespace App\Form\Extension\{TargetModel};

use {TargetFormFqcn};  // value returned by step 1
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\{FieldType};  // or your custom type
use Symfony\Component\Form\FormBuilderInterface;

final class {TargetModel}TypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('{field_name}', {FieldType}::class, [
            'label' => 'app.form.{target_model_snake}.{field_name}',
            'required' => {required},
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [{TargetModel}Type::class];
    }
}
```

## 3. Register the service

Add to `config/services.yaml`:

```yaml
services:
    app.form.extension.{target_model_snake}_type:
        class: App\Form\Extension\{TargetModel}\{TargetModel}TypeExtension
        tags:
            - { name: form.type_extension }
```

## 4. Create the Twig template

`templates/admin/{target_model_snake}/form/sections/{section}/{field_name}.html.twig`:

```twig
{{ form_row(hookable_metadata.context.form.{field_name}) }}
```

## 5. Register the Twig hooks (split by action)

One file per action under `config/packages/twig_hooks/{target_model_snake}/`. Loaded via the `imports:` block at the top of `config/packages/_sylius.yaml` (add `- { resource: "twig_hooks/**/*.yaml" }` if not already there).

If files already exist for this target (from a previous `extends-form` run), add the new entry under the existing hook key — do not overwrite.

`config/packages/twig_hooks/{target_model_snake}/create.yaml`:

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.{target_model_snake}.create.content.form.sections.{section}':
            {field_name}:
                template: 'admin/{target_model_snake}/form/sections/{section}/{field_name}.html.twig'
                priority: -100
```

`config/packages/twig_hooks/{target_model_snake}/update.yaml` — same content, replace `.create.` with `.update.`.

A negative priority places the field at the bottom of the section without renumbering existing entries.

## 6. Add the translation

Get the project's default locale:

```bash
bin/console debug:container --parameter=kernel.default_locale
```

Add to `translations/messages.{locale}.yaml`:

```yaml
app:
    form:
        {target_model_snake}:
            {field_name}: '{Field label}'
```

## 7. Clear cache

```bash
bin/console cache:clear
```

## 8. Verify

- [ ] `bin/console debug:form '{TargetFormFqcn}'` (FQCN from step 1) lists `{field_name}` among the type's fields
- [ ] `bin/console debug:translation {locale} --domain=messages 2>&1 | grep 'app.form.{target_model_snake}.{field_name}'` finds the label.

---

 ## Next steps

- Extending a Sylius core entity with a column → `/sylius-app:extends-model` (typical paired workflow: extend the entity, then expose the field here)
- Using an autocomplete type as the field → `/sylius-app:add-autocomplete`
- Displaying the new field in the admin grid → `/sylius-app:extends-grid`
