#!/usr/bin/env bash
# Builds an upload-ready zip for shared hosting (vendor/ included, no dev deps).
# Usage: scripts/build-release.sh [version]
set -euo pipefail
VERSION="${1:-$(date +%Y.%m.%d)}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BUILD="$(mktemp -d)"
DEST="$BUILD/devhail"

mkdir -p "$DEST"
rsync -a "$ROOT/" "$DEST/" \
  --exclude .git --exclude .env --exclude node_modules --exclude tests --exclude vendor \
  --exclude 'database/*.sqlite' --exclude 'storage/app/installed.lock' \
  --exclude 'storage/logs/*.log' --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*.php' --exclude 'storage/framework/cache/data/*' \
  --exclude 'bootstrap/cache/*.php' --exclude dist

(cd "$DEST" && composer install --no-dev --optimize-autoloader --no-interaction --quiet)

mkdir -p "$ROOT/dist"
(cd "$BUILD" && zip -qr "$ROOT/dist/devhail-$VERSION.zip" devhail)
rm -rf "$BUILD"
echo "Built dist/devhail-$VERSION.zip"
