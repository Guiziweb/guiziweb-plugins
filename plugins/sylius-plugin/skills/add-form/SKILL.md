---
name: add-form
description: Add an admin FormType for an existing Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a FormType for a Sylius Resource

Ask the user for the ModelName if not provided. Read `src/**/Entity/{ModelName}/{ModelName}.php` to detect the entity's fields.

**Prerequisite:** the model must already exist as a Sylius Resource (run `add-model` first).

## 1. Create the FormType

`src/Form/Type/{ModelName}/{ModelName}Type.php`:

```php
<?php

declare(strict_types=1);

namespace $SYLIUS_NAMESPACE\Form\Type\{ModelName};

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\FormBuilderInterface;

final class {ModelName}Type extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // add fields based on the entity properties
        ;
    }

    public function getBlockPrefix(): string
    {
        return '${SYLIUS_PREFIX}_{model_snake}';
    }
}
```

Read the entity fields and add the appropriate Symfony form types for each one (e.g. `TextType`, `TextareaType`, `IntegerType`, `CheckboxType`, etc.). Label each field with `${SYLIUS_PREFIX}.form.{model_snake}.{field}`:

```php
$builder
    ->add('name', TextType::class, [
        'label' => '${SYLIUS_PREFIX}.form.{model_snake}.name',
    ])
    ->add('enabled', CheckboxType::class, [
        'label' => '${SYLIUS_PREFIX}.form.{model_snake}.enabled',
    ])
;
```

## 2. Register the service

Add to `config/services.yaml`:

```yaml
services:
    ${SYLIUS_PREFIX}.form.type.{model_snake}:
        class: $SYLIUS_NAMESPACE\Form\Type\{ModelName}\{ModelName}Type
        arguments:
            - '%${SYLIUS_PREFIX}.model.{model_snake}.class%'
            - ['sylius']
        tags:
            - { name: form.type }
```

## 3. Declare the form in the resource config

Edit `config/packages/sylius_resource.yaml`. Add `form:` under the existing `classes:` for `${SYLIUS_PREFIX}.{model_snake}`:

```yaml
sylius_resource:
    resources:
        ${SYLIUS_PREFIX}.{model_snake}:
            driver: doctrine/orm
            classes:
                model: $SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}
                interface: $SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}Interface
                form: $SYLIUS_NAMESPACE\Form\Type\{ModelName}\{ModelName}Type
```

## 4. Translation keys

Get the project's default locale:

```bash
$SYLIUS_CONSOLE debug:container --parameter=kernel.default_locale
```

Add labels to `translations/messages.{locale}.yaml` under `${SYLIUS_PREFIX}.form.{model_snake}.{field}` (one entry per field declared in the FormType):

```yaml
${SYLIUS_PREFIX}:
    form:
        {model_snake}:
            name: Name
            enabled: Enabled
            # one entry per field
```

## 5. Clear cache

```bash
$SYLIUS_CONSOLE cache:clear
```

## 6. Verify

- [ ] `$SYLIUS_CONSOLE debug:form "$SYLIUS_NAMESPACE\Form\Type\{ModelName}\{ModelName}Type"` lists the declared fields
- [ ] `$SYLIUS_CONSOLE debug:translation {locale} --domain=messages 2>&1 | grep '${SYLIUS_PREFIX}.form.{model_snake}'` lists every field label you added. 

## Next steps

- `/sylius:add-grid` to add the admin index grid
- Then `/sylius:add-routes` and `/sylius:add-menu` to wire it into the admin
- `/sylius:add-images` if the model needs an images collection
- `/sylius:add-autocomplete` to expose the model as a searchable field in another form