# Sylius Documentation Plugin

Search and explore Sylius e-commerce framework documentation locally.

## Features

- Search local Sylius documentation
- Lazy loading: Documentation cloned only when first used
- Fast search using filename patterns (one file = one concept)
- Automatic initialization when needed

## Installation

```bash
/plugin marketplace add Guiziweb/guiziweb-plugins
/plugin install sylius-doc@guiziweb-plugins
```

## Configuration

To allow the skill to read documentation without prompts, add this to your `~/.claude/settings.json`:

```json
{
  "permissions": {
    "allow": [
      "Read(~/.claude/sylius-doc/**)"
    ]
  }
}
```

## Usage

### Search Sylius Documentation

Ask questions about Sylius (Claude will automatically invoke the skill):
- "How do I extend the Product entity?"
- "Show me how Sylius grids work"
- "How to customize forms in Sylius?"

### First Time Setup

The first time you use the skill, it will automatically clone the Sylius documentation to `~/.claude/sylius-doc/Documentation/` (~10 seconds, with visible progress).

## How It Works

1. When you ask a Sylius question, the skill checks if documentation exists
2. If not found, it automatically clones the docs (one-time setup, ~10s)
3. Searches using filename patterns (fast and efficient)
4. Reads the most relevant file(s) and answers your question

Documentation is stored in `~/.claude/sylius-doc/` and shared across all projects.

## Why Local Clone?

We tested several approaches before settling on local cloning:

| Approach | Result |
|----------|--------|
| **WebFetch** | Too slow - fetching pages on-demand adds latency |
| **MCP Server** | Too slow - even with Sylius docs available via MCP |
| **Local clone** | Fast - Claude searches local files instantly |

Local cloning is the simplest and fastest solution: one-time ~10s setup, then instant searches forever.

## Limitations

- **Not mandatory**: Claude may use this skill automatically, but nothing forces it to. Don't hesitate to ask explicitly: "check the Sylius docs"
- **No auto-update**: The documentation is cloned once and not automatically updated. To refresh, delete the folder and let the skill re-clone:
  ```bash
  rm -rf ~/.claude/sylius-doc
  ```
- **Future improvement**: An update command (`/sylius-doc update`) is planned to pull the latest version without manual deletion.