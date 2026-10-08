#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC_DIR="${1:-${ROOT_DIR}/workspace/mod_videoembed}"
BUILD_DIR="${ROOT_DIR}/build"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUT_ZIP="${BUILD_DIR}/videoembed_${STAMP}.zip"
STAGE_DIR="$(mktemp -d)"

if [[ ! -d "${SRC_DIR}" ]]; then
  echo "ERROR: workspace plugin not found: ${SRC_DIR}" >&2
  echo "Run ./scripts/build_workspace.sh first." >&2
  exit 1
fi

mkdir -p "${BUILD_DIR}"

cleanup() {
  rm -rf "${STAGE_DIR}"
}
trap cleanup EXIT

# Moodle expects activity module folder name inside mod/ as "videoembed" (not "mod_videoembed").
if command -v zip >/dev/null 2>&1; then
  cp -a "${SRC_DIR}" "${STAGE_DIR}/videoembed"
  (
    cd "${STAGE_DIR}"
    zip -rq "${OUT_ZIP}" "videoembed" -x '*.DS_Store' '*__MACOSX*' '*/.git/*'
  )
elif command -v python3 >/dev/null 2>&1; then
  python3 -c "
import sys, os, zipfile
src = sys.argv[1]
out = sys.argv[2]
with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
    for root, dirs, files in os.walk(src):
        for f in files:
            if f in ('.DS_Store',) or '__MACOSX' in root or '/.git' in root:
                continue
            full = os.path.join(root, f)
            rel = os.path.relpath(full, src)
            z.write(full, os.path.join('videoembed', rel))
" "${SRC_DIR}" "${OUT_ZIP}"
else
  echo "ERROR: neither zip nor python3 is installed." >&2
  exit 1
fi

echo "OK: package created"
echo "  ${OUT_ZIP}"
