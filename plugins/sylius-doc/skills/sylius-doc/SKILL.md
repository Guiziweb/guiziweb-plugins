---
name: sylius-doc
description: Search the local Sylius e-commerce framework documentation (entities, grids, hooks, forms, customization, plugins).
allowed-tools: Bash, Read, Skill
---

# Sylius Documentation Search

Search the local Sylius documentation using the index.

## FIRST: Check if documentation is ready

Check if index exists:
```bash
test -f ~/.claude/sylius-doc/index.md && echo "ready" || echo "setup needed"
```

If "setup needed":
1. Tell the user: "Setting up Sylius documentation (first time only)..."
2. Call: `Skill(skill: "sylius-doc:sync-docs")`

## Process

1. **Read the index** (`~/.claude/sylius-doc/index.md`)
2. **Analyze the user's question** and pick 1-2 most relevant files from the index
3. **Read the selected files** from `~/.claude/sylius-doc/Documentation/sylius-2.0/...`
4. **Answer** with the information found

## File Path Mapping

The index shows relative paths. Full paths are:
- `the-customization-guide/customizing-grids.md` → `~/.claude/sylius-doc/Documentation/sylius-2.0/the-customization-guide/customizing-grids.md`
- `the-book/products/attributes.md` → `~/.claude/sylius-doc/Documentation/sylius-2.0/the-book/products/attributes.md`

## Examples

- "How do Twig Hooks work?" → Read `the-customization-guide/customizing-templates.md` (hooks are documented there)
- "How to extend an entity?" → Read `the-customization-guide/customizing-models/how-to-add-a-custom-model.md`
- "How do grids work?" → Read `the-customization-guide/customizing-grids.md`
- "How to add a product attribute?" → Read `the-book/products/attributes.md`

## Rules

- Read maximum 2 files
- Answer directly with what you find
- If the user's question is about a plugin (CMS, invoicing, refund, wishlist), look in `features-plugins/`