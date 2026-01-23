---
name: setup-docs
description: Initialize Sylius documentation (clones repo to ~/.claude/sylius-doc/)
allowed-tools: Bash
---

# Setup Sylius Documentation

Clone the Sylius documentation repository to `~/.claude/sylius-doc/Documentation/`.

## Process

1. Check if documentation already exists
2. If not, clone it with `git clone --depth 1` (fast, ~10s)
3. Display success message
4. **IMPORTANT**: After this skill completes, immediately return control to the calling skill (sylius-doc) so it can continue searching

Run the following command:

```bash
DOC_DIR="$HOME/.claude/sylius-doc/Documentation"

if [ -d "$DOC_DIR" ]; then
  echo "✓ Sylius documentation already exists at: $DOC_DIR"
  exit 0
fi

echo "Cloning Sylius documentation (this will take ~10 seconds)..."
mkdir -p "$HOME/.claude/sylius-doc"
cd "$HOME/.claude/sylius-doc"
git clone --depth 1 https://github.com/Sylius/Documentation.git

echo ""
echo "✓ Sylius documentation ready!"
```

This only needs to run once.
