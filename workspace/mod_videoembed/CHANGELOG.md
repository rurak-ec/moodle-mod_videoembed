# Changelog

All notable changes to **mod_videoembed** (Video) are documented here.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.8] - 2026-10-11

`$plugin->version` unchanged (`2026100801`): no database upgrade needed. This release also stops installing the
plugin through a bind mount of the workspace in `compose.yaml`: the web and cron containers both use the package.

### Changed
- **Activity icon in the same style as mod_pdfquiz and the Space 5 tiles**: a soft red tile (`#dc5750`, `#b8453f` in
  dark mode, with the theme's relief in the "gradient 3D" tile style) with the drawing in white. `pix/monologo.svg` is
  again a single-ink 24×24 outline (`stroke="currentColor"`, 1.5): a rounded screen, a player bar at 60% opacity and a
  play triangle. It replaces the red gradient and the full-colour icon of 2026-10-10 (a red rectangle with a white
  triangle), which the inverting filter turned into a cyan rectangle. The tile is found with the same selectors as
  mod_pdfquiz (`icon-videoembed`, `modicon_videoembed`, the image in the activity overview and the chooser option).

### Added
- **CLI** `cli/videoembed.php` (`classes/local/cli.php`) to manage Video activities from the console; run it as
  `www-data`, never as root. It was already in the 2026-10-10 package without a changelog entry.

## [1.2.7] - 2026-10-08

### Fixed
- **Activity icon (`pix/monologo.svg`)**: the previous icon was a full-colour 50×50 tile with an opaque
  background. Since Moodle 4.0 a `monologo` is recoloured with an SVG filter that turns every opaque pixel
  into the activity-purpose colour, so the icon rendered as a solid square. It is now a single-ink
  24×24 outline (video frame with a play triangle, `stroke="currentColor"`) matching the core and Space 5
  activity icons. Image-only change: no database upgrade needed.

## [1.2.6] - 2026-10-08

### Added
- Support for **Moodle 5.3 (LTS)** (`$plugin->supported = [405, 503]`).
- CI matrix covering `MOODLE_503_STABLE` on PHP 8.3 (pgsql) and PHP 8.4 (mariadb).
- Native lazy loading (`loading="lazy"`) across all video providers (YouTube, Vimeo, Bunny Stream, Google Drive).
- CSS containment and isolation: `contain: strict;` on iframe and `contain: layout style; content-visibility: auto;` on responsive container.

### Changed & Optimized
- **Zero-impact Page Rendering**:
  - Non-blocking YouTube player: moved external `iframe_api` script loading from `<head>` to the page footer.
  - Eliminated blocking server-side HTTP probes: Google Drive restriction diagnostics now only run for activity managers with in-memory memoization and low timeouts (2s max), avoiding outbound network delays for student views.

## [1.2.4] - 2026-06-21

### Changed
- **Declared support for Moodle 4.5 LTS** (`$plugin->supported = [405, 502]`, `requires = 2024100700`).
  Every core API the plugin uses (`\core\attribute\deprecated`, `\core\deprecation`, the activity
  events, `\core_calendar`/`\core_completion`/`\core_search`/`\core_external` callbacks) was verified to
  exist in `MOODLE_405_STABLE`. CI now also runs the `MOODLE_405_STABLE` branch on PHP 8.1 and 8.3.

## [1.2.3] - 2026-06-20

### Fixed
- **Include the AMD build** (`amd/build/player.min.js` + source map): the repo `.gitignore`
  matched `build/` unanchored, which excluded the plugin's `amd/build/`, so the YouTube player
  module was missing from the package. The pattern is now anchored to the repo root (`/build/`),
  and the build is generated with grunt (rollup).
- **Test generator** now sets a default `videourl`, so `videoembed_add_instance()` no longer
  rejects generator-created instances (fixes the PHPUnit suite).
- **Behat features** rewritten to exercise the `videoembed` module (they were copied from
  `mod_page`); feature names are now unique (gherkinlint).
- **PHPDoc**: completed parameter lists (`videoembed_reset_userdata`, `videoembed_export_contents`,
  `mod_videoembed_core_calendar_provide_event_action`) and switched `{@link Class::method}` inline
  tags to `{@see ...}`.

## [1.2.2] - 2026-06-20

### Changed
- **Ship English only**, per the moodle.org plugins-directory policy ("just the English strings
  should ship with the plugin"). The Spanish translation is retained outside the plugin under
  [`/translations`](../../translations/) and will be contributed to lang.moodle.org (AMOS) after
  approval, so Spanish reaches all sites via the normal language-pack mechanism.
- The plugin README is now bilingual (English / Spanish).

## [1.2.1] - 2026-06-20

### Changed
- Declared support for **Moodle 5.2** (`$plugin->supported = [500, 502]`); the plugin now targets
  Moodle 5.0–5.2.
- CI now tests Moodle 5.0/5.1/5.2 across PHP 8.2–8.4 (Moodle 5.2 on PHP 8.3+, since 5.2 dropped PHP
  8.2) with PostgreSQL 16 and MariaDB 10.11.

## [1.2.0] - 2026-06-20

### Fixed
- **Backup/restore data loss:** the `videourl` field was not included in the activity
  backup structure, so the embedded video URL was lost on backup/restore. It is now
  backed up and restored correctly.

### Changed
- **Rendering refactored to the standard Moodle output architecture.** The view now uses
  a renderable/templatable class (`\mod_videoembed\output\embed`), a Mustache template
  (`templates/embed.mustache`), an AMD module (`mod_videoembed/player`) for the YouTube
  error handling, and a namespaced `styles.css`. Inline HTML, inline styles and the inline
  JavaScript block were removed from `view.php`.
- `version.php`: declared `$plugin->supported = [500, 501]` (Moodle 5.0–5.1), lowered
  `$plugin->requires` to Moodle 5.0.0, and removed the deprecated `$plugin->cron` property.

### Added
- Accessible `title` attribute on the video iframe (new `iframetitle` string, EN + ES).
- PHPUnit tests for the video URL parser, covering every provider plus malicious and
  malformed inputs (`tests/locallib_test.php`).
- GitHub Actions CI using `moodlehq/moodle-plugin-ci` (PHP 8.2–8.4 × Moodle 5.0/5.1,
  PostgreSQL and MariaDB).
- Top-level `LICENSE` (GNU GPL v3) and this changelog.

## [1.1.0] - 2026-03-19

### Added
- Multi-provider embedding: Google Drive, YouTube, Vimeo and Bunny Stream, with strict
  per-provider URL parsing and canonical embed-URL normalisation.
- Bunny Stream canonical input support
  (`https://player.mediadelivery.net/play/{library}/{video}`) with default embed flags.
- YouTube IFrame API error reporting and a Google Drive sharing-restriction diagnostic.

### Deprecated
- `\mod_videoembed\event\course_module_instance_list_viewed`
  (see [MDL-84632](https://tracker.moodle.org/browse/MDL-84632)).

## [1.0.0] - 2026-03-18

### Added
- Initial release of the Video activity module: embed a single video URL per activity,
  shown above the optional description.

[1.2.3]: https://github.com/rurak-ec/moodle-mod_videoembed/releases/tag/v1.2.3
[1.2.2]: https://github.com/rurak-ec/moodle-mod_videoembed/releases/tag/v1.2.2
[1.2.1]: https://github.com/rurak-ec/moodle-mod_videoembed/releases/tag/v1.2.1
[1.2.0]: https://github.com/rurak-ec/moodle-mod_videoembed/releases/tag/v1.2.0
[1.1.0]: https://github.com/rurak-ec/moodle-mod_videoembed/releases/tag/v1.1.0
[1.0.0]: https://github.com/rurak-ec/moodle-mod_videoembed/releases/tag/v1.0.0
