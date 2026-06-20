This file is part of Moodle - http://moodle.org/

Moodle is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Moodle is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

copyright 2009 Petr Skoda (http://skodak.org)
license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later


mod_videoembed (Video)
======================

Overview
--------
mod_videoembed is a Moodle activity module for embedding one video URL per activity.
The video is always rendered first and the optional "Video description" is shown below it.

Supported providers
-------------------
- Google Drive:
  - Input: https://drive.google.com/file/d/FILE_ID/view
  - Embed: https://drive.google.com/file/d/FILE_ID/preview
- YouTube:
  - Input: watch, youtu.be, embed, shorts URLs
  - Embed: https://www.youtube.com/embed/VIDEO_ID
- Vimeo:
  - Input: https://vimeo.com/VIDEO_ID
  - Embed: https://player.vimeo.com/video/VIDEO_ID
- Bunny Stream:
  - Input: https://player.mediadelivery.net/play/LIBRARY_ID/VIDEO_ID
  - Embed: https://player.mediadelivery.net/embed/LIBRARY_ID/VIDEO_ID
  - Defaults: autoplay=false, loop=false, muted=false, preload=true, responsive=true

Key behavior
------------
- "Video URL" is required (form validation and server-side validation).
- Invalid or unsupported URLs do not break rendering; users see a warning.
- Existing activities without a video URL remain viewable (backward compatibility).
- YouTube embed errors 100/101/150/153 are mapped to human-readable warnings.
- Google Drive restrictions are detected with a best-effort diagnostic message.

Privacy and data handling
-------------------------
- This plugin stores activity configuration in Moodle DB.
- It does not store personal data outside normal Moodle activity records.
- Privacy API null provider is implemented in classes/privacy/provider.php.

Security and compatibility notes
--------------------------------
- URL parsing is strict and provider-aware.
- Bunny Stream embed params are normalised and safely encoded.
- Query params are encoded with PHP_QUERY_RFC3986.
- Compatible with Moodle versions defined in version.php (`$plugin->requires`).

Installation
------------
1. Copy module code to: mod/videoembed
2. Visit Site administration -> Notifications, or run:
   php admin/cli/upgrade.php --non-interactive
3. Purge caches:
   php admin/cli/purge_caches.php

Release checklist (Moodle Plugins directory readiness)
------------------------------------------------------
- Bump $plugin->version on each release.
- Keep $plugin->release and $plugin->maturity updated.
- Keep readme.txt and UPGRADING.md up to date.
- Run PHPUnit and Behat tests where applicable.
- Validate PHP syntax with php -l on changed files.
- Ship the English language pack only (lang/en); other translations go to lang.moodle.org (AMOS)
  after approval. The Spanish translation is kept in the repository under /translations.
