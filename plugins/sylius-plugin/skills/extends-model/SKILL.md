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

namespace Tests\{Namespace}\Entity;

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
                model: Tests\{Namespace}\Entity\Product

sylius_taxonomy:
    resources:
        taxon:
            classes:
                model: Tests\{Namespace}\Entity\Taxon

sylius_channel:
    resources:
        channel:
            classes:
                model: Tests\{Namespace}\Entity\Channel
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
                        prefix: Tests\{Namespace}\Entity
```

> `naming_strategy: underscore_number_aware` is required for Doctrine to convert camelCase property names (`$metadataTitle`) to snake_case column names (`metadata_title`) automatically.

---

## 4. Verify

```bash
docker compose exec php vendor/bin/console debug:container --parameter=sylius.model.product.class
```

Expected:
```
sylius.model.product.class   Tests\{Namespace}\Entity\Product
```

```bash
docker compose exec php vendor/bin/console doctrine:mapping:describe 'Tests\{Namespace}\Entity\{ModelName}'
```

The command should output the mapped columns (inherited parent ones + your custom fields). If it errors with "not a mapped entity", the Doctrine mapping in step 3 is not taking effect.

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
| Relation to a Sylius Resource (ManyToOne, ManyToMany) | `/sylius-plugin:add-autocomplete` — creates the autocomplete type, then use `/sylius-plugin:add-form-extension` with that type |
| Scalar field (string, boolean, integer…) | `/sylius-plugin:add-form-extension` — wires a `{TargetModel}TypeExtension`, Twig hook and translation |