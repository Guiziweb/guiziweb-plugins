---
name: add-model
description: Add a Doctrine entity as a Sylius Resource
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Model

Ask the user for the ModelName if not provided, the list of fields with their types, and any relations to other resources.

## Where to place the files

Group by domain like Sylius-Standard (`src/Entity/Product/`, `src/Entity/Customer/`):

- Name starts with an existing resource (`{Parent}Image`, `{Parent}Translation`, …) → `src/Entity/{Parent}/`
- Otherwise → own folder `src/Entity/{ModelName}/`

## 1. Create the interface

`src/Entity/{ModelName}/{ModelName}Interface.php`:

```php
<?php

declare(strict_types=1);

namespace $SYLIUS_NAMESPACE\Entity\{ModelName};

use Sylius\Resource\Model\ResourceInterface;

interface {ModelName}Interface extends ResourceInterface
{
    public function getId(): ?int;
    // declare the getter (and setter if mutable) for every field and relation added in step 2
}
```

## 2. Create the entity

`src/Entity/{ModelName}/{ModelName}.php`:

```php
<?php

declare(strict_types=1);

namespace $SYLIUS_NAMESPACE\Entity\{ModelName};

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '${SYLIUS_PREFIX}_{model_snake}')]
class {ModelName} implements {ModelName}Interface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    // add your fields here
}
```

For fields that require validation, add Symfony constraints directly on the property:

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\NotBlank]
#[Assert\Length(max: 255)]
#[ORM\Column(length: 255)]
private ?string $title = null;
```

For relations to another resource in the same project, always use the target's **interface** as `targetEntity` — Sylius's `ResolveTargetEntityListener` resolves it at runtime from the `interface:` key in the resource config. In forms, `EntityType` does not go through this listener — pass the concrete class or use `/sylius:add-autocomplete`.

**ManyToOne** — `{ModelName}` belongs to one `{RelatedModel}`:

```php
#[ORM\ManyToOne(targetEntity: {RelatedModel}Interface::class)]
private ?{RelatedModel}Interface ${related_model} = null;
```

**OneToMany** (inverse side) — `{ModelName}` has many `{RelatedModel}`. Initialize the collection in the constructor. Requires the owning side (`{RelatedModel}` entity) to declare a matching `ManyToOne` field named `{model_snake}`:

```php
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\OneToMany(mappedBy: '{model_snake}', targetEntity: {RelatedModel}Interface::class)]
private Collection ${related_model_plural};

public function __construct()
{
    $this->{related_model_plural} = new ArrayCollection();
}
```

Add the canonical Sylius adder/remover pair so `LiveCollectionType` and other form types sync the inverse side correctly:

```php
public function add{RelatedModel}({RelatedModel}Interface ${related_model}): void
{
    if (!$this->{related_model_plural}->contains(${related_model})) {
        $this->{related_model_plural}->add(${related_model});
        ${related_model}->set{ModelName}($this);
    }
}

public function remove{RelatedModel}({RelatedModel}Interface ${related_model}): void
{
    if ($this->{related_model_plural}->removeElement(${related_model})) {
        ${related_model}->set{ModelName}(null);
    }
}
```

Same adder/remover pattern for ManyToMany — omit the inverse sync call (no owning-side field to update on the other entity).

**ManyToMany:**

```php
#[ORM\ManyToMany(targetEntity: {RelatedModel}Interface::class)]
private Collection ${related_model_plural};

public function __construct()
{
    $this->{related_model_plural} = new ArrayCollection();
}
```

To reach a Sylius core entity from a plugin (e.g. attach plugin data to `Product`), do not add a relation on the plugin entity — use `/sylius:extends-model` + a plugin-provided trait instead (the FK lives on the extended core entity).

## 3. Register as Sylius Resource

Append to the project's `sylius_resource.yaml` under `sylius_resource.resources`:
- **App context**: `config/packages/sylius_resource.yaml` (shipped by Sylius-Standard with a `#app.book:` commented hint)
- **Plugin context**: `config/packages/sylius_resource.yaml` at the **plugin root** (create if missing). The `PluginSkeleton` test-app loads only `tests/TestApplication/config/config.yaml` (via `SYLIUS_TEST_APP_CONFIGS_TO_IMPORT`), which itself imports `@{PluginBundle}/config/config.yaml` — so the plugin's own config has to pull in the packages dir. Add once to the plugin's `config/config.yaml`: `- { resource: "packages/*.yaml" }`.

```yaml
sylius_resource:
    resources:
        ${SYLIUS_PREFIX}.{model_snake}:
            driver: doctrine/orm
            classes:
                model: $SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}
                interface: $SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}Interface
```

## 4. Generate and apply the migration

**If `$SYLIUS_CONTEXT = plugin`** — the plugin's DI extension declares the `DoctrineMigrations` namespace via `PrependDoctrineMigrationsTrait`, so generate into it:

```bash
$SYLIUS_CONSOLE doctrine:migrations:diff --namespace=DoctrineMigrations
```

**If `$SYLIUS_CONTEXT = app`** — use the app's default migrations namespace:

```bash
$SYLIUS_CONSOLE doctrine:migrations:diff
```

**Always review the generated migration before applying.** `doctrine:migrations:diff` captures **every** difference between mapping and DB — including pre-existing schema drift unrelated to your model (e.g. `ALTER TABLE messenger_messages ...` from a Sylius update never migrated locally). The migration should only contain `CREATE TABLE ${SYLIUS_PREFIX}_{model_snake}` + its indexes/FKs.

If unrelated SQL is present:
- **Plugin context** — manually trim the migration `up()`/`down()` to keep only your table. The drift belongs to the test app baseline, not your plugin.
- **App context** — investigate the drift before merging. Either generate a separate baseline migration to absorb it, or trim and document why.

Then apply:

```bash
$SYLIUS_CONSOLE doctrine:migrations:migrate --no-interaction
```

## 5. Clear cache

```bash
$SYLIUS_CONSOLE cache:clear
```

## 6. Verify

- [ ] `$SYLIUS_CONSOLE sylius:debug:resource '$SYLIUS_NAMESPACE\Entity\{ModelName}\{ModelName}'` prints the resource metadata (alias `${SYLIUS_PREFIX}.{model_snake}`, model + interface classes)
- [ ] `$SYLIUS_CONSOLE doctrine:query:sql "DESCRIBE ${SYLIUS_PREFIX}_{model_snake}"` lists the expected columns (`id`, plus user fields)

---

## Next steps

1. `/sylius:add-form` to add an admin form
2. `/sylius:add-translatable-model` if the model needs translations
3. `/sylius:add-grid`, then `/sylius:add-routes`, then `/sylius:add-menu`
