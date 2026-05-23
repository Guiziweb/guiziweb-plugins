---
name: extends-model
description: Extend an existing Sylius entity (Product, Taxon, Channel, etc.) to add custom fields or implement a plugin interface
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Extend a Sylius entity

Sylius base entities like `Sylius\Component\Core\Model\Product` are declared as `MappedSuperclass`, not `#[ORM\Entity]`. They're not real Doctrine entities — every Sylius project is expected to declare a concrete `#[ORM\Entity]` subclass that inherits the parent columns and adds its own (or applies a plugin-provided trait).

Ask the user for the ModelName if not provided (e.g. `Product`, `Taxon`, `Channel`, `Customer`).

`$SYLIUS_CONTEXT` determines where the override lives:
- `app` — integrator in a Sylius app → override lives in `src/Entity/`
- `plugin` — plugin developer exercising the plugin through its test app → override lives in `tests/TestApplication/src/Entity/`

## 1. Identify the current resource config

```bash
$SYLIUS_CONSOLE sylius:debug:resource sylius.{model_snake}
```

The output's `classes.model` row is the current model FQCN — **that's the class your override must extend**. Run without an argument to list all resource aliases.

Also read the reference template used by Sylius-Standard for the exact file structure to mimic:

```bash
cat vendor/sylius/sylius-standard/src/Entity/{Subdir}/{ModelName}.php
cat vendor/sylius/sylius-standard/config/packages/_sylius.yaml
```

`{Subdir}` is the functional grouping used by Sylius-Standard (`Product`, `Taxonomy`, `Shipping`, `Addressing`, `User`, `Customer`, `Channel`, `Order`, `Payment`, `Promotion`, `Taxation`). `ls vendor/sylius/sylius-standard/src/Entity/` to see them all.

## 2. Create the override entity

**App context** — `src/Entity/{Subdir}/{ModelName}.php`:

```php
<?php

declare(strict_types=1);

namespace $SYLIUS_NAMESPACE\Entity\{Subdir};

use Doctrine\ORM\Mapping as ORM;
use {BaseModelFqcn} as Base{ModelName};  // value from step 1 — typically Sylius\Component\Core\Model\{ModelName}

#[ORM\Entity]
#[ORM\Table(name: 'sylius_{model_snake}')]
class {ModelName} extends Base{ModelName}
{
    // add your fields / implements Xxx + use XxxTrait here
}
```

**Plugin context** — `tests/TestApplication/src/Entity/{Subdir}/{ModelName}.php` with namespace `Tests\$SYLIUS_NAMESPACE\Entity\{Subdir}`. Same body.

Omitting `#[ORM\Entity]` makes Doctrine ignore the class entirely — the override silently doesn't take effect.

## 3. Register the override as the resource model

Unlike our own plugin resources (`add-model`, one file per resource), Sylius core overrides go in a **single monolithic file** — this is the Sylius official convention. All overrides share the same `_sylius.yaml` across bundle keys.

Copy the bundle key + resource key pairing from `vendor/sylius/sylius-standard/config/packages/_sylius.yaml` (read in step 1). Example for Product:

```yaml
# config/packages/_sylius.yaml  (app)
# tests/TestApplication/config/packages/_sylius.yaml  (plugin)
sylius_product:
    resources:
        product:
            classes:
                model: $SYLIUS_NAMESPACE\Entity\{Subdir}\{ModelName}
                # plugin: Tests\$SYLIUS_NAMESPACE\Entity\{Subdir}\{ModelName}
```

Non-obvious pairings to watch for (the bundle key isn't always `sylius_{model_snake}`):
- Taxon → `sylius_taxonomy.resources.taxon`
- ShopUser/AdminUser → `sylius_user.resources.shop_user` / `admin_user`
- Country/Address/Zone → `sylius_addressing.resources.{country|address|zone|zone_member}`

If unsure, `grep -r "{model_snake}:" vendor/sylius/sylius-standard/config/packages/_sylius.yaml` finds the exact placement.

## 4. Configure Doctrine mapping

**App context** — nothing to do. Sylius Standard already maps `$SYLIUS_NAMESPACE\` (`App\`) to `src/`; `#[ORM\Entity]` classes under any subnamespace are picked up automatically.

**Plugin context** — the test app must learn the `Tests\$SYLIUS_NAMESPACE\Entity` namespace. Add to `tests/TestApplication/config/config.yaml`:

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
                        prefix: Tests\$SYLIUS_NAMESPACE\Entity
```

The `prefix` covers all subnamespaces (`Tests\...\Entity\Core`, `Tests\...\Entity\Addressing`, etc.) automatically. `naming_strategy: underscore_number_aware` converts camelCase properties (`$metadataTitle`) to snake_case columns (`metadata_title`).

## 5. Generate and apply the migration

Plugin context — append `--namespace=DoctrineMigrations` so the migration lands in the plugin's declared namespace. App context — no flag needed.

```bash
$SYLIUS_CONSOLE doctrine:migrations:diff        # plugin: add --namespace=DoctrineMigrations
```

**Always review the generated migration before applying.** It should contain only `ALTER TABLE sylius_{model_snake} ADD ...` for the new columns. Unrelated SQL (other Sylius core tables, `messenger_messages`, etc.) means pre-existing schema drift between mapping and DB — investigate or trim before applying.

```bash
$SYLIUS_CONSOLE doctrine:migrations:migrate --no-interaction
```

## 6. Clear cache

```bash
$SYLIUS_CONSOLE cache:clear
```

## 7. Verify

- [ ] `$SYLIUS_CONSOLE debug:container --parameter=sylius.model.{model_snake}.class` outputs the override FQCN — `$SYLIUS_NAMESPACE\Entity\{Subdir}\{ModelName}` (app) or `Tests\$SYLIUS_NAMESPACE\Entity\{Subdir}\{ModelName}` (plugin)
- [ ] `$SYLIUS_CONSOLE doctrine:mapping:describe '{EntityFqcn}'` lists the inherited columns plus the new ones (no `not a mapped entity` error)
- [ ] `$SYLIUS_CONSOLE doctrine:query:sql "DESCRIBE sylius_{model_snake}"` shows the new columns persisted in the DB

---

## Plugin-provided interface + trait pattern

When the plugin ships a trait/interface for integrators to attach plugin data to any Sylius entity:

Plugin side:

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

    public function getReferenceableContent(): ?SEOContentInterface { /* ... */ }
    public function setReferenceableContent(?SEOContentInterface $seoContent): static { /* ... */ }
}
```

Integrator side:

```php
#[ORM\Entity]
#[ORM\Table(name: 'sylius_product')]
class Product extends BaseProduct implements ReferenceableInterface
{
    use ReferenceableTrait;
}
```

Unidirectional OneToOne: the Sylius entity holds the FK, the plugin object knows nothing about the entity. Works for any current or future Sylius entity without plugin modification.

---

## Next steps

After the migration, the column exists in the database but is not visible in the UI. Depending on what was added:

| Change | Next step |
|---|---|
| Scalar field (string, boolean, integer…) | `/sylius:extends-form` — adds a FormTypeExtension + Twig hook + translation |
| Relation to another Sylius Resource (ManyToOne, ManyToMany) | `/sylius:add-autocomplete` first, then `/sylius:extends-form` using that autocomplete type |
| Show the new field in the admin index grid | `/sylius:extends-grid` |
