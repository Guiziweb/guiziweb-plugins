#!/bin/bash
# Sync Sylius documentation: clone if missing, pull if exists, then generate index

DOC_DIR="$HOME/.claude/sylius-doc/Documentation"
INDEX_FILE="$HOME/.claude/sylius-doc/index.md"

# Folders to index (add/remove as needed)
INDEX_FOLDERS=("sylius-2.0" "features-plugins")

# Clone or update documentation
if [ -d "$DOC_DIR" ]; then
  cd "$DOC_DIR"
  echo "Updating Sylius documentation..."
  git pull
else
  echo "Cloning Sylius documentation..."
  mkdir -p "$HOME/.claude/sylius-doc"
  cd "$HOME/.claude/sylius-doc"
  git clone --depth 1 https://github.com/Sylius/Documentation.git
fi

# Generate index.md with documentation tree structure
echo "Generating index..."
echo "# Sylius Documentation Index" > "$INDEX_FILE"

for folder in "${INDEX_FOLDERS[@]}"; do
  if [ -d "$DOC_DIR/$folder" ]; then
    echo "" >> "$INDEX_FILE"
    echo "## $folder/" >> "$INDEX_FILE"
    echo "" >> "$INDEX_FILE"

    cd "$DOC_DIR/$folder" && find . -type d -not -path "./.git*" -not -path "*/.gitbook*" | sort | while read dir; do
      depth=$(echo "$dir" | tr -cd '/' | wc -c)
      indent=$(printf '%*s' $((depth * 2)) '')
      name=$(basename "$dir")
      if [ "$name" != "." ]; then
        echo "${indent}**${name}/**"
      fi
      find "$dir" -maxdepth 1 -name "*.md" -type f 2>/dev/null | sort | while read f; do
        fname=$(basename "$f")
        if [ "$fname" != "SUMMARY.md" ] && [ "$fname" != "README.md" ]; then
          echo "${indent}  - ${fname}"
        fi
      done
    done >> "$INDEX_FILE"
  fi
done

echo ""
echo "✓ Sylius documentation ready!"