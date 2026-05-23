# Guiziweb Plugins

Claude Code plugins by Guiziweb.

## Available plugins

| Plugin | Description |
|--------|-------------|
| [`sylius-plugin`](./plugins/sylius-plugin/README.md) | Skills for developing a Sylius plugin (PluginSkeleton, test application, distributable package) |
| [`sylius-app`](./plugins/sylius-app/README.md) | Skills for integrating in a Sylius application (extend core entities, add custom resources) |
| [`sylius-stack`](./plugins/sylius-stack/README.md) | Skills for building Symfony apps with the Sylius Stack |
| [`claude-hud`](./plugins/claude-hud/README.md) | Minimal context + rate-limits statusline HUD |

## Installation

```bash
claude plugin marketplace add Guiziweb/guiziweb-plugins
claude plugin install <plugin-name>@guiziweb-plugins
```

## Update

```bash
claude plugin marketplace update guiziweb-plugins
claude plugin update <plugin-name>@guiziweb-plugins
```

## Uninstall

```bash
claude plugin uninstall <plugin-name>@guiziweb-plugins
```

## Local development

```bash
claude --plugin-dir /path/to/guiziweb-plugins/plugins/<plugin-name>/
```