#!/usr/bin/env bash
# Print the CHANGELOG.md body for one version (heading excluded).
# Usage: changelog-section.sh <version|vTag> [changelog file]
set -euo pipefail

version="${1#v}"
file="${2:-CHANGELOG.md}"

body="$(awk -v version="$version" '
    index($0, "## [" version "]") == 1 { found = 1; next }
    found && (/^## \[/ || /^\[[^]]+\]: /) { exit }
    found { print }
' "$file" | sed -e '/./,$!d' | sed -e ':a' -e '/^\n*$/{$d;N;ba' -e '}')"

if [ -z "$body" ]; then
    echo "No CHANGELOG section found for version ${version} in ${file}." >&2
    exit 1
fi

printf '%s\n' "$body"
