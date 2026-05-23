---
name: add-form
description: Add an admin FormType for an existing Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a FormType for a Sylius Resource

Ask the user for the ModelName if not provided. Read `src/**/Entity/{ModelName}/{ModelName}.php` to detect the entity's fields.

**Prerequisite:** the model must already exist as a Sylius Resource (run `/sylius-app:add-model` first).

## 1. Create the FormType

`src/Form/Type/{ModelName}/{ModelName}Type.php`:

```php
<?php

declare(strict_types=1);

namespace App\Form\Type\{ModelName};

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
        return 'app_{model_snake}';
    }
}
```

Read the entity fields and add the appropriate Symfony form types for each one (e.g. `TextType`, `TextareaType`, `IntegerType`, `CheckboxType`, etc.). Label each field with `app.form.{model_snake}.{field}`:

```php
$builder
    ->add('name', TextType::class, [
        'label' => 'app.form.{model_snake}.name',
    ])
    ->add('enabled', CheckboxType::class, [
        'label' => 'app.form.{model_snake}.enabled',
    ])
;
```

## 2. Register the service

Add to `config/services.yaml`:

```yaml
services:
    app.form.type.{model_snake}:
        class: App\Form\Type\{ModelName}\{ModelName}Type
        arguments:
            - '%app.model.{model_snake}.class%'
            - ['sylius']
        tags:
            - { name: form.type }
```

## 3. Declare the form in the resource config

Edit `config/packages/sylius_resource.yaml`. Add `form:` under the existing `classes:` for `app.{model_snake}`:

```yaml
sylius_resource:
    resources:
        app.{model_snake}:
            driver: doctrine/orm
            classes:
                model: App\Entity\{ModelName}\{ModelName}
                interface: App\Entity\{ModelName}\{ModelName}Interface
                form: App\Form\Type\{ModelName}\{ModelName}Type
```

## 4. Translation keys

Get the project's default locale:

```bash
bin/console debug:container --parameter=kernel.default_locale
```

Add labels to `translations/messages.{locale}.yaml` under `app.form.{model_snake}.{field}` (one entry per field declared in the FormType):

```yaml
app:
    form:
        {model_snake}:
            name: Name
            enabled: Enabled
            # one entry per field
```

## 5. Clear cache

```bash
bin/console cache:clear
```

## 6. Verify

- [ ] `bin/console debug:form "App\Form\Type\{ModelName}\{ModelName}Type"` lists the declared fields
- [ ] `bin/console debug:translation {locale} --domain=messages 2>&1 | grep 'app.form.{model_snake}'` lists every field label you added. 

## Next steps

- `/sylius-app:add-grid` to add the admin index grid
- Then `/sylius-app:add-routes` and `/sylius-app:add-menu` to wire it into the admin
- `/sylius-app:add-images` if the model needs an images collection
- `/sylius-app:add-autocomplete` to expose the model as a searchable field in another form