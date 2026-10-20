#!/usr/bin/env bash
# Sube el sitio a Hostinger por SSH y lo verifica. Se corre desde Git Bash en el PC de Santi:
#
#   bash tools/deploy.sh             empaqueta, prueba en el servidor, respalda y publica
#   bash tools/deploy.sh --dry-run   igual, pero solo lista lo que cambiaría (no publica nada)
#
# Pasos: empaqueta app/, public_html/ y tools/ (sin logs, respaldos, config.local.php ni
# herramientas locales) -> lo sube a la carpeta del dominio -> lo descomprime aparte (.release)
# -> revisa sintaxis, catálogo y pruebas con el PHP 8.3 del servidor -> respalda lo publicado en
# deploy-backups/ (guarda los 10 últimos) -> sincroniza con rsync --delete sin tocar app/logs,
# app/backups, app/config.local.php ni public_html/.well-known -> comprueba que el sitio responda.
#
# La cuenta de Hostinger aloja otros sitios: este script solo entra a domains/$DOMAIN.
# Acceso: clave SSH ~/.ssh/taptop_hostinger (la pública está en hPanel -> Acceso SSH).
# Nunca poner contraseñas aquí.

set -euo pipefail

HOST=212.1.211.197
PORT=65002
USER_NAME=u555341304
DOMAIN=taptopmobile.com
KEY="$HOME/.ssh/taptop_hostinger"
PHP_BIN=/opt/alt/php83/usr/bin/php
KEEP_BACKUPS=10

DRY_RUN=0
if [ "${1:-}" = "--dry-run" ]; then
  DRY_RUN=1
elif [ -n "${1:-}" ]; then
  echo "Uso: bash tools/deploy.sh [--dry-run]" >&2
  exit 2
fi

cd "$(dirname "$0")/.."
if [ ! -f public_html/index.php ] || [ ! -f app/bootstrap.php ]; then
  echo "No encuentro app/ y public_html/ junto a tools/." >&2
  exit 1
fi
[ -f "$KEY" ] || { echo "Falta la clave SSH $KEY" >&2; exit 1; }

SSH_OPTS=(-i "$KEY" -o IdentitiesOnly=yes -o BatchMode=yes -o ConnectTimeout=20)
WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT

echo "1/4 Empaquetando..."
tar --owner=0 --group=0 --numeric-owner --mode='u+rwX,go+rX,go-w' \
    --exclude='app/logs/*' --exclude='app/backups/*' --exclude='app/config.local.php' \
    --exclude='tools/dev-router.php' --exclude='tools/deploy.sh' \
    -czf "$WORK/release.tar.gz" app public_html tools

echo "2/4 Subiendo a $DOMAIN..."
scp -q -P "$PORT" "${SSH_OPTS[@]}" "$WORK/release.tar.gz" "$USER_NAME@$HOST:domains/$DOMAIN/.release.tar.gz"

echo "3/4 Probando en el servidor..."
# El script remoto va dentro de main() para que bash lo lea completo antes de ejecutar nada.
ssh -p "$PORT" "${SSH_OPTS[@]}" "$USER_NAME@$HOST" bash -s -- "$DOMAIN" "$PHP_BIN" "$KEEP_BACKUPS" "$DRY_RUN" <<'REMOTE'
main() {
  set -euo pipefail
  local domain=$1 php=$2 keep=$3 dry=$4
  cd "$HOME/domains/$domain"

  rm -rf .release
  mkdir .release
  tar -xzf .release.tar.gz -C .release
  rm -f .release.tar.gz
  trap 'rm -rf "$HOME/domains/'"$domain"'/.release"' EXIT

  # Si algo falla aquí, el sitio publicado no se toca. (CageFS no tiene /dev/fd: nada de <(...).)
  local out
  out=$(find .release -name '*.php' -exec "$php" -l {} \; 2>&1 | grep -v '^No syntax errors detected' || true)
  [ -z "$out" ] || { echo "$out"; echo "Errores de sintaxis: no se publica." >&2; return 1; }

  out=$(cd .release && "$php" tools/validate-products.php 2>&1) || { echo "$out"; echo "Catálogo con errores: no se publica." >&2; return 1; }
  out=$(cd .release && "$php" tools/selftest.php 2>&1) || { echo "$out" | grep -E '^FAIL|fallaron'; echo "Pruebas fallidas: no se publica." >&2; return 1; }
  echo "  Pruebas: $(echo "$out" | tail -n 1)"

  sync_dirs() {
    rsync -rlptD --delete "$@" --exclude=/logs/ --exclude=/backups/ --exclude=/config.local.php .release/app/ app/
    rsync -rlptD --delete "$@" --exclude=/.well-known/ .release/public_html/ public_html/
    rsync -rlptD --delete "$@" .release/tools/ tools/
  }

  if [ "$dry" = 1 ]; then
    echo "  Cambios que se publicarían:"
    sync_dirs --dry-run --itemize-changes
    return 0
  fi

  mkdir -p deploy-backups
  local existing="" d backup
  for d in app public_html tools; do
    if [ -d "$d" ]; then existing="$existing $d"; fi
  done
  if [ -n "$existing" ]; then
    backup="deploy-backups/predeploy-$(date +%Y%m%d-%H%M%S).tar.gz"
    # shellcheck disable=SC2086
    tar -czf "$backup" --exclude=app/logs --exclude=app/backups --exclude=app/config.local.php $existing
    echo "  Respaldo de lo anterior: $backup"
  fi
  ls -1t deploy-backups/predeploy-*.tar.gz 2>/dev/null | tail -n +"$((keep + 1))" | xargs -r rm -f

  mkdir -p app/logs app/backups
  sync_dirs
  echo "  Publicado."
}
main "$@"
REMOTE

if [ "$DRY_RUN" = 1 ]; then
  echo "Prueba terminada: no se publicó nada."
  exit 0
fi

echo "4/4 Revisando el sitio en vivo..."
fail=0
check() {
  local code
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 30 "https://$DOMAIN$1")
  if [ "$code" = "$2" ]; then
    echo "  $1 -> $code"
  else
    echo "  $1 -> $code (se esperaba $2)"
    fail=1
  fi
}
check / 200
check /productos 200
check /contacto 200
check /no-existe 404
check /app/config.php 404
if [ "$fail" = 0 ]; then
  echo "Listo: https://$DOMAIN"
else
  echo "Revisar: algo no respondió como se esperaba." >&2
  exit 1
fi
