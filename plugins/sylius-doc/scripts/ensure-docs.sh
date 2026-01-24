#!/bin/bash
[ -f ~/.cache/sylius-doc/index.md ] || bash "${CLAUDE_PLUGIN_ROOT}/skills/sync/scripts/sync.sh"