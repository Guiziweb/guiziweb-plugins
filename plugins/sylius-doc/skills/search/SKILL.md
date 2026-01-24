---
name: search
description: Search the local Sylius e-commerce framework documentation (entities, grids, hooks, forms, customization, plugins).
allowed-tools: Read, Skill
---

# Sylius Documentation Search

Documentation is auto-cloned on session start via hook.

## Process

1. **Read the index** (`~/.cache/sylius-doc/index.md`)
2. **Pick 1-2 relevant files** based on the user's question
3. **Read the files** from `~/.cache/sylius-doc/`
4. **Answer** with the information found

## Fallback

If index.md is not found, call `Skill(skill: "sylius-doc:sync")` then retry.