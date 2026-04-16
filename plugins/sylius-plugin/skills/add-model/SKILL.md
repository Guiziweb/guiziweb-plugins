---
name: add-model
description: Add a new Doctrine entity as a Sylius Resource to a plugin
argument-hint: "[ModelName]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Add a Model to a Sylius Plugin

Ask the user for the ModelName if not provided, and the list of fields with their types. Read `composer.json` and existing source files to detect the plugin namespace and DB prefix.

---

## 1. Create the interface

`src/Entity/{ModelName}Interface.php`

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Entity;

use Sylius\Component\Resource\Model\ResourceInterface;

interface {ModelName}Interface extends ResourceInterface
{
    public function getId(): ?int;
    // add your fields here
}
```

## 2. Create the entity

`src/Entity/{ModelName}.php`

```php
<?php

declare(strict_types=1);

namespace {Namespace}\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '{db_prefix}_{model_snake}')]
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

**Column naming:** Doctrine automatically converts camelCase property names to snake_case column names. No need to specify `name:` on `#[ORM\Column]` unless you want a custom name.

## 3. Register as Sylius Resource

Add to `config/resources.yaml` (create if it doesn't exist):

```yaml
sylius_resource:
    resources:
        {plugin_alias}.{model_snake}:
            driver: doctrine/orm
            classes:
                model: {Namespace}\Entity\{ModelName}
```

If this is the first resource, also add the import to `config/config.yaml` so the file is loaded (the skeleton does not import it by default):

```yaml
imports:
    - { resource: "resources.yaml" }
```


## 4. Configure Doctrine mapping in the DI Extension

In `src/DependencyInjection/{PluginName}Extension.php`, add to the `prepend()` method:

```php
$container->prependExtensionConfig('doctrine', [
    'orm' => [
        'entity_managers' => [
            'default' => [
                'mappings' => [
                    '{PluginName}' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => __DIR__ . '/../../src',
                        'prefix' => '{Namespace}',
                    ],
                ],
            ],
        ],
    ],
]);
```

> This only needs to be done once per plugin, not for every model.

## 5. Configure migrations namespace

Create `tests/TestApplication/config/packages/doctrine_migrations.yaml` if it doesn't exist:

```yaml
doctrine_migrations:
    storage:
        table_storage:
            table_name: sylius_migrations
    migrations_paths:
        'DoctrineMigrations': '%kernel.project_dir%/../../../src/Migrations'
```

## 6. Verify the resource is recognized

```bash
docker compose exec php vendor/bin/console sylius:debug:resource | grep {model_snake}
```

Expected output:
```
  {plugin_alias}.{model_snake}
```

Also verify Doctrine sees the entity:

```bash
docker compose exec php vendor/bin/console doctrine:mapping:info | grep {ModelName}
```

Expected output:
```
 [OK]   {Namespace}\Entity\{ModelName}
```

## 7. Generate the migration

Generate:

```bash
docker compose exec php vendor/bin/console doctrine:migrations:diff --namespace=DoctrineMigrations
```

Review the generated file in `src/Migrations/`. It should only contain SQL for your new table. If there is unrelated SQL, delete the migration, investigate why, and regenerate.

Apply the migration:

```bash
docker compose exec php vendor/bin/console doctrine:migrations:migrate --no-interaction
```

---

## Next steps

1. Run `/sylius-plugin:add-form`
2. If the model needs translations → run `/sylius-plugin:add-translatable-model`
3. Run `/sylius-plugin:add-grid`, then `/sylius-plugin:add-routes`, then `/sylius-plugin:add-menu`