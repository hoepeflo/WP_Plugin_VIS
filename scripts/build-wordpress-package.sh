#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_DIR="$REPO_ROOT/vis"
MAIN_PLUGIN_FILE="$PLUGIN_DIR/vis.php"
DIST_DIR="$REPO_ROOT/dist"

if [[ ! -f "$MAIN_PLUGIN_FILE" ]]; then
  echo "Fehler: Plugin-Hauptdatei nicht gefunden: $MAIN_PLUGIN_FILE" >&2
  exit 1
fi

VERSION="$(sed -n 's/^ \* Version: //p' "$MAIN_PLUGIN_FILE" | head -n 1)"
VERSION="${VERSION:-dev}"
PACKAGE_NAME="vis-${VERSION}.zip"
PACKAGE_PATH="$DIST_DIR/$PACKAGE_NAME"

mkdir -p "$DIST_DIR"
rm -f "$PACKAGE_PATH"

(
  cd "$REPO_ROOT"
  zip -rq "$PACKAGE_PATH" vis \
    -x 'vis/.git/*' \
    -x 'vis/.DS_Store' \
    -x 'vis/**/.DS_Store'
)

echo "WordPress-Installationsdatei erstellt: $PACKAGE_PATH"
