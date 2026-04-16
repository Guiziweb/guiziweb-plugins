---
name: add-resource
description: Add a new Doctrine entity as a Sylius Resource (entity, repository, FormType, migration). Operations are added separately.
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Resource — Sylius Stack

Ask the user for the ModelName if not provided, and the list of fields with their types and which fields should have validation constraints (e.g. `NotBlank`, `Length`, `Email`).

This skill only registers the resource. To expose it in the admin, add operations via `/sylius-stack:add-operation`.

---

## 1. Create the entity

`src/Entity/{ModelName}.php`

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Form\{ModelName}Type;
use App\Repository\{ModelName}Repository;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Metadata\AsResource;
use Sylius\Resource\Model\ResourceInterface;

#[ORM\Entity(repositoryClass: {ModelName}Repository::class)]
#[AsResource(
    section: 'admin',
    routePrefix: '/admin',
    templatesDir: '@SyliusAdminUi/crud',
    formType: {ModelName}Type::class,
)]
class {ModelName} implements ResourceInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // add your fields here

    public function getId(): ?int
    {
        return $this->id;
    }
}
```

Add the fields asked by the user with proper `#[ORM\Column]` attributes and getters/setters.

For fields that require validation, add Symfony constraints directly on the property:

```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\NotBlank]
#[Assert\Length(max: 255)]
#[ORM\Column(length: 255)]
private ?string $title = null;
```

## 2. Create the repository

`src/Repository/{ModelName}Repository.php`

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\{ModelName};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\CreatePaginatorTrait;

class {ModelName}Repository extends ServiceEntityRepository
{
    use CreatePaginatorTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, {ModelName}::class);
    }
}
```

## 3. Generate the form

Run the command directly:

```bash
bin/console make:form {ModelName}Type {ModelName}
```

Read the generated `src/Form/{ModelName}Type.php` and verify the fields match what the user requested.

To add an autocomplete on a field (simple `ChoiceType` or AJAX entity autocomplete), run `/sylius-stack:add-autocomplete` afterwards.

## 4. Generate and apply the migration

Run both commands directly, do not ask the user:

```bash
bin/console doctrine:migrations:diff
```

Read the generated migration file and verify it only contains SQL for the new table.

```bash
bin/console doctrine:migrations:migrate --no-interaction
```

## 5. Verify

Run the command directly:

```bash
bin/console sylius:debug:resource 'App\Entity\{ModelName}'
```

---

## Next steps

- Run `/sylius-stack:add-operation` to add CRUD operations (Create, Update, Delete, BulkDelete, Index, Show)
- Run `/sylius-stack:add-grid` to create an admin grid (required before adding the Index operation)
- Run `/sylius-stack:add-menu` to add the resource to the admin sidebar
