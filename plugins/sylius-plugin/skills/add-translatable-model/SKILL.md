---
name: add-translatable-model
description: Add a new translatable Doctrine entity as a Sylius Resource to a plugin
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Translatable Model to a Sylius Plugin

Ask the user for the ModelName if not provided. Read `composer.json` and existing source files to detect the plugin namespace and DB prefix.

**Follow all steps from `add-model` first**, then add the steps below.

---

## How Sylius handles translatable entities

Sylius Resource Bundle automatically injects the `OneToMany`/`ManyToOne` relation between a translatable entity and its translation via `ORMTranslatableListener` at runtime — **you do not need to map these relations manually**. The listener also injects the `locale` field on the translation table.

This only works if both the model and its translation are registered as Sylius Resources (see step 3 below).

---

## 1. Create the translation interface

`src/{Domain}/Entity/{ModelName}TranslationInterface.php`

```php
<?php

declare(strict_types=1);

namespace Acme\SyliusExamplePlugin\{Domain}\Entity;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TranslationInterface;

interface {ModelName}TranslationInterface extends ResourceInterface, TranslationInterface
{
    // translatable fields
    public function getName(): ?string;
    public function setName(?string $name): static;
}
```

## 2. Create the translation entity

`src/{Domain}/Entity/{ModelName}Translation.php`

```php
<?php

declare(strict_types=1);

namespace Acme\SyliusExamplePlugin\{Domain}\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\AbstractTranslation;

#[ORM\Entity]
#[ORM\Table(name: '{db_prefix}_{model_snake}_translation')]
class {ModelName}Translation extends AbstractTranslation implements {ModelName}TranslationInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
```

**Do NOT declare `$translatable`, `$locale`, or the relation to the parent entity** — Sylius injects these automatically via `ORMTranslatableListener`.

## 3. Update the main entity

Use `TranslatableTrait` and delegate translatable fields to `$this->getTranslation()`:

```php
<?php

declare(strict_types=1);

namespace Acme\SyliusExamplePlugin\{Domain}\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\TranslatableTrait;

#[ORM\Entity]
#[ORM\Table(name: '{db_prefix}_{model_snake}')]
class {ModelName} implements {ModelName}Interface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;
        getTranslation as private doGetTranslation;
    }

    public function __construct()
    {
        $this->initializeTranslationsCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->getTranslation()->getName();
    }

    public function getTranslation(?string $locale = null): {ModelName}TranslationInterface
    {
        /** @var {ModelName}TranslationInterface $translation */
        $translation = $this->doGetTranslation($locale);

        return $translation;
    }

    protected function createTranslation(): {ModelName}Translation
    {
        return new {ModelName}Translation();
    }
}
```

**Do NOT redeclare `$translations`** — it is already defined in `TranslatableTrait`. Redeclaring it will cause a PHP fatal error.

## 4. Register both model and translation as Sylius Resources

In `config/resources.yaml`:

```yaml
sylius_resource:
    resources:
        {plugin_alias}.{model_snake}:
            driver: doctrine/orm
            classes:
                model: Acme\SyliusExamplePlugin\{Domain}\Entity\{ModelName}
            translation:
                classes:
                    model: Acme\SyliusExamplePlugin\{Domain}\Entity\{ModelName}Translation
```

The `translation:` key is what triggers `ORMTranslatableListener` to inject the relation mapping automatically.

## 5. Verify

```bash
docker compose exec php vendor/bin/console sylius:debug:resource | grep {model_snake}
```

Expected output:
```
  {plugin_alias}.{model_snake}
  {plugin_alias}.{model_snake}_translation
```

```bash
docker compose exec php vendor/bin/console doctrine:mapping:info | grep {ModelName}
```

Expected output:
```
 [OK]   Acme\SyliusExamplePlugin\{Domain}\Entity\{ModelName}
 [OK]   Acme\SyliusExamplePlugin\{Domain}\Entity\{ModelName}Translation
```

## 6. Generate the migration

Follow the same steps as in `add-model`. The generated migration should contain:
- `CREATE TABLE {db_prefix}_{model_snake}`
- `CREATE TABLE {db_prefix}_{model_snake}_translation` with `translatable_id` FK, `locale` column, and a unique constraint on `(translatable_id, locale)`