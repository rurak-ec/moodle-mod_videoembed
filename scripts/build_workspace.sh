#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REF_DIR="${1:-${ROOT_DIR}/reference/mod_page}"
OUT_DIR="${2:-${ROOT_DIR}/workspace/mod_videogdrive}"
STAMP="$(date +%Y%m%d_%H%M%S)"
NEW_VERSION="$(date +%Y%m%d)00"

if [[ ! -d "${REF_DIR}" ]]; then
  echo "ERROR: reference directory not found: ${REF_DIR}" >&2
  exit 1
fi

if [[ -e "${OUT_DIR}" ]]; then
  mv "${OUT_DIR}" "${OUT_DIR}.bak.${STAMP}"
fi

mkdir -p "$(dirname "${OUT_DIR}")"
cp -a "${REF_DIR}" "${OUT_DIR}"

# Remove lab metadata copied from reference.
rm -f "${OUT_DIR}/README_LAB_ES.md"

# Rename files/directories containing "page" token.
while IFS= read -r path; do
  newpath="${path//page/videogdrive}"
  if [[ "${path}" != "${newpath}" ]]; then
    mv "${path}" "${newpath}"
  fi
done < <(find "${OUT_DIR}" -depth -name '*page*')

# Apply source-level renames for independent module namespace/component.
while IFS= read -r file; do
  perl -i -pe '
    s/\bmod_page\b/mod_videogdrive/g;
    s/\bmod_page_/mod_videogdrive_/g;
    s/\bmoodle1_mod_page_handler\b/moodle1_mod_videogdrive_handler/g;
    s/\bmod_page_generator\b/mod_videogdrive_generator/g;
    s/\bmod_page_mod_form\b/mod_videogdrive_mod_form/g;
    s/\bxmldb_page_/xmldb_videogdrive_/g;
    s/\bbackup_page_/backup_videogdrive_/g;
    s/\brestore_page_/restore_videogdrive_/g;
    s/\bpage_content_file_info\b/videogdrive_content_file_info/g;
    s#(/mod/)page(/)#$1videogdrive$2#g;
    s#mod/page#mod/videogdrive#g;
    s#\\/mod\\/page\\/#\\/mod\\/videogdrive\\/#g;
    s#/activity/page#/activity/videogdrive#g;
    s/\bpage_([a-zA-Z0-9_]+)/videogdrive_$1/g;
    s/\bPAGE([A-Z_]+)/VIDEOGDRIVE$1/g;
    s/\047page\047/\047videogdrive\047/g;
    s/\042page\042/\042videogdrive\042/g;
    s/\047page\//\047videogdrive\//g;
    s/\042page\//\042videogdrive\//g;
    s/\bpage:addinstance\b/videogdrive:addinstance/g;
    s/\bpage:view\b/videogdrive:view/g;
    s/page-mod-page-x/videogdrive-mod-videogdrive-x/g;
    s/\047page\.xml\047/\047videogdrive.xml\047/g;
    s/\042page\.xml\042/\042videogdrive.xml\042/g;
    s/activities\/page_/activities\/videogdrive_/g;
    s/\/page\/id/\/videogdrive\/id/g;
    s/\bPATH=\"mod\/page\/db\"/PATH=\"mod\/videogdrive\/db\"/g;
    s/\bNAME=\"page\"/NAME=\"videogdrive\"/g;
  ' "${file}"
done < <(find "${OUT_DIR}" -type f \( -name '*.php' -o -name '*.xml' -o -name '*.txt' -o -name '*.md' -o -name '*.feature' -o -name '*.yml' -o -name '*.json' \))

# Ensure plugin metadata is unique for this lab module.
if [[ -f "${OUT_DIR}/version.php" ]]; then
  sed -i -E "s/(\\\$plugin->version\\s*=\\s*)[0-9]+/\\1${NEW_VERSION}/" "${OUT_DIR}/version.php"
  sed -i -E "s/(\\\$plugin->component\\s*=\\s*)'mod_[^']+';/\\1'mod_videogdrive';/" "${OUT_DIR}/version.php"
fi

# Give the module a clear lab identity in UI strings.
if [[ -f "${OUT_DIR}/lang/en/videogdrive.php" ]]; then
  sed -i "s/Create a new videogdrive resource/Insertar Video de Google Drive/g" "${OUT_DIR}/lang/en/videogdrive.php"
  sed -i "s/\$string\\['modulename'\\] = '.*';/\$string['modulename'] = 'Insertar Video de Google Drive';/g" "${OUT_DIR}/lang/en/videogdrive.php"
  sed -i "s/\$string\\['modulenameplural'\\] = '.*';/\$string['modulenameplural'] = 'Insertar Video de Google Drive';/g" "${OUT_DIR}/lang/en/videogdrive.php"
  sed -i "s/\$string\\['pluginname'\\] = '.*';/\$string['pluginname'] = 'Insertar Video de Google Drive';/g" "${OUT_DIR}/lang/en/videogdrive.php"
fi

echo "OK: workspace built"
echo "  reference: ${REF_DIR}"
echo "  workspace: ${OUT_DIR}"
echo "  version:   ${NEW_VERSION}"
