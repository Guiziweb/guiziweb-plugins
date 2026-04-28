---
name: plugin-init
description: Create a new Sylius plugin from the official PluginSkeleton with Docker environment
argument-hint: "[Company] [PluginName] [description]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Sylius Plugin Development

## Creating a new plugin

Arguments are appended at the end as `ARGUMENTS: Company PluginName "description"`. Parse them to get Company (PascalCase), PluginName (PascalCase), and Description.

If no arguments are provided, use AskUserQuestion to collect Company, PluginName, and Description.

1. **Derive the directory name** from Company and PluginName: `{Company}{Name}Plugin` (PascalCase concatenation, e.g. `Guiziweb` + `Blog` → `GuiziwebBlogPlugin`). The package name, namespace, and bundle class are set by `rename-plugin.php` in step 5.

2. **Clone the skeleton:**
   ```bash
   git clone https://github.com/Sylius/PluginSkeleton.git {Company}{Name}Plugin
   cd {Company}{Name}Plugin
   rm -rf .git
   cp compose.override.dist.yml compose.override.yml
   ```

3. **Check ports**: read `compose.override.yml` to identify exposed ports, then run `lsof` checks in parallel. If a port is in use, find a free port and update `compose.override.yml`.

4. **Initialize Docker** (this takes several minutes — run in background using `run_in_background: true`):
   ```bash
   ENV=dev make init
   ```
   If error, run the same command again. Wait for completion before proceeding.

5. **Rename the plugin** (via Docker, after `make init` finishes):
   ```bash
   docker compose exec php php bin/rename-plugin.php --company={Company} --plugin-name={Name} --description="{description}" --skip-interaction
   ```

6. **Initialize the database**:
   ```bash
   make database-init && make load-fixtures
   ```

7. **Display setup summary** to the user:

    | Service | URL |
    |---------|-----|
    | Frontend | http://localhost:{http_port} |
    | Admin | http://localhost:{http_port}/admin |
    | MySQL | localhost:{mysql_port} |
    | Mailhog | http://localhost:{mailhog_port} |

    Admin credentials: `sylius@example.com` / `sylius`

## Next steps

- Start adding resources to the plugin → `/sylius:add-model`
- Once the plugin is feature-complete, create a GitHub repo and push
- Publish on [Packagist](https://packagist.org/packages/submit)
- Add a Symfony Flex recipe so integrators auto-install plugin assets
