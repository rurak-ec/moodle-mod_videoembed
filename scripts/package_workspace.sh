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
cp -a "${SRC_DIR}" "${STAGE_DIR}/videoembed"

(
  cd "${STAGE_DIR}"
  zip -rq "${OUT_ZIP}" "videoembed" -x '*.DS_Store' '*__MACOSX*' '*/.git/*'
)

echo "OK: package created"
echo "  ${OUT_ZIP}"
