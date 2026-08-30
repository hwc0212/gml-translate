#!/usr/bin/env bash
# GML Translate release packager.

set -euo pipefail

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PLUGIN_DIR="$( cd "$SCRIPT_DIR/.." && pwd )"
PLUGINS_DIR="$( cd "$PLUGIN_DIR/.." && pwd )"
PLUGIN_SLUG="gml-translate"

VERSION="$( grep -m1 '^ \* Version:' "$PLUGIN_DIR/gml-translate.php" | sed 's/.*Version: *//' | tr -d '[:space:]' )"
if [ -z "$VERSION" ]; then
  echo "ERROR: could not read version from gml-translate.php" >&2
  exit 1
fi

ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"
ZIP_PATH="${PLUGINS_DIR}/${ZIP_NAME}"

echo "Packaging ${PLUGIN_SLUG} v${VERSION} → ${ZIP_PATH}"

php "$PLUGIN_DIR/bin/translation-core.php" verify

rm -f "$ZIP_PATH"

cd "$PLUGINS_DIR"
zip -r "$ZIP_NAME" "$PLUGIN_SLUG" \
  --exclude "${PLUGIN_SLUG}/.git/*" \
  --exclude "${PLUGIN_SLUG}/.gitignore" \
  --exclude "${PLUGIN_SLUG}/.github/*" \
  --exclude "${PLUGIN_SLUG}/tests/*" \
  --exclude "${PLUGIN_SLUG}/tools/*" \
  --exclude "${PLUGIN_SLUG}/bin/*" \
  --exclude "${PLUGIN_SLUG}/docs/*" \
  --exclude "${PLUGIN_SLUG}/CHANGELOG.md" \
  --exclude "${PLUGIN_SLUG}/README.md" \
  --exclude "${PLUGIN_SLUG}/INSTALL.md" \
  --exclude "${PLUGIN_SLUG}/translation-core.lock.json" \
  --exclude "${PLUGIN_SLUG}/includes/vendor/gml-translation-core/GENERATED.md" \
  --exclude "${PLUGIN_SLUG}/languages/*.po" \
  --exclude "${PLUGIN_SLUG}/languages/*.pot" \
  --exclude "${PLUGIN_SLUG}/.DS_Store" \
  --exclude "${PLUGIN_SLUG}/Thumbs.db" \
  -q

if unzip -Z1 "$ZIP_NAME" | grep -Fx "${PLUGIN_SLUG}/${PLUGIN_SLUG}.php" >/dev/null; then
  echo "Internal path OK: ${PLUGIN_SLUG}/${PLUGIN_SLUG}.php"
else
  echo "ERROR: missing ${PLUGIN_SLUG}/${PLUGIN_SLUG}.php in release ZIP" >&2
  exit 1
fi

SIZE="$( du -sh "$ZIP_NAME" | cut -f1 )"
echo "Done: ${ZIP_NAME} (${SIZE})"
