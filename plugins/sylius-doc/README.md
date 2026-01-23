# Sylius Documentation Plugin

Search and explore Sylius e-commerce framework documentation locally.

## Features

- Fast local search using an indexed documentation tree
- Claude reads the index and picks the most relevant files
- Lazy loading: Documentation cloned only when first used
- Automatic index generation for quick navigation

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

## Configuration

To allow the skill to read documentation without prompts, add this to your `~/.claude/settings.json`:

```json
{
  "permissions": {
    "allow": [
      "Read(~/.cache/sylius-doc/**)"
    ]
  }
}
```

## Usage

### Search Sylius Documentation

Ask questions about Sylius:
- "How do Twig Hooks work?"
- "How do I extend an entity?"
- "How to customize grids?"
- "How to add a product attribute?"

### First Time Setup

The first time you use the skill, it automatically:
1. Clones the Sylius documentation
2. Generates an index of all files

### Update Documentation

To pull the latest documentation and regenerate the index:
```
/sylius-doc:sync
```

## How It Works

1. Claude reads the documentation index (`~/.cache/sylius-doc/index.md`)
2. Based on your question, Claude picks 1-2 relevant files
3. Claude reads those files and answers your question

The index shows the full documentation tree structure, allowing Claude to understand where each topic is documented (e.g., Twig Hooks are in `customizing-templates.md`).

## Documentation Structure

```
~/.cache/sylius-doc/
├── index.md                    # Tree index of all files
├── Documentation/
│   ├── sylius-2.0/             # How to customize Sylius
│   └── features-plugins/       # Official plugins (CMS, invoicing...)
└── Stack/
    └── docs/                   # Grid, Resource, TwigHooks
```

## Limitations

- **Not mandatory**: Claude may use this skill automatically, but you can ask explicitly: "check the Sylius docs"
- **Manual update**: Use `/sylius-doc:sync` to pull the latest changes when needed