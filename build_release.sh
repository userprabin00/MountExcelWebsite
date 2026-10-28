#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RELEASE_DIR="$ROOT/release"
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
mkdir -p "$RELEASE_DIR"

while IFS= read -r -d '' file; do
  rel="${file#"$ROOT"/}"
  case "/$rel/" in
    */.git/*|*/tests/*|*/release/*) continue ;;
    */backups/*|*/logs/*)
      case "$rel" in backups/.gitkeep|backups/.htaccess|logs/.gitkeep|logs/.htaccess) ;; *) continue ;; esac ;;
    */uploads/gallery/*|*/uploads/staff/*)
      case "$rel" in uploads/gallery/.gitkeep|uploads/gallery/.htaccess|uploads/staff/.gitkeep|uploads/staff/.htaccess) ;; *) continue ;; esac ;;
  esac
  case "$rel" in
    .env|.gitignore|README.md|tests/*|database/seed.php|database/seed_content.php|images/background1.jpg|*.zip) continue ;;
    *.md) [ "$rel" = "DEPLOYMENT.md" ] || continue ;;
  esac
  if [[ "$rel" == *.sql && "$rel" != "database/schema.sql" ]]; then continue; fi
  mkdir -p "$STAGE/$(dirname "$rel")"
  cp "$file" "$STAGE/$rel"
done < <(find "$ROOT" -type f -print0)

mkdir -p "$STAGE/backups" "$STAGE/logs" "$STAGE/uploads/gallery" "$STAGE/uploads/staff"
for dir in backups logs uploads/gallery uploads/staff; do
  touch "$STAGE/$dir/.gitkeep"
done
cp "$ROOT/backups/.htaccess" "$STAGE/backups/.htaccess" 2>/dev/null || true
cp "$ROOT/logs/.htaccess" "$STAGE/logs/.htaccess" 2>/dev/null || true
for dir in uploads/gallery uploads/staff; do
  cp "$ROOT/$dir/.htaccess" "$STAGE/$dir/.htaccess" 2>/dev/null || printf 'Require all denied\nDeny from all\n' > "$STAGE/$dir/.htaccess"
done

if ! command -v zip >/dev/null 2>&1; then
  echo "The zip utility is required to build the release archive." >&2
  exit 1
fi
rm -f "$RELEASE_DIR/MountExcelWebsite-deploy.zip"
(cd "$STAGE" && zip -qr "$RELEASE_DIR/MountExcelWebsite-deploy.zip" .)
echo "Created $RELEASE_DIR/MountExcelWebsite-deploy.zip"
