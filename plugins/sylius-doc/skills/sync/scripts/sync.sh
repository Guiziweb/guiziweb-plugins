#!/bin/bash
set -e

BASE_DIR="$HOME/.cache/sylius-doc"
INDEX_FILE="$BASE_DIR/index.md"

mkdir -p "$BASE_DIR"

# Clone a repo with sparse checkout
clone_sparse() {
  local name="$1"
  local repo="$2"
  local folders="$3"
  local branch="${4:-}"

  rm -rf "${BASE_DIR:?}/$name"
  echo "Cloning $name..."

  local branch_flag=""
  [ -n "$branch" ] && branch_flag="--branch $branch"

  git clone --depth 1 --filter=blob:none --sparse $branch_flag "$repo" "$BASE_DIR/$name"
  git -C "$BASE_DIR/$name" sparse-checkout set $folders --no-cone
  find "$BASE_DIR/$name" -type d -name ".gitbook" -exec rm -rf {} + 2>/dev/null || true
  rm -rf "$BASE_DIR/$name/.git"
}

# Generate tree index for a folder
index_folder() {
  local dir="$1"
  local title="$2"

  [ ! -d "$dir" ] && return

  {
    echo ""
    echo "## $title"
    echo ""

    find "$dir" -type d ! -path "*/.git*" ! -path "*/.gitbook*" | sort | while read -r d; do
      local depth=$(echo "$d" | sed "s|$dir||" | tr -cd '/' | wc -c)
      local indent=$(printf '%*s' $((depth * 2)) '')
      local name=$(basename "$d")

      [ "$name" != "$(basename "$dir")" ] && echo "${indent}**${name}/**"

      find "$d" -maxdepth 1 -type f -name "*.md" ! -name "SUMMARY.md" ! -name "README.md" | sort | while read -r f; do
        echo "${indent}  - $(basename "$f")"
      done
    done
  } >> "$INDEX_FILE"
}

# Clone repos
clone_sparse "Sylius" "https://github.com/Sylius/Sylius.git" "docs" "2.0"
clone_sparse "Stack" "https://github.com/Sylius/Stack.git" "docs"

# Generate index
echo "Generating index..."
echo "# Sylius Documentation Index" > "$INDEX_FILE"

index_folder "$BASE_DIR/Sylius/docs" "Sylius/docs/ (Sylius 2.0 documentation)"
index_folder "$BASE_DIR/Stack/docs" "Stack/docs/ (grid, resource, twig-hooks...)"

echo ""
echo "✓ Sylius documentation ready!"