---
name: add-translatable-model
description: Make an existing Sylius Resource translatable, with TranslationType and Twig hooks
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Make a Sylius Resource translatable

Ask the user for the ModelName if not provided, and the **translatable fields** (the fields that vary per locale — they move from the main entity to the translation entity).

**Prerequisites:** `add-model` and `add-form` must have been run first.

## How Sylius handles translatable entities

Sylius injects the `OneToMany`/`ManyToOne` relation between the entity and its translation via `ORMTranslatableListener` at runtime — **do not map this relation manually**. The listener also injects the `locale` field on the translation table.

## 1. Create the translation interface

`src/Entity/{ModelName}/{ModelName}TranslationInterface.php`:

```php
<?php

declare(strict_types=1);

namespace $SYLIUS_NAMESPACE\Entity\{ModelName};

use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslationInterface;

interface {ModelName}TranslationInterface extends ResourceInterface, TranslationInterface
{
    // declare getters and setters for each translatable field
}
```

## 2. Create the translation entity

`src/Entity/{ModelName}/{ModelName}Translation.php`:

```php
<?php

declare(strict_types=1);

namespace $SYLIUS_NAMESPACE\Entity\{ModelName};

use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Model\AbstractTranslation;

#[ORM\Entity]
#[ORM\Table(name: '${SYLIUS_PREFIX}_{model_snake}_translation')]
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

    // translatable fields: remove ORM\Column from the property, delegate to getTranslation()
    // public function getName(): ?string { return $this->getTranslation()->getName(); }
    // public function setName(?string $name): void { $this->getTranslation()->setName($name); }

    protected function createTranslation(): TranslationInterface
    {
        return new {ModelName}Translation();
    }
}
```

**Do NOT redeclare `$translations`** — already defined in `TranslatableTrait`. If the entity has a constructor already (e.g. from relations added by `add-model`), merge the body into the existing constructor rather than duplicating.

## 4. Create the TranslationType

`src/Form/Type/{ModelName}/{ModelName}TranslationType.php`:

```php
<?php

declare(strict_types=1);

namespace $SYLIUS_NAMESPACE\Form\Type\{ModelName};

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\FormBuilderInterface;

final class {ModelName}TranslationType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // add translatable fields with labels
            // ->add('title', TextType::class, ['label' => '${SYLIUS_PREFIX}.form.{model_snake}.title'])
        ;
    }

    public function getBlockPrefix(): string
    {
        return '${SYLIUS_PREFIX}_{model_snake}_translation';
    }
}
```

Labels for translation fields share the same `${SYLIUS_PREFIX}.form.{model_snake}.*` namespace as the main FormType — the resource is the same, the field lives there regardless of whether it's translatable.

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

## 6. Register services in `config/services.yaml`

Add the translation form type:

```yaml
services:
    ${SYLIUS_PREFIX}.form.type.{model_snake}_translation:
        class: $SYLIUS_NAMESPACE\Form\Type\{ModelName}\{ModelName}TranslationType
        arguments:
            - '%${SYLIUS_PREFIX}.model.{model_snake}_translation.class%'
            - ['sylius']
        tags:
            - { name: form.type }
```

The `ResourceFormComponent` provides the live-component wiring consumed by the `create.content` hook below. Register it only if it doesn't already exist:

```bash
$SYLIUS_CONSOLE debug:container --tag=sylius.live_component.admin | grep '${SYLIUS_PREFIX}:{model_snake}:form'
```

If the command returns nothing, append to `config/services.yaml`:

```yaml
services:
    ${SYLIUS_PREFIX}.twig.component.{model_snake}.form:
        class: Sylius\Bundle\UiBundle\Twig\Component\ResourceFormComponent
        autoconfigure: false    # Sylius-Standard's `_defaults: autoconfigure: true` makes Symfony UX try to auto-name this component before Sylius's compiler pass tags it
        arguments:
            - '@${SYLIUS_PREFIX}.repository.{model_snake}'
            - '@form.factory'
            - '%${SYLIUS_PREFIX}.model.{model_snake}.class%'
            - $SYLIUS_NAMESPACE\Form\Type\{ModelName}\{ModelName}Type
        tags:
            - { name: sylius.live_component.admin, key: '${SYLIUS_PREFIX}:{model_snake}:form' }
```

## 7. Update the resource config

Edit `config/packages/sylius_resource.yaml`. Add the `translation:` block to the existing `${SYLIUS_PREFIX}.{model_snake}` entry, and add `form:` under `classes:` if not already there:

```yaml
sylius_resource:
    resources:
        ${SYLIUS_PREFIX}.{model_snake}:
            driver: doctrine/orm
            classes:
                model: $SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}
                interface: $SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}Interface
                form: $SYLIUS_NAMESPACE\Form\Type\{ModelName}\{ModelName}Type
            translation:
                classes:
                    model: $SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}Translation
                    interface: $SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}TranslationInterface
                    form: $SYLIUS_NAMESPACE\Form\Type\{ModelName}\{ModelName}TranslationType
```

## 8. Templates

Sylius renders translation forms with accordion-style locale tabs via the `translations.with_hook` macro. Without the hookable templates below, fields are rendered without locale tabs and potentially twice.

All templates live under `templates/admin/{model_snake}/` at the project root.

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

One template per **non-translatable** field at `templates/admin/{model_snake}/form/sections/general/{field_name}.html.twig`:

```twig
{{ form_row(hookable_metadata.context.form.{field_name}) }}
```

One template per **translatable** field at `templates/admin/{model_snake}/form/sections/translations/{field_name}.html.twig`:

```twig
{% set form = hookable_metadata.context.form %}
<div class="col-12">
    {{ form_row(form.{field_name}) }}
</div>
```

## 9. Twig hooks

Hook files are split by action (`create.yaml`, `update.yaml`), one directory per resource — matching Sylius core convention.

Unified path: `config/packages/twig_hooks/{model_snake}/create.yaml` and `update.yaml`.

First-time setup: add (or extend) an `imports:` block at the top of `config/packages/_sylius.yaml` so the split files are loaded:

```yaml
imports:
    - { resource: "twig_hooks/**/*.yaml" }
```

Plugin context: same in `tests/TestApplication/config/packages/_sylius.yaml` (create if missing).

### create.yaml

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.{model_snake}.create.content':
            form:
                component: '${SYLIUS_PREFIX}:{model_snake}:form'
                props:
                    resource: '@=_context.resource'
                    form: '@=_context.form'
                    template: '@SyliusAdmin/shared/crud/common/content/form.html.twig'
                priority: 0

        'sylius_admin.{model_snake}.create.content.form.sections':
            general:
                template: '${SYLIUS_TEMPLATE_NS}admin/{model_snake}/form/sections/general.html.twig'
                priority: 100
            translations:
                template: '${SYLIUS_TEMPLATE_NS}admin/{model_snake}/form/sections/translations.html.twig'
                priority: 0

        'sylius_admin.{model_snake}.create.content.form.sections.general':
            default:
                enabled: false
            # one entry per non-translatable field:
            # {field_name}:
            #     template: '${SYLIUS_TEMPLATE_NS}admin/{model_snake}/form/sections/general/{field_name}.html.twig'
            #     priority: 0

        'sylius_admin.{model_snake}.create.content.form.sections.translations':
            # one entry per translatable field:
            # {field_name}:
            #     template: '${SYLIUS_TEMPLATE_NS}admin/{model_snake}/form/sections/translations/{field_name}.html.twig'
            #     priority: 0
```

### update.yaml

Same structure as `create.yaml` — replace every occurrence of `.create.` with `.update.`.

## 10. Generate and apply the migration

```bash
$SYLIUS_CONSOLE doctrine:migrations:diff
```

Plugin-only: append `--namespace=DoctrineMigrations` if the diff does not land in the plugin's migrations directory (the plugin's DI extension declares this namespace via `PrependDoctrineMigrationsTrait`).

**Always review the generated migration before applying.** `doctrine:migrations:diff` captures every difference between mapping and DB — including pre-existing schema drift unrelated to your translatable model. The migration should contain only:
- `CREATE TABLE ${SYLIUS_PREFIX}_{model_snake}_translation` with `translatable_id` FK, `locale` column, and a unique constraint on `(translatable_id, locale)`.
- `ALTER TABLE ${SYLIUS_PREFIX}_{model_snake}` dropping the former translatable columns (no-op if the main table was never migrated with them).

If unrelated SQL is present (e.g. on Sylius core tables), trim the migration — drift belongs to the project baseline, not your skill output.

Apply:

```bash
$SYLIUS_CONSOLE doctrine:migrations:migrate --no-interaction
```

## 11. Translation keys

Get the project's default locale:

```bash
$SYLIUS_CONSOLE debug:container --parameter=kernel.default_locale
```

Add the field labels under `${SYLIUS_PREFIX}.form.{model_snake}.*` in `translations/messages.{locale}.yaml`. The namespace is shared between non-translatable (main form) and translatable (translation form) fields — one entry per field regardless of which form it lives in:

```yaml
${SYLIUS_PREFIX}:
    form:
        {model_snake}:
            name: Name              # non-translatable, lives on the main form
            enabled: Enabled
            title: Title            # translatable, lives on the translation form
            description: Description
```

## 12. Clear cache

```bash
$SYLIUS_CONSOLE cache:clear
```

## 13. Verify

- [ ] `$SYLIUS_CONSOLE sylius:debug:resource '$SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}'` prints the main resource and references the translation class in its `translation.classes.model` row
- [ ] `$SYLIUS_CONSOLE sylius:debug:resource '$SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}Translation'` prints the translation resource
- [ ] `$SYLIUS_CONSOLE doctrine:query:sql "DESCRIBE ${SYLIUS_PREFIX}_{model_snake}_translation"` lists the expected columns (`id`, `translatable_id`, `locale`, plus translatable fields)

## Next steps

- Add admin grid → run `/sylius:add-grid`
- Add admin routes → run `/sylius:add-routes`
- Add admin menu → run `/sylius:add-menu`
