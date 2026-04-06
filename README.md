# Guiziweb Skills

Claude Code plugins for Sylius plugin development.

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
| [sylius-plugin](./plugins/sylius-plugin/README.md) | Develop Sylius plugins — init, models, Stimulus controllers, daily dev commands |
| [code-review](./plugins/code-review/README.md) | Automated code review for Sylius plugin pull requests — checks for bugs and logic errors |

## Local Development

To test a plugin locally without installing from the marketplace:
```bash
claude --plugin-dir /path/to/guiziweb-plugins/plugins/sylius-plugin/
```

## Compatibility

These plugins work with [Claude Code](https://github.com/anthropics/claude-code), the official Anthropic CLI.
