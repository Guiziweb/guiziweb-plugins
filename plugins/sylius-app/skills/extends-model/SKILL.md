---
name: extends-model
description: Extend an existing Sylius entity (Product, Taxon, Channel, etc.) in your Sylius application to add custom fields or apply a plugin-provided trait
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Extend a Sylius entity

Sylius base entities like `Sylius\Component\Core\Model\Product` are declared as `MappedSuperclass`, not `#[ORM\Entity]`. They're not real Doctrine entities — every Sylius project is expected to declare a concrete `#[ORM\Entity]` subclass that inherits the parent columns and adds its own (or applies a plugin-provided trait).

Ask the user for the ModelName if not provided (e.g. `Product`, `Taxon`, `Channel`, `Customer`).

The override lives in `src/Entity/`.

## 1. Identify the current resource config

```bash
bin/console sylius:debug:resource sylius.{model_snake}
```

The output's `classes.model` row is the current model FQCN — **that's the class your override must extend**. Run without an argument to list all resource aliases.

Also read the reference template used by Sylius-Standard for the exact file structure to mimic:

```bash
cat vendor/sylius/sylius-standard/src/Entity/{Subdir}/{ModelName}.php
cat vendor/sylius/sylius-standard/config/packages/_sylius.yaml
```

`{Subdir}` is the functional grouping used by Sylius-Standard (`Product`, `Taxonomy`, `Shipping`, `Addressing`, `User`, `Customer`, `Channel`, `Order`, `Payment`, `Promotion`, `Taxation`). `ls vendor/sylius/sylius-standard/src/Entity/` to see them all.

## 2. Create the override entity

`src/Entity/{Subdir}/{ModelName}.php`:

```php
<?php

declare(strict_types=1);

namespace App\Entity\{Subdir};

use Doctrine\ORM\Mapping as ORM;
use {BaseModelFqcn} as Base{ModelName};  // value from step 1 — typically Sylius\Component\Core\Model\{ModelName}

#[ORM\Entity]
#[ORM\Table(name: 'sylius_{model_snake}')]
class {ModelName} extends Base{ModelName}
{
    // add your fields / implements Xxx + use XxxTrait here
}
```

Omitting `#[ORM\Entity]` makes Doctrine ignore the class entirely — the override silently doesn't take effect.

## 3. Register the override as the resource model

Unlike custom resources (`add-model`, one file per resource), Sylius core overrides go in a **single monolithic file** — this is the Sylius official convention. All overrides share the same `_sylius.yaml` across bundle keys.

Copy the bundle key + resource key pairing from `vendor/sylius/sylius-standard/config/packages/_sylius.yaml` (read in step 1). Example for Product:

```yaml
# config/packages/_sylius.yaml
sylius_product:
    resources:
        product:
            classes:
                model: App\Entity\{Subdir}\{ModelName}
```

Non-obvious pairings to watch for (the bundle key isn't always `sylius_{model_snake}`):
- Taxon → `sylius_taxonomy.resources.taxon`
- ShopUser/AdminUser → `sylius_user.resources.shop_user` / `admin_user`
- Country/Address/Zone → `sylius_addressing.resources.{country|address|zone|zone_member}`

If unsure, `grep -r "{model_snake}:" vendor/sylius/sylius-standard/config/packages/_sylius.yaml` finds the exact placement.

## 4. Configure Doctrine mapping

Nothing to do — Sylius Standard already maps `App\` to `src/`; `#[ORM\Entity]` classes under any subnamespace are picked up automatically.

## 5. Generate and apply the migration

```bash
bin/console doctrine:migrations:diff
```

**Always review the generated migration before applying.** It should contain only `ALTER TABLE sylius_{model_snake} ADD ...` for the new columns. Unrelated SQL (other Sylius core tables, `messenger_messages`, etc.) means pre-existing schema drift between mapping and DB — investigate or trim before applying.

```bash
bin/console doctrine:migrations:migrate --no-interaction
```

## 6. Clear cache

```bash
bin/console cache:clear
```

## 7. Verify

- [ ] `bin/console debug:container --parameter=sylius.model.{model_snake}.class` outputs the override FQCN — `App\Entity\{Subdir}\{ModelName}`
- [ ] `bin/console doctrine:mapping:describe 'App\Entity\{Subdir}\{ModelName}'` lists the inherited columns plus the new ones (no `not a mapped entity` error)
- [ ] `bin/console doctrine:query:sql "DESCRIBE sylius_{model_snake}"` shows the new columns persisted in the DB

---

## Next steps

After the migration, the column exists in the database but is not visible in the UI. Depending on what was added:

| Change | Next step |
|---|---|
| Scalar field (string, boolean, integer…) | `/sylius-app:extends-form` — adds a FormTypeExtension + Twig hook + translation |
| Relation to another Sylius Resource (ManyToOne, ManyToMany) | `/sylius-app:add-autocomplete` first, then `/sylius-app:extends-form` using that autocomplete type |
| Show the new field in the admin index grid | `/sylius-app:extends-grid` |