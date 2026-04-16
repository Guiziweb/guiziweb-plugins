---
name: add-translatable-model
description: Make an existing Sylius Resource translatable, with TranslationType and Twig hooks
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Make a Sylius Resource translatable

Ask the user for the ModelName if not provided. Ask for the **translatable fields** (the fields that vary per locale — they move from the main entity to the translation entity).

Read `composer.json` and existing source files to detect the plugin namespace, plugin alias, and DB prefix.

**Prerequisites:** `add-model` and `add-form` must have been run first.

---

## How Sylius handles translatable entities

Sylius automatically injects the `OneToMany`/`ManyToOne` relation between the entity and its translation via `ORMTranslatableListener` at runtime — **do not map these relations manually**. The listener also injects the `locale` field on the translation table.

---

## 1. Create the translation interface

`src/Entity/{ModelName}TranslationInterface.php`:

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Entity;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TranslationInterface;

interface {ModelName}TranslationInterface extends ResourceInterface, TranslationInterface
{
    // declare getters and setters for each translatable field
}
```

---

## 2. Create the translation entity

`src/Entity/{ModelName}Translation.php`:

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\AbstractTranslation;

#[ORM\Entity]
#[ORM\Table(name: '{db_prefix}_{model_snake}_translation')]
class {ModelName}Translation extends AbstractTranslation implements {ModelName}TranslationInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // translatable fields with ORM\Column + getters + setters

    public function getId(): ?int
    {
        return $this->id;
    }
}
```

**Do NOT declare `$translatable`, `$locale`, or the relation to the parent entity** — Sylius injects these automatically.

---

## 3. Update the main entity

The entity already exists. Add `TranslatableTrait` + `TranslatableInterface`, remove the translatable fields' `#[ORM\Column]` annotations, and delegate their accessors to `$this->getTranslation()`:

```php
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslatableTrait;
use Sylius\Resource\Model\TranslationInterface;

class {ModelName} implements {ModelName}Interface, TranslatableInterface
{
    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;
    }

    public function __construct()
    {
        $this->initializeTranslationsCollection();
    }

    // translatable fields: remove ORM\Column, delegate to getTranslation()
    // public function getName(): ?string { return $this->getTranslation()->getName(); }
    // public function setName(?string $name): void { $this->getTranslation()->setName($name); }

    protected function createTranslation(): TranslationInterface
    {
        return new {ModelName}Translation();
    }
}
```

**Do NOT redeclare `$translations`** — already defined in `TranslatableTrait`.

---

## 4. Create the TranslationType

`src/Form/Type/{ModelName}TranslationType.php`:

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\FormBuilderInterface;

final class {ModelName}TranslationType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // add translatable fields
        ;
    }

    public function getBlockPrefix(): string
    {
        return '{plugin_alias}_{model_snake}_translation';
    }
}
```

---

## 5. Update the main FormType

The `{ModelName}Type` already exists. Replace the translatable fields with a `ResourceTranslationsType`:

```php
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;

$builder
    // keep non-translatable fields
    ->add('translations', ResourceTranslationsType::class, [
        'entry_type' => {ModelName}TranslationType::class,
        'label' => 'sylius.ui.translations',
    ])
;
```

---

## 6. Register services in `config/services.xml`

The main form service already exists. Add the translation form type and the `ResourceFormComponent` (required for Sylius 2.x admin forms):

```xml
<service id="{plugin_alias}.form.type.{model_snake}_translation"
         class="{Namespace}\Form\Type\{ModelName}TranslationType">
    <argument>%{plugin_alias}.model.{model_snake}_translation.class%</argument>
    <argument type="collection"><argument>sylius</argument></argument>
    <tag name="form.type" />
</service>

<service id="{plugin_alias}.twig.component.{model_snake}.form"
         class="Sylius\Bundle\UiBundle\Twig\Component\ResourceFormComponent">
    <argument type="service" id="{plugin_alias}.repository.{model_snake}" />
    <argument type="service" id="form.factory" />
    <argument>%{plugin_alias}.model.{model_snake}.class%</argument>
    <argument>{Namespace}\Form\Type\{ModelName}Type</argument>
    <tag name="sylius.live_component.admin" key="{plugin_alias}:{model_snake}:form" />
</service>
```

---

## 7. Update `config/resources.yaml`

The entry already exists. Add `translation:` and update `form:`:

```yaml
sylius_resource:
    resources:
        {plugin_alias}.{model_snake}:
            driver: doctrine/orm
            classes:
                model: {Namespace}\Entity\{ModelName}
                form: {Namespace}\Form\Type\{ModelName}Type
            translation:
                classes:
                    model: {Namespace}\Entity\{ModelName}Translation
                    form: {Namespace}\Form\Type\{ModelName}TranslationType
```

---

## 8. Add Twig templates and hooks

Sylius renders translation forms with accordion-style locale tabs via the `translations.with_hook` macro. Without this, fields are rendered without locale tabs and potentially twice.

### Templates

`templates/admin/{model_snake}/form/sections/general.html.twig`:

```twig
<div class="card mb-3">
    <div class="card-header">
        <div class="card-title">{{ 'sylius.ui.general'|trans }}</div>
    </div>
    <div class="card-body">
        <div class="row">
            {% hook 'general' %}
        </div>
    </div>
</div>
```

`templates/admin/{model_snake}/form/sections/translations.html.twig`:

```twig
{% import '@SyliusAdmin/shared/helper/translations.html.twig' as translations %}
{% set form = hookable_metadata.context.form %}
{% set prefixes = hookable_metadata.prefixes %}

<div class="card mb-3">
    <div class="card-header">
        <div class="card-title">{{ 'sylius.ui.translations'|trans }}</div>
    </div>
    <div class="card-body">
        {{ translations.with_hook(form.translations, prefixes, null, { accordion_flush: true }) }}
    </div>
</div>
```

For each non-translatable field, create `templates/admin/{model_snake}/form/sections/general/{field_name}.html.twig`:

```twig
{{ form_row(hookable_metadata.context.form.{field_name}) }}
```

For each translatable field, create `templates/admin/{model_snake}/form/sections/translations/{field_name}.html.twig`:

```twig
{% set form = hookable_metadata.context.form %}
<div class="col-12">
    {{ form_row(form.{field_name}) }}
</div>
```

> All templates live in the plugin's `templates/` directory and are referenced with the `@{BundleName}/` prefix in hooks.

### Twig hooks

Create or update `config/twig_hooks/admin/{model_snake}.yaml`.

> **Important:** `create.content` may already exist — overwrite it as-is. `content.form.sections` may already exist too — add `general:` and `translations:` inside the existing block, do NOT create a duplicate key.

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.{model_snake}.create.content':
            form:
                component: '{plugin_alias}:{model_snake}:form'
                props:
                    resource: '@=_context.resource'
                    form: '@=_context.form'
                    template: '@SyliusAdmin/shared/crud/common/content/form.html.twig'
                priority: 0

        'sylius_admin.{model_snake}.create.content.form.sections':
            general:
                template: '@{BundleName}/admin/{model_snake}/form/sections/general.html.twig'
                priority: 100
            translations:
                template: '@{BundleName}/admin/{model_snake}/form/sections/translations.html.twig'
                priority: 0

        'sylius_admin.{model_snake}.create.content.form.sections.general':
            default:
                enabled: false
            # one entry per non-translatable field:
            # {field_name}:
            #     template: '@{BundleName}/admin/{model_snake}/form/sections/general/{field_name}.html.twig'
            #     priority: 0

        'sylius_admin.{model_snake}.create.content.form.sections.translations':
            # one entry per translatable field:
            # {field_name}:
            #     template: '@{BundleName}/admin/{model_snake}/form/sections/translations/{field_name}.html.twig'
            #     priority: 0

        'sylius_admin.{model_snake}.update.content':
            form:
                component: '{plugin_alias}:{model_snake}:form'
                props:
                    resource: '@=_context.resource'
                    form: '@=_context.form'
                    template: '@SyliusAdmin/shared/crud/common/content/form.html.twig'
                priority: 0

        'sylius_admin.{model_snake}.update.content.form.sections':
            general:
                template: '@{BundleName}/admin/{model_snake}/form/sections/general.html.twig'
                priority: 100
            translations:
                template: '@{BundleName}/admin/{model_snake}/form/sections/translations.html.twig'
                priority: 0

        'sylius_admin.{model_snake}.update.content.form.sections.general':
            default:
                enabled: false
            # one entry per non-translatable field

        'sylius_admin.{model_snake}.update.content.form.sections.translations':
            # one entry per translatable field
```

---

## 9. Generate the migration

```bash
docker compose exec php vendor/bin/console doctrine:migrations:diff --namespace=DoctrineMigrations
```

The migration should contain:
- `ALTER TABLE {db_prefix}_{model_snake}` to drop the translatable columns
- `CREATE TABLE {db_prefix}_{model_snake}_translation` with `translatable_id` FK, `locale` column, and a unique constraint on `(translatable_id, locale)`

Apply:

```bash
docker compose exec php vendor/bin/console doctrine:migrations:migrate --no-interaction
```

---

## 10. Verify

```bash
docker compose exec php vendor/bin/console cache:clear
docker compose exec php vendor/bin/console sylius:debug:resource '{Namespace}\Entity\{ModelName}'
docker compose exec php vendor/bin/console sylius:debug:resource '{Namespace}\Entity\{ModelName}Translation'
```

Both commands should print the resource metadata. The main resource should reference the translation in its `translation.classes.model` row.

---

## Next steps

- Add admin grid → run `/sylius-plugin:add-grid`
- Add admin routes → run `/sylius-plugin:add-routes`
- Add admin menu → run `/sylius-plugin:add-menu`
