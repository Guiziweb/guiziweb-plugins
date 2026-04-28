---
name: add-images
description: Add a multiple images collection (OneToMany) to an existing Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add multiple images to a Sylius Resource

Ask the user for the ModelName if not provided.

**Prerequisite:** `add-model` must have been run first.

## 1. Create the Image entity

`src/Entity/{ModelName}/{ModelName}Image.php`:

```php
<?php

declare(strict_types=1);

namespace $SYLIUS_NAMESPACE\Entity\{ModelName};

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Image;

#[ORM\Entity]
#[ORM\Table(name: '${SYLIUS_PREFIX}_{model_snake}_image')]
class {ModelName}Image extends Image
{
    #[ORM\ManyToOne(
        targetEntity: {ModelName}::class,
        inversedBy: 'images'
    )]
    #[ORM\JoinColumn(
        name: 'owner_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    protected $owner = null;
}
```

## 2. Update the main entity

Add `ImagesAwareInterface` and the images collection to the existing entity:

```php
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\ImagesAwareInterface;

class {ModelName} implements {ModelName}Interface, ImagesAwareInterface
{
    #[ORM\OneToMany(
        targetEntity: {ModelName}Image::class,
        mappedBy: 'owner',
        orphanRemoval: true,
        cascade: ['persist', 'remove', 'detach']
    )]
    private Collection $images;

    public function __construct()
    {
        // keep existing constructor content
        $this->images = new ArrayCollection();
    }

    public function getImages(): Collection
    {
        return $this->images;
    }

    public function getImagesByType(string $type): Collection
    {
        return $this->images->filter(fn(ImageInterface $image) => $image->getType() === $type);
    }

    public function hasImages(): bool
    {
        return !$this->images->isEmpty();
    }

    public function hasImage(ImageInterface $image): bool
    {
        return $this->images->contains($image);
    }

    public function addImage(ImageInterface $image): void
    {
        if (!$this->hasImage($image)) {
            $image->setOwner($this);
            $this->images->add($image);
        }
    }

    public function removeImage(ImageInterface $image): void
    {
        if ($this->hasImage($image)) {
            $image->setOwner(null);
            $this->images->removeElement($image);
        }
    }
}
```

## 3. Create the Image FormType

`src/Form/Type/{ModelName}/{ModelName}ImageType.php`:

```php
<?php

declare(strict_types=1);

namespace $SYLIUS_NAMESPACE\Form\Type\{ModelName};

use $SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}Image;
use Sylius\Bundle\CoreBundle\Form\Type\ImageType;

final class {ModelName}ImageType extends ImageType
{
    public function __construct()
    {
        parent::__construct({ModelName}Image::class, ['sylius']);
    }

    public function getBlockPrefix(): string
    {
        return '${SYLIUS_PREFIX}_{model_snake}_image';
    }
}
```

## 4. Register services in `config/services.yaml`

```yaml
services:
    ${SYLIUS_PREFIX}.form.type.{model_snake}_image:
        class: $SYLIUS_NAMESPACE\Form\Type\{ModelName}\{ModelName}ImageType
        tags:
            - { name: form.type }

    ${SYLIUS_PREFIX}.listener.images_upload.{model_snake}:
        parent: sylius.listener.images_upload
        autowire: true
        public: false
        tags:
            - { name: kernel.event_listener, event: '${SYLIUS_PREFIX}.{model_snake}.pre_create', method: uploadImages }
            - { name: kernel.event_listener, event: '${SYLIUS_PREFIX}.{model_snake}.pre_update', method: uploadImages }
```

The `ResourceFormComponent` is required for `LiveCollectionType` add/remove buttons to work — it wraps the form in a Live Component context. Register it only if it doesn't already exist:

```bash
$SYLIUS_CONSOLE debug:container --tag=sylius.live_component.admin | grep '${SYLIUS_PREFIX}:{model_snake}:form'
```

If the command returns nothing, append to `config/services.yaml`:

```yaml
services:
    ${SYLIUS_PREFIX}.twig.component.{model_snake}.form:
        class: Sylius\Bundle\UiBundle\Twig\Component\ResourceFormComponent
        arguments:
            - '@${SYLIUS_PREFIX}.repository.{model_snake}'
            - '@form.factory'
            - '%${SYLIUS_PREFIX}.model.{model_snake}.class%'
            - $SYLIUS_NAMESPACE\Form\Type\{ModelName}\{ModelName}Type
        tags:
            - { name: sylius.live_component.admin, key: '${SYLIUS_PREFIX}:{model_snake}:form' }
```

> The event names use `${SYLIUS_PREFIX}` (Sylius 2.x resource events use the application prefix, not `sylius.*`).

## 5. Register the image entity as a Sylius Resource

Append to `sylius_resource.yaml`:
- **App context**: `config/packages/sylius_resource.yaml`
- **Plugin context**: `tests/TestApplication/config/packages/sylius_resource.yaml`

```yaml
sylius_resource:
    resources:
        ${SYLIUS_PREFIX}.{model_snake}_image:
            classes:
                model: $SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}Image
                form: $SYLIUS_NAMESPACE\Form\Type\{ModelName}\{ModelName}ImageType
```

## 6. Add `images` field to the main FormType

In the existing `{ModelName}Type`, add the images collection:

```php
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

$builder->add('images', LiveCollectionType::class, [
    'entry_type' => {ModelName}ImageType::class,
    'allow_add' => true,
    'allow_delete' => true,
    'by_reference' => false,
    'label' => 'sylius.ui.images',
]);
```

## 7. Add Twig templates and hooks

### Templates

`templates/admin/{model_snake}/form/sections/images.html.twig`:

```twig
<div class="card mb-3">
    <div class="card-header">
        <div class="card-title">{{ 'sylius.ui.images'|trans }}</div>
    </div>
    <div class="card-body">
        {% hook 'images' %}
    </div>
</div>
```

`templates/admin/{model_snake}/form/sections/images/content.html.twig`:

```twig
{% set images = hookable_metadata.context.form.images %}

<div class="row">
    {% for image_form in images %}
        <div class="col-12 col-md-6 row mb-4">
            <div class="col-auto">
                <div>
                    {% if image_form.vars.value.path is not null %}
                        <span class="avatar avatar-xl" style="background-image: url('{{ image_form.vars.value.path|imagine_filter('sylius_small') }}')"></span>
                    {% else %}
                        <span class="avatar avatar-xl"></span>
                    {% endif %}
                </div>
                <div class="mt-3 d-flex items-center">
                    {{ form_widget(image_form.vars.button_delete, { label: 'sylius.ui.delete'|trans, attr: { class: 'btn btn-outline-danger w-100' }}) }}
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    {{ form_row(image_form.file) }}
                </div>
            </div>
        </div>
    {% endfor %}
</div>
```

`templates/admin/{model_snake}/form/sections/images/add_button.html.twig`:

```twig
<div class="d-grid gap-2">
    {{ form_widget(hookable_metadata.context.form.images.vars.button_add) }}
</div>
```

### Twig hooks

Hook files are split by action (`create.yaml`, `update.yaml`), one directory per resource — matching Sylius core convention.

Unified path: `config/packages/twig_hooks/{model_snake}/create.yaml` and `update.yaml`.

First-time setup: add (or extend) an `imports:` block at the top of `config/packages/_sylius.yaml` so the split files are loaded:

```yaml
imports:
    - { resource: "twig_hooks/**/*.yaml" }
```

Plugin context: same in `tests/TestApplication/config/packages/_sylius.yaml` (create if missing).

### create.yaml

> If `create.yaml` already exists for this resource (e.g. from `add-translatable-model`), the `form:` block is already set — only add the `images:` entry under `content.form.sections` and the `images.*` sub-hook.

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
            images:
                template: '${SYLIUS_TEMPLATE_NS}admin/{model_snake}/form/sections/images.html.twig'
                priority: -100

        'sylius_admin.{model_snake}.create.content.form.sections.images':
            content:
                template: '${SYLIUS_TEMPLATE_NS}admin/{model_snake}/form/sections/images/content.html.twig'
                priority: 100
            add_button:
                template: '${SYLIUS_TEMPLATE_NS}admin/{model_snake}/form/sections/images/add_button.html.twig'
                priority: 0
```

### update.yaml

Same structure as `create.yaml` — replace every occurrence of `.create.` with `.update.`.

## 8. Add validation constraints

On the image entity:

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Image(
    groups: ['sylius'],
    mimeTypes: ['image/png', 'image/jpeg', 'image/gif'],
    maxSize: '10M'
)]
protected $file;
```

On the main entity:

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Valid]
private Collection $images;
```

## 9. Generate and apply the migration

```bash
$SYLIUS_CONSOLE doctrine:migrations:diff
```

Plugin-only: append `--namespace=DoctrineMigrations` if the diff does not land in the plugin's migrations directory.

**Always review the generated migration before applying.** `doctrine:migrations:diff` captures every difference between mapping and DB — including pre-existing schema drift unrelated to your image collection. The migration should only contain `CREATE TABLE ${SYLIUS_PREFIX}_{model_snake}_image` with its `owner_id` FK + indexes. If unrelated SQL is present (e.g. on `messenger_messages` or other Sylius core tables), trim the migration to keep only the image table — drift belongs to the project baseline, not your skill output.

Apply:

```bash
$SYLIUS_CONSOLE doctrine:migrations:migrate --no-interaction
```

## 10. Clear cache

```bash
$SYLIUS_CONSOLE cache:clear
```

## 11. Verify

- [ ] `$SYLIUS_CONSOLE sylius:debug:resource '$SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}Image'` prints the image resource metadata (alias `${SYLIUS_PREFIX}.{model_snake}_image`, model class, form type)
- [ ] `$SYLIUS_CONSOLE doctrine:query:sql "DESCRIBE ${SYLIUS_PREFIX}_{model_snake}_image"` lists the expected columns (`id`, `owner_id`, `type`, `path`)
- [ ] `$SYLIUS_CONSOLE debug:container --tag=sylius.live_component.admin | grep '${SYLIUS_PREFIX}:{model_snake}:form'` finds the live component key