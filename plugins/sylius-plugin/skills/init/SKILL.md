---
name: init
description: Create a new Sylius plugin from scratch with Docker environment and GitHub repo
argument-hint: "[Company] [PluginName] [description] [GitHubOrg]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Sylius Plugin Development

## Creating a new plugin

Arguments are appended at the end as `ARGUMENTS: Company PluginName "description" GitHubOrg`. Parse them to get Company (PascalCase), PluginName (PascalCase), Description, and GitHubOrg. If GitHubOrg is not provided, default to Company. Visibility = `public`.

If no arguments are provided, use AskUserQuestion to collect Company, PluginName, Description, GitHub account, and visibility.

1. **Derive names** from Company and PluginName:
   - Namespace: `{Company}\Sylius{Name}Plugin`
   - Package: `{company-kebab}/sylius-{name-kebab}-plugin`
   - DB prefix: `{company_snake}_sylius_{name_snake}`
   - Directory: `{Company}{Name}Plugin`
   - GitHub repo: `https://github.com/{GitHubOrg}/{Company}{Name}Plugin`

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

6. **Create GitHub repo and push** (from the plugin directory):
   ```bash
   git init
   git add -A
   git commit -m "Initialize {Company}Sylius{Name}Plugin from skeleton"
   gh repo create {GitHubOrg}/{Company}{Name}Plugin --{visibility} --description="{description}" --source=. --push
   git tag v0.1.0
   git push origin v0.1.0
   ```

7. **Initialize the database**:
   ```bash
   make database-init && make load-fixtures
   ```

8. **Display setup summary** to the user:

    | Service | URL |
    |---------|-----|
    | Frontend | http://localhost:{http_port} |
    | Admin | http://localhost:{http_port}/admin |
    | MySQL | localhost:{mysql_port} |
    | Mailhog | http://localhost:{mailhog_port} |

    Admin credentials: `sylius@example.com` / `sylius`

    Next steps:
    - Publish on [Packagist](https://packagist.org/packages/submit)
    - Add Symfony Flex recipe


## References

- [PluginSkeleton GitHub](https://github.com/Sylius/PluginSkeleton)
- [Test Application Overview](https://docs.sylius.com/plugins-development-guide/test-application.md)
- [Creating plugins with Test Application](https://docs.sylius.com/plugins-development-guide/test-application/creating-and-testing-plugins-using-test-application.md)
