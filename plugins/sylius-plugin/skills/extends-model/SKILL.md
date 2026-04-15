---
name: extends-model
description: Extend an existing Sylius entity (Product, Taxon, Channel, etc.) to add custom fields or implement a plugin interface
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Override a Sylius Model

Ask the user for the ModelName if not provided (e.g. `Product`, `Taxon`, `Channel`).

---

## Key concept: Sylius entities are MappedSuperclass

Sylius base models like `Sylius\Component\Core\Model\Product` are **`MappedSuperclass`** in Doctrine — not `#[ORM\Entity]`. This means:

- You **must** add `#[ORM\Entity]` and `#[ORM\Table]` on your override class
- You are the one who makes it a real Doctrine entity
- Your override inherits all columns from the parent and adds yours

---

## 1. Create the override entity

`tests/TestApplication/src/Entity/{ModelName}.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Acme\SyliusExamplePlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Product as BaseProduct;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_product')]
class Product extends BaseProduct
{
    // add your fields here
}
```

**Important:** The namespace must be `Tests\...\Entity` to match:
- PSR-4 autoload-dev: `Tests\...\` → `tests/TestApplication/src/`
- Doctrine mapping prefix: `Tests\...\Entity` → `tests/TestApplication/src/Entity/`

---

## 2. Register the override in Sylius resources

`tests/TestApplication/config/packages/sylius_resources.yaml`

```yaml
sylius_product:
    resources:
        product:
            classes:
                model: Tests\Acme\SyliusExamplePlugin\Entity\Product

sylius_taxonomy:
    resources:
        taxon:
            classes:
                model: Tests\Acme\SyliusExamplePlugin\Entity\Taxon

sylius_channel:
    resources:
        channel:
            classes:
                model: Tests\Acme\SyliusExamplePlugin\Entity\Channel
```

Import it in `tests/TestApplication/config/config.yaml`:

```yaml
imports:
    - { resource: "packages/sylius_resources.yaml" }
```

---

## 3. Configure Doctrine mapping and naming strategy

In `tests/TestApplication/config/config.yaml`:

```yaml
doctrine:
    orm:
        entity_managers:
            default:
                naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
                mappings:
                    TestApplication:
                        is_bundle: false
                        type: attribute
                        dir: '%kernel.project_dir%/../../../tests/TestApplication/src/Entity'
                        prefix: Tests\Acme\SyliusExamplePlugin\Entity
```

> `naming_strategy: underscore_number_aware` is required for Doctrine to convert camelCase property names (`$metadataTitle`) to snake_case column names (`metadata_title`) automatically.

---

## 4. Verify

```bash
docker compose exec php vendor/bin/console debug:container --parameter=sylius.model.product.class
```

Expected:
```
sylius.model.product.class   Tests\Acme\SyliusExamplePlugin\Entity\Product
```

```bash
docker compose exec php vendor/bin/console doctrine:mapping:info | grep Tests
```

Expected:
```
[OK]   Tests\Acme\SyliusExamplePlugin\Entity\Product
```

---

## 5. Generate the migration

Follow the same steps as in `add-model`. The generated migration should contain `ALTER TABLE sylius_product ADD ...` for your new columns only.

Remove any `messenger_messages` noise from the migration before applying.

---

## Using with a plugin interface (ReferenceableTrait pattern)

If you want to attach a plugin-provided object to any Sylius entity via a trait:

**Plugin provides:**

```php
// src/SEO/Adapter/ReferenceableInterface.php
interface ReferenceableInterface
{
    public function getReferenceableContent(): ?SEOContentInterface;
    public function setReferenceableContent(?SEOContentInterface $seoContent): static;
}

// src/SEO/Adapter/ReferenceableTrait.php
trait ReferenceableTrait
{
    #[ORM\OneToOne(targetEntity: SEOContent::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'seo_content_id', referencedColumnName: 'id', onDelete: 'SET NULL', nullable: true)]
    protected ?SEOContentInterface $seoContent = null;

    public function getReferenceableContent(): ?SEOContentInterface { ... }
    public function setReferenceableContent(?SEOContentInterface $seoContent): static { ... }
}
```

**Integrator uses:**

```php
#[ORM\Entity]
#[ORM\Table(name: 'sylius_product')]
class Product extends BaseProduct implements ReferenceableInterface
{
    use ReferenceableTrait;
}
```

This is a **unidirectional OneToOne** — the entity holds the FK, the plugin object knows nothing about the entity. This makes the pattern extensible to any future entity without modifying the plugin.

---

## Next steps — exposing the field in the admin form

After the migration, the field exists in the database but is not visible in the admin UI. The next step depends on the field type:

| Field type | Next step |
|------------|-----------|
| Relation to a Sylius Resource (ManyToOne, ManyToMany) | Run `/sylius-plugin:add-autocomplete` — it creates the autocomplete type, the form extension, and the Twig hook |
| Scalar field (string, boolean, integer…) | Manually create a `{TargetModel}TypeExtension` (see pattern below) and a Twig hook |

### Form extension pattern for scalar fields

`src/Form/Extension/{TargetModel}TypeExtension.php`:

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Form\Extension;

use Sylius\Bundle\{TargetBundle}\Form\Type\{TargetModel}Type;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType; // or CheckboxType, IntegerType…
use Symfony\Component\Form\FormBuilderInterface;

final class {TargetModel}TypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('{field_name}', TextType::class, [
            'label' => '{plugin_alias}.form.{target_model_snake}.{field_name}',
            'required' => false,
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [{TargetModel}Type::class];
    }
}
```

Register in `config/services.xml`:

```xml
<service id="{plugin_alias}.form.extension.{target_model_snake}_type"
         class="{Namespace}\Form\Extension\{TargetModel}TypeExtension">
    <tag name="form.type_extension" />
</service>
```

Then add a Twig template and hook — identify the correct hook name first:

```bash
docker compose exec php vendor/bin/console debug:config sylius_twig_hooks 2>&1 | grep "{target_model_snake}.*create\|{target_model_snake}.*update" | head -10
```

`templates/admin/{target_model_snake}/form/sections/general/{field_name}.html.twig`:

```twig
{{ form_row(hookable_metadata.context.form.{field_name}) }}
```

`config/twig_hooks/admin/{target_model_snake}.yaml`:

```yaml
sylius_twig_hooks:
    hooks:
        'sylius_admin.{target_model_snake}.create.content.form.sections.general':
            {field_name}:
                template: '@{BundleName}/admin/{target_model_snake}/form/sections/general/{field_name}.html.twig'
                priority: -100

        'sylius_admin.{target_model_snake}.update.content.form.sections.general':
            {field_name}:
                template: '@{BundleName}/admin/{target_model_snake}/form/sections/general/{field_name}.html.twig'
                priority: -100
```