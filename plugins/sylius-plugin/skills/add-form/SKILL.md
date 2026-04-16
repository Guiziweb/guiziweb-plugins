---
name: add-form
description: Add an admin FormType for an existing Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a FormType for a Sylius Resource

Ask the user for the ModelName if not provided. Read `composer.json` and `src/**/Entity/{ModelName}.php` to detect the plugin namespace, plugin alias, and the entity's fields.

**Prerequisite:** the model must already exist as a Sylius Resource (run `add-model` first).

---

## 1. Create the FormType

`src/Form/Type/{ModelName}Type.php`:

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Form\Type;

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
        return '{plugin_alias}_{model_snake}';
    }
}
```

Read the entity fields and add the appropriate Symfony form types for each one (e.g. `TextType`, `TextareaType`, `IntegerType`, `CheckboxType`, etc.).

---

## 2. Register in `config/services.xml`

```xml
<service id="{plugin_alias}.form.type.{model_snake}"
         class="{Namespace}\Form\Type\{ModelName}Type">
    <argument>%{plugin_alias}.model.{model_snake}.class%</argument>
    <argument type="collection"><argument>sylius</argument></argument>
    <tag name="form.type" />
</service>
```

---

## 3. Declare in `config/resources.yaml`

Add the `form` class to the existing resource declaration:

```yaml
sylius_resource:
    resources:
        {plugin_alias}.{model_snake}:
            driver: doctrine/orm
            classes:
                model: {Namespace}\Entity\{ModelName}
                form: {Namespace}\Form\Type\{ModelName}Type
```

---

## 4. Verify

```bash
docker compose exec php vendor/bin/console cache:clear
docker compose exec php vendor/bin/console debug:form "{Namespace}\Form\Type\{ModelName}Type"
```

