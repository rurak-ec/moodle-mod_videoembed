#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC_DIR="${1:-/opt/cursos/custom-plugins/mod_page_lab_20260319}"
DST_DIR="${ROOT_DIR}/reference/mod_page"
STAMP="$(date +%Y%m%d_%H%M%S)"

if [[ ! -d "${SRC_DIR}" ]]; then
  echo "ERROR: source not found: ${SRC_DIR}" >&2
  exit 1
fi

if [[ -e "${DST_DIR}" ]]; then
  mv "${DST_DIR}" "${DST_DIR}.bak.${STAMP}"
fi

mkdir -p "${ROOT_DIR}/reference"
cp -a "${SRC_DIR}" "${DST_DIR}"

echo "OK: reference rebuilt"
echo "  source: ${SRC_DIR}"
echo "  target: ${DST_DIR}"
