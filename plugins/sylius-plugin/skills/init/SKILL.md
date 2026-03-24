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
   - Store the absolute path: `PLUGIN_DIR="$(pwd)/{Company}{Name}Plugin"`

2. **Create GitHub repo** (empty, with description):
   ```bash
   gh repo create {GitHubOrg}/{Company}{Name}Plugin --{visibility} --description="{description}"
   ```

3. **Clone the skeleton locally** (for Docker setup):
   ```bash
   git clone https://github.com/Sylius/PluginSkeleton.git {Company}{Name}Plugin
   rm -rf {Company}{Name}Plugin/.git
   cp {Company}{Name}Plugin/compose.override.dist.yml {Company}{Name}Plugin/compose.override.yml
   ```

4. **Check ports**: read `{Company}{Name}Plugin/compose.override.yml` to identify exposed ports, then run `lsof` checks in parallel. If a port is in use, find a free port and update the file.

5. **Initialize Docker** (this takes several minutes — run in background using `run_in_background: true`):
   ```bash
   cd "$PLUGIN_DIR" && ENV=dev make init
   ```
   If error, run the same command again. Wait for completion before proceeding.

6. **Push skeleton to trigger bootstrap CI**:
   ```bash
   cd "$PLUGIN_DIR" && git init && git add -A && git commit -m "chore: init"
   cd "$PLUGIN_DIR" && git remote add origin https://github.com/{GitHubOrg}/{Company}{Name}Plugin.git && git push origin main
   ```
   Then wait for the `Bootstrap Plugin` CI workflow to finish (rename + cleanup + templates, committed under the bot). Poll with:
   ```bash
   gh run watch --repo {GitHubOrg}/{Company}{Name}Plugin --exit-status
   ```

7. **Pull the bootstrapped code**:
   ```bash
   cd "$PLUGIN_DIR" && git pull origin main
   ```

8. **Push v0.1.0 tag** to trigger recipe and Packagist CI:
   ```bash
   cd "$PLUGIN_DIR" && git tag v0.1.0 && git push origin v0.1.0
   ```

9. **Initialize the database**:
   ```bash
   cd "$PLUGIN_DIR" && ENV=dev make database-init && ENV=dev make load-fixtures
   ```

10. **Display setup summary** to the user:

    | Service | URL |
    |---------|-----|
    | Frontend | http://localhost:{http_port} |
    | Admin | http://localhost:{http_port}/admin |
    | MySQL | localhost:{mysql_port} |
    | Mailhog | http://localhost:{mailhog_port} |

    Admin credentials: `sylius@example.com` / `sylius`
