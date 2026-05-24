---
name: add-model
description: Add a Doctrine entity as a Sylius Resource in a Sylius application
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

namespace App\Entity\{ModelName};

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

namespace App\Entity\{ModelName};

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'app_{model_snake}')]
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

For relations to another resource in your app, always use the target's **interface** as `targetEntity` — Sylius's `ResolveTargetEntityListener` resolves it at runtime from the `interface:` key in the resource config. In forms, `EntityType` does not go through this listener — pass the concrete class or use `/sylius-app:add-autocomplete`.

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

To relate to a Sylius core entity (e.g. attach data to `Product`), use its interface (`Sylius\Component\Core\Model\ProductInterface`) as `targetEntity`. The interface resolves at runtime to whichever class is configured — yours if you customized it via `/sylius-app:extends-model`, otherwise the Sylius default.

## 3. Register as Sylius Resource

Append to `config/packages/sylius_resource.yaml` under `sylius_resource.resources` (Sylius-Standard ships this file with a `#app.book:` commented hint):

```yaml
sylius_resource:
    resources:
        app.{model_snake}:
            driver: doctrine/orm
            classes:
                model: App\Entity\{ModelName}\{ModelName}
                interface: App\Entity\{ModelName}\{ModelName}Interface
```

## 4. Generate and apply the migration

```bash
bin/console doctrine:migrations:diff
```

**Always review the generated migration before applying.** `doctrine:migrations:diff` captures **every** difference between mapping and DB — including pre-existing schema drift unrelated to your model (e.g. `ALTER TABLE messenger_messages ...` from a Sylius update never migrated locally). The migration should only contain `CREATE TABLE app_{model_snake}` + its indexes/FKs.

If unrelated SQL is present, investigate the drift before merging. Either generate a separate baseline migration to absorb it, or trim the diff and document why.

Then apply:

```bash
bin/console doctrine:migrations:migrate --no-interaction
```

## 5. Clear cache

```bash
bin/console cache:clear
```

## 6. Verify

- [ ] `bin/console sylius:debug:resource 'App\Entity\{ModelName}\{ModelName}'` prints the resource metadata (alias `app.{model_snake}`, model + interface classes)
- [ ] `bin/console doctrine:query:sql "DESCRIBE app_{model_snake}"` lists the expected columns (`id`, plus user fields)

---

## Next steps

1. `/sylius-app:add-form` to add an admin form
2. `/sylius-app:add-translatable-model` if the model needs translations
3. `/sylius-app:add-grid`, then `/sylius-app:add-routes`, then `/sylius-app:add-menu`