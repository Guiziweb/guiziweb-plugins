---
name: init
description: Create a new Sylius plugin from scratch with Docker environment and GitHub repo
argument-hint: "[PluginName] [description]"
allowed-tools: AskUserQuestion, Bash, Read, Edit, Write, Glob, Grep
---

# Sylius Plugin Development

## Creating a new plugin

Arguments are appended at the end as `ARGUMENTS: PluginName "description"`. Parse them to get PluginName (PascalCase) and Description.

If no arguments are provided, use AskUserQuestion to collect PluginName and Description.

1. **Trigger the bootstrap workflow** (creates repo + bootstraps skeleton automatically):
   ```bash
   gh workflow run bootstrap.yml --repo Guiziweb/.github \
     --field repo="Guiziweb{Name}Plugin" \
     --field company="Guiziweb" \
     --field name="{Name}" \
     --field description="{description}"
   ```
   Then get the run ID and wait for it to finish:
   ```bash
   sleep 3 && RUN_ID=$(gh run list --repo Guiziweb/.github --workflow=bootstrap.yml --limit=1 --json databaseId --jq '.[0].databaseId')
   gh run watch $RUN_ID --repo Guiziweb/.github --exit-status
   ```

2. **Clone the bootstrapped repo locally**:
   ```bash
   cd ~
   git clone https://github.com/Guiziweb/Guiziweb{Name}Plugin.git
   cp ~/Guiziweb{Name}Plugin/compose.override.dist.yml ~/Guiziweb{Name}Plugin/compose.override.yml
   ```

3. **Check ports**: read `~/Guiziweb{Name}Plugin/compose.override.yml` to identify exposed ports, then run `lsof` checks in parallel. If a port is in use, find a free port and update the file.

4. **Initialize Docker** (this takes several minutes — run in background using `run_in_background: true`):
   ```bash
   cd ~/Guiziweb{Name}Plugin && ENV=dev make init
   ```
   If error, run the same command again. Wait for completion before proceeding.

5. **Initialize the database**:
   ```bash
   cd ~/Guiziweb{Name}Plugin && ENV=dev make database-init && ENV=dev make load-fixtures
   ```

6. **Display setup summary** to the user:

   | Service  | URL                                    |
   |----------|----------------------------------------|
   | Frontend | http://localhost:{http_port}           |
   | Admin    | http://localhost:{http_port}/admin     |
   | MySQL    | localhost:{mysql_port}                 |
   | Mailhog  | http://localhost:{mailhog_port}        |

   Admin credentials: `sylius@example.com` / `sylius`