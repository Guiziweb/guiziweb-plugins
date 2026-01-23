---
name: sylius-doc
description: Search the local Sylius e-commerce framework documentation (entities, grids, hooks, forms, customization, plugins).
allowed-tools: Glob, Read, Skill
---

# Sylius Documentation Search

Search the local Sylius documentation efficiently. One file = one concept.

## FIRST: Check if documentation exists

Use Glob to check if documentation exists:
```
Glob(pattern="README.md", path="~/.claude/sylius-doc/Documentation")
```

If NO files found (documentation not cloned yet):
1. Tell the user: "First time setup: Cloning Sylius documentation (~10 seconds, this is normal)..."
2. Call the setup skill: `Skill(skill: "sylius-doc:setup-docs")`
3. **After setup completes**, search again with: `Skill(skill: "sylius-doc:sylius-doc", args: "<original user question>")`

## Process (STRICT - Follow exactly)

1. **ONE Glob search** with the main keyword from the user's question
2. **Read 1-2 most relevant files** from the results
3. **Answer immediately** with the information found

## Rules

- Use ONLY ONE Glob call: `Glob(pattern="**/*keyword*.md", path="~/.claude/sylius-doc/Documentation")`
- Extract ONE main keyword (e.g., "grids", "product", "entity", "hooks")
- Read maximum 2 files
- DO NOT make multiple Glob searches with variations
- DO NOT search for "index", "component", "bundle" files
- Answer directly with what you find

## Examples

- "How do grids work?" → ONE Glob: `**/*grid*.md` → Read top 1-2 files → Answer
- "Extend Product entity" → ONE Glob: `**/*product*.md` → Read top 1-2 files → Answer
- "How do hooks work?" → ONE Glob: `**/*hook*.md` → Read top 1-2 files → Answer

Be fast. One search, 1-2 reads, done.