# Guiziweb Skills

Claude Code plugins for web development workflows.

## Installation

From terminal:
```bash
claude plugin marketplace add Guiziweb/guiziweb-plugins
claude plugin install sylius-doc@guiziweb-plugins
```

Or inside Claude Code:
```
/plugin marketplace add Guiziweb/guiziweb-plugins
/plugin install sylius-doc@guiziweb-plugins
```

## Available Plugins

### [Sylius Documentation](./plugins/sylius-doc/)

Search and explore Sylius e-commerce framework documentation locally.

- Fast search using filename patterns
- Lazy loading: Documentation cloned only when first used
- Shared documentation across all projects

[View documentation →](./plugins/sylius-doc/README.md)

### [Sylius Plugin Initializer](./plugins/sylius-init-plugin/)

Create new Sylius plugins from scratch with Docker environment.

- Clones official PluginSkeleton
- Configures Docker environment
- Initializes database with fixtures

[View documentation →](./plugins/sylius-init-plugin/README.md)

## Compatibility

These plugins work with [Claude Code](https://github.com/anthropics/claude-code), the official Anthropic CLI.

## Contributing

More plugins coming soon. This marketplace will expand with additional web development tools.