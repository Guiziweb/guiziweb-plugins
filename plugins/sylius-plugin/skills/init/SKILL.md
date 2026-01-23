---
name: init
description: Create a new Sylius plugin from scratch with Docker environment
allowed-tools: AskUserQuestion, Bash, Read, Edit, TodoWrite
---

# Sylius Plugin Development

## Creating a new plugin

1. **Use AskUserQuestion** to collect:
   - Company name (PascalCase): e.g., `Acme`, `MyCompany`
   - Plugin name (PascalCase): e.g., `ProductReview`
   - Description: e.g., "Product review system"

2. **Show what will be generated** and ask for confirmation:
   - Namespace: `{Company}\{Name}Plugin`
   - Package: `{company-kebab}/{name-kebab}-plugin`
   - DB prefix: `{company_snake}_{name_snake}`
   - Directory: `{Company}{Name}Plugin`

3. **Clone the skeleton:**
   ```bash
   git clone https://github.com/Sylius/PluginSkeleton.git {Company}{Name}Plugin
   cd {Company}{Name}Plugin
   rm -rf .git
   cp compose.override.dist.yml compose.override.yml
   ```

4. **Configure dev environment**: edit `compose.override.yml` to replace `APP_ENV: ${ENV:-prod}` with `APP_ENV: dev`.

5. **Check ports**: read `compose.override.yml` to identify exposed ports, then run `lsof` checks in parallel. If a port is in use, find a free port and update `compose.override.yml`.

6. **Initialize Docker**:
   ```bash
   make init
   ```
   If error, run the same command again.

7. **Rename the plugin** (via Docker):
   ```bash
   COMPANY={Company} PLUGIN_NAME={Name} DESCRIPTION="{description}" SKIP_INTERACTION=1 make rename
   ```

8. **Initialize the database**:
   ```bash
   make database-init && make load-fixtures
   ```

9. **Open browser and display summary**:
   ```bash
   open http://localhost:{http_port}
   open http://localhost:{http_port}/admin
   ```
   Then display:
   ```
   Plugin {Company}{Name}Plugin initialized!

   Services:
   - Front: http://localhost:{http_port}
   - Admin: http://localhost:{http_port}/admin
   - MySQL: localhost:{mysql_port}
   - Mailhog: http://localhost:{mailhog_port}

   Admin credentials:
   - Email:    sylius@example.com
   - Password: sylius
   ```


## Commands (after installation)

**IMPORTANT:** These commands are for daily use AFTER installation. Do not use them during setup.

| Action | Command |
|--------|---------|
| Start containers | `make up` |
| Stop containers | `make down` |
| Clear cache | `make cache-clear` |

**Symfony Console:** `docker compose exec php vendor/bin/console <cmd>`


## References

- [PluginSkeleton GitHub](https://github.com/Sylius/PluginSkeleton)
- [Test Application Overview](https://docs.sylius.com/plugins-development-guide/test-application.md)
- [Creating plugins with Test Application](https://docs.sylius.com/plugins-development-guide/test-application/creating-and-testing-plugins-using-test-application.md)
