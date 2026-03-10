# Guiziweb Skills

Claude Code plugins for web development workflows.

## Installation

From terminal:
```bash
claude plugin marketplace add Guiziweb/guiziweb-plugins
```

Or inside Claude Code:
```
/plugin marketplace add Guiziweb/guiziweb-plugins
```

## Update

To remove the marketplace:
```bash
claude plugin marketplace remove guiziweb-plugins
```

## Available Plugins

| Plugin | Description |
|--------|-------------|
| [sylius-doc](./plugins/sylius-doc/README.md) | Search Sylius documentation locally with index-based search |
| [sylius-plugin](./plugins/sylius-plugin/README.md) | Develop Sylius plugins — init, models, Stimulus controllers, daily dev commands |

## Local Development

To test a plugin locally without installing from the marketplace:
```bash
claude --plugin-dir /path/to/guiziweb-plugins/plugins/sylius-plugin/
claude --plugin-dir /path/to/guiziweb-plugins/plugins/sylius-doc/
```

## Compatibility

These plugins work with [Claude Code](https://github.com/anthropics/claude-code), the official Anthropic CLI.

## Contributing

More plugins coming soon. This marketplace will expand with additional web development tools.
