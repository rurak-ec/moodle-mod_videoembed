# moodle-mod_videoembed

Moodle activity module **`mod_videoembed`** (display name: **Video**) — embed one video per
activity from Google Drive, YouTube, Vimeo or Bunny Stream.

[![moodle-plugin-ci](https://github.com/rurak-ec/moodle-mod_videoembed/actions/workflows/moodle-plugin-ci.yml/badge.svg)](https://github.com/rurak-ec/moodle-mod_videoembed/actions/workflows/moodle-plugin-ci.yml)

- **Component:** `mod_videoembed`
- **Moodle:** 4.5 – 5.3 (`$plugin->supported = [405, 503]`)
- **PHP:** 8.1 – 8.4 (Moodle 4.5 supports PHP 8.1–8.3; Moodle 5.2+ requires PHP 8.3+)
- **License:** [GNU GPL v3 or later](LICENSE)

---

## English

### What it does
A teacher pastes a single video URL into a *Video* activity and Moodle renders it inline as a
responsive 16:9 iframe (with an accessible title), followed by an optional description.

### Supported providers
| Provider | Input example | Rendered embed |
|---|---|---|
| Google Drive | `https://drive.google.com/file/d/FILE_ID/view` | `.../file/d/FILE_ID/preview` |
| YouTube | `watch`, `youtu.be`, `embed`, `shorts` URLs | `https://www.youtube.com/embed/VIDEO_ID` |
| Vimeo | `https://vimeo.com/VIDEO_ID` | `https://player.vimeo.com/video/VIDEO_ID` |
| Bunny Stream | `https://player.mediadelivery.net/play/LIBRARY_ID/VIDEO_ID` | `.../embed/LIBRARY_ID/VIDEO_ID` |

URLs are parsed strictly per provider (host allow-list + ID validation) and normalised to a
canonical embed URL; unsupported or malformed URLs are rejected.

### Installation
1. Copy `workspace/mod_videoembed` into your Moodle as `mod/videoembed`.
2. Run the upgrade and purge caches:
   ```bash
   php admin/cli/upgrade.php --non-interactive
   php admin/cli/purge_caches.php
   ```
3. Confirm under *Site administration → Plugins → Activity modules*.

### Documentation & changes
- Plugin README: [`workspace/mod_videoembed/README.md`](workspace/mod_videoembed/README.md)
- Moodle-style readme: [`workspace/mod_videoembed/readme.txt`](workspace/mod_videoembed/readme.txt)
- Changelog: [`workspace/mod_videoembed/CHANGELOG.md`](workspace/mod_videoembed/CHANGELOG.md)
- Upgrade notes: [`workspace/mod_videoembed/UPGRADING.md`](workspace/mod_videoembed/UPGRADING.md)

---

## Español

Módulo de actividad de Moodle que incrusta un video por actividad desde Google Drive, YouTube,
Vimeo o Bunny Stream. El docente pega una URL y Moodle la muestra como un iframe responsivo 16:9
(con título accesible), seguido de una descripción opcional.

- Proveedores: Google Drive, YouTube, Vimeo y Bunny Stream.
- Entrada Bunny recomendada: `https://player.mediadelivery.net/play/{library}/{video}`
  (se convierte automáticamente a `.../embed/...`).

### Instalación
1. Copiar `workspace/mod_videoembed` a `mod/videoembed`.
2. Ejecutar:
   ```bash
   php admin/cli/upgrade.php --non-interactive
   php admin/cli/purge_caches.php
   ```
3. Verificar en *Administración del sitio → Plugins → Módulos de actividad*.

---

## Repository layout
- `workspace/mod_videoembed/` — plugin source (this is what ships).
- `.github/workflows/` — moodle-plugin-ci.
- `scripts/` — packaging/verification helpers.
- `docs/` — internal development notes.
- `build/` — generated ZIP artifacts (not plugin source).

## License
This plugin is free software, licensed under the **GNU GPL v3 or later**. See [LICENSE](LICENSE).
Portions derive from Moodle core (© Moodle/Petr Skoda and others); original copyright notices are
retained in each file.
