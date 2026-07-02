#!/usr/bin/env bash
# Sync production files from root to deploy/
# Usage: ./deploy.sh

set -e

ROOT="$(cd "$(dirname "$0")" && pwd)"
DEST="$ROOT/deploy"

echo "🔄 Syncing to $DEST..."

# Root HTML pages
for f in index.html hakkimizda.html felsefemiz.html iletisim.html \
          terapi-sureci.html sss.html gizlilik.html kvkk.html beyin-beden.html; do
  [ -f "$ROOT/$f" ] && cp "$ROOT/$f" "$DEST/$f" && echo "  ✓ $f"
done

# Static files
cp "$ROOT/robots.txt"  "$DEST/robots.txt"  && echo "  ✓ robots.txt"
cp "$ROOT/sitemap.xml" "$DEST/sitemap.xml" && echo "  ✓ sitemap.xml"

# Assets (fonts, js, images)
rsync -a --delete "$ROOT/assets/" "$DEST/assets/"
echo "  ✓ assets/"

# Compiled CSS
rsync -a --delete "$ROOT/dist/" "$DEST/dist/"
echo "  ✓ dist/"

# TR blog
rsync -a --delete "$ROOT/blog/" "$DEST/blog/"
echo "  ✓ blog/"

# TR service pages
rsync -a --delete "$ROOT/hizmetler/" "$DEST/hizmetler/"
echo "  ✓ hizmetler/"

# EN pages
rsync -a --delete "$ROOT/en/" "$DEST/en/"
echo "  ✓ en/"

echo ""
echo "✅ Deploy sync complete."
