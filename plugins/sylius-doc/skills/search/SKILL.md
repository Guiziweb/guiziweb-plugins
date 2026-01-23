---
name: search
description: Search the local Sylius e-commerce framework documentation (entities, grids, hooks, forms, customization, plugins).
allowed-tools: Bash, Read, Skill
---

# Sylius Documentation Search

## FIRST: Check if documentation is ready

```bash
test -f ~/.cache/sylius-doc/index.md && echo "ready" || echo "setup needed"
```

If "setup needed":
1. Tell the user: "Setting up Sylius documentation (first time only)..."
2. Call: `Skill(skill: "sylius-doc:sync")`

## Process

1. **Read the index** (`~/.cache/sylius-doc/index.md`)
2. **Pick 1-2 relevant files** based on the user's question
3. **Read the files** from `~/.cache/sylius-doc/`
4. **Answer** with the information found