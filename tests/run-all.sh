#!/usr/bin/env bash

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

find "$ROOT" -type f -name '*.php' -print0 | xargs -0 -n1 php -l

for test_file in "$ROOT"/tests/integration/*.php; do
    php "$test_file"
done

while IFS= read -r -d '' script_file; do
    node --check "$script_file"
done < <(find "$ROOT/assets" -type f -name '*.js' -print0)

echo "OK all GML Translate checks"
