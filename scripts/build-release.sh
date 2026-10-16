#!/usr/bin/env bash
# Builds an upload-ready zip for shared hosting (vendor/ included, no dev deps).
# Packages the committed state (HEAD), so commit your changes first.
# Usage: scripts/build-release.sh [version]
set -euo pipefail
VERSION="${1:-$(date +%Y.%m.%d)}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BUILD="$(mktemp -d)"
DEST="$BUILD/devhail"

mkdir -p "$DEST"
git -C "$ROOT" archive HEAD | tar -x -C "$DEST"
rm -rf "$DEST/tests" "$DEST/phpunit.xml"

(cd "$DEST" && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet)
# Packages installed from source carry their full git history; it's never needed at runtime.
find "$DEST/vendor" -name .git -type d -prune -exec rm -rf {} +

mkdir -p "$ROOT/dist"
rm -f "$ROOT/dist/devhail-$VERSION.zip"
(cd "$BUILD" && zip -qr "$ROOT/dist/devhail-$VERSION.zip" devhail)
rm -rf "$BUILD"
echo "Built dist/devhail-$VERSION.zip"
