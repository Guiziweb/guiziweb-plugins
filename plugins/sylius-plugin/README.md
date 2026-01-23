# Sylius Plugin Initializer

Create new Sylius plugins from scratch with Docker environment.

## Features

- Initialize a new Sylius plugin project
- Clones official PluginSkeleton
- Configures Docker environment (PHP, MySQL, Mailhog)
- Auto-detects available ports
- Renames plugin to your namespace
- Initializes database with fixtures
- Opens browser when ready

## Installation

From terminal:
```bash
claude plugin marketplace add Guiziweb/guiziweb-plugins
claude plugin install sylius-plugin@guiziweb-plugins
```

Or inside Claude Code:
```
/plugin marketplace add Guiziweb/guiziweb-plugins
/plugin install sylius-plugin@guiziweb-plugins
```

## Usage

### Create a New Plugin

Ask Claude to create a new Sylius plugin:
- "Create a new Sylius plugin"
- "Initialize a Sylius plugin project"
- "Set up a new plugin for Sylius"

The skill will:
1. Ask for company name (e.g., `Acme`)
2. Ask for plugin name (e.g., `ProductReview`)
3. Ask for description
4. Show what will be generated and ask for confirmation
5. Clone PluginSkeleton
6. Configure Docker environment
7. Check and adjust ports if needed
8. Initialize Docker containers
9. Rename plugin to your namespace
10. Initialize database with fixtures
11. Open browser to frontend and admin

## What You Get

After initialization, you'll have:

```
AcmeProductReviewPlugin/
├── src/                    # Your plugin code
├── tests/                  # Test suite
├── compose.yml            # Docker configuration
├── Makefile               # Development commands
└── ...                    # Full plugin structure
```

**Services:**
- Frontend: `http://localhost:{port}`
- Admin: `http://localhost:{port}/admin`
- MySQL: `localhost:{port}`
- Mailhog: `http://localhost:{port}`

**Default admin credentials:**
- Email: `sylius@example.com`
- Password: `sylius`

## References

- [PluginSkeleton GitHub](https://github.com/Sylius/PluginSkeleton)
- [Sylius Plugin Development Guide](https://docs.sylius.com/en/latest/book/plugins/)
