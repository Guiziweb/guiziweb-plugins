---
name: add-images
description: Add a multiple images collection (OneToMany) to an existing Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add multiple images to a Sylius Resource

Ask the user for the ModelName if not provided. Read `composer.json` and existing source files to detect the plugin namespace, plugin alias, bundle name, and DB prefix.

**Prerequisites:** `add-model` must have been run first.

---

## 1. Create the Image entity

`src/Entity/{ModelName}Image.php`:

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Image;

#[ORM\Entity]
#[ORM\Table(name: '{db_prefix}_{model_snake}_image')]
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

---

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

---

## 3. Create the Image FormType

`src/Form/Type/{ModelName}ImageType.php`:

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Form\Type;

use {Namespace}\Entity\{ModelName}Image;
use Sylius\Bundle\CoreBundle\Form\Type\ImageType;

final class {ModelName}ImageType extends ImageType
{
    public function __construct()
    {
        parent::__construct({ModelName}Image::class, ['sylius']);
    }

    public function getBlockPrefix(): string
    {
        return '{plugin_alias}_{model_snake}_image';
    }
}
```

---

## 4. Register services in `config/services.xml`

```xml
<service id="{plugin_alias}.form.type.{model_snake}_image"
         class="{Namespace}\Form\Type\{ModelName}ImageType">
    <tag name="form.type" />
</service>

<service id="{plugin_alias}.listener.images_upload.{model_snake}"
         class="Sylius\Bundle\CoreBundle\EventListener\ImagesUploadListener"
         parent="sylius.listener.images_upload"
         autowire="true"
         public="false">
    <tag name="kernel.event_listener" event="{plugin_alias}.{model_snake}.pre_create" method="uploadImages" />
    <tag name="kernel.event_listener" event="{plugin_alias}.{model_snake}.pre_update" method="uploadImages" />
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

> **Note:** The `ResourceFormComponent` is required for `LiveCollectionType` add/remove buttons to work. It wraps the form in a Live Component context.

---

## 5. Register the image entity as a Sylius Resource

Add to `config/resources.yaml`:

```yaml
sylius_resource:
    resources:
        {plugin_alias}.{model_snake}_image:
            classes:
                model: {Namespace}\Entity\{ModelName}Image
                form: {Namespace}\Form\Type\{ModelName}ImageType
```

---

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

---

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

Create or update `config/twig_hooks/admin/{model_snake}.yaml`.

> **Important:** `create.content` may already exist — overwrite it as-is. `content.form.sections` may already exist too — add `images:` inside the existing block, do NOT create a duplicate key.

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
            images:
                template: '@{BundleName}/admin/{model_snake}/form/sections/images.html.twig'
                priority: -100

        'sylius_admin.{model_snake}.create.content.form.sections.images':
            content:
                template: '@{BundleName}/admin/{model_snake}/form/sections/images/content.html.twig'
                priority: 100
            add_button:
                template: '@{BundleName}/admin/{model_snake}/form/sections/images/add_button.html.twig'
                priority: 0

        'sylius_admin.{model_snake}.update.content':
            form:
                component: '{plugin_alias}:{model_snake}:form'
                props:
                    resource: '@=_context.resource'
                    form: '@=_context.form'
                    template: '@SyliusAdmin/shared/crud/common/content/form.html.twig'
                priority: 0

        'sylius_admin.{model_snake}.update.content.form.sections':
            images:
                template: '@{BundleName}/admin/{model_snake}/form/sections/images.html.twig'
                priority: -100

        'sylius_admin.{model_snake}.update.content.form.sections.images':
            content:
                template: '@{BundleName}/admin/{model_snake}/form/sections/images/content.html.twig'
                priority: 100
            add_button:
                template: '@{BundleName}/admin/{model_snake}/form/sections/images/add_button.html.twig'
                priority: 0
```

---

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

---

## 9. Generate the migration

```bash
docker compose exec php vendor/bin/console doctrine:migrations:diff --namespace=DoctrineMigrations
```

The migration should create `{db_prefix}_{model_snake}_image` with `owner_id` FK.

Apply:

```bash
docker compose exec php vendor/bin/console doctrine:migrations:migrate --no-interaction
```

---

## 10. Verify

```bash
docker compose exec php vendor/bin/console cache:clear
docker compose exec php vendor/bin/console sylius:debug:resource | grep {model_snake}_image
```
