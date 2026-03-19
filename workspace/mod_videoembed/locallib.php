<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Private Video Embed module utility functions
 *
 * @package mod_videoembed
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/videoembed/lib.php");


/**
 * File browsing support class
 */
class videoembed_content_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}

function videoembed_get_editor_options($context) {
    global $CFG;
    return array('subdirs'=>1, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>-1, 'changeformat'=>1, 'context'=>$context, 'noclean'=>1, 'trusttext'=>0);
}

/**
 * Normalise host names for URL parsing.
 *
 * @param string $host
 * @return string
 */
function videoembed_normalize_host(string $host): string {
    $host = strtolower(trim($host));
    return preg_replace('/^www\./', '', $host);
}

/**
 * Convert a YouTube time expression into seconds.
 *
 * Supported examples: 120, 1m30s, 2h, 1h2m3s.
 *
 * @param string $value
 * @return int
 */
function videoembed_parse_time_to_seconds(string $value): int {
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    if (ctype_digit($value)) {
        return (int)$value;
    }
    if (preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/', $value, $matches)) {
        $hours = isset($matches[1]) && $matches[1] !== '' ? (int)$matches[1] : 0;
        $minutes = isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] : 0;
        $seconds = isset($matches[3]) && $matches[3] !== '' ? (int)$matches[3] : 0;
        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }
    return 0;
}

/**
 * Normalise a bool-like URL parameter value into 'true' or 'false'.
 *
 * @param string $value
 * @return string|null Null when the value is not a recognised bool-like value.
 */
function videoembed_normalize_bool_query_value(string $value): ?string {
    $value = strtolower(trim($value));
    if ($value === '') {
        return null;
    }

    if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
        return 'true';
    }
    if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
        return 'false';
    }

    return null;
}

/**
 * Build a canonical Bunny Stream embed URL.
 *
 * Converts play/embed source URLs into the canonical player embed format and applies
 * safe defaults for performance and responsive behaviour.
 *
 * @param string $libraryid Bunny library ID.
 * @param string $videoid Bunny video ID.
 * @param array $queryparams Input URL query params.
 * @return string
 */
function videoembed_build_bunny_embed_url(string $libraryid, string $videoid, array $queryparams): string {
    $embedparams = [
        'autoplay' => 'false',
        'loop' => 'false',
        'muted' => 'false',
        'preload' => 'true',
        'responsive' => 'true',
    ];

    $boolkeys = ['autoplay', 'loop', 'muted', 'preload', 'responsive'];
    foreach ($boolkeys as $key) {
        if (!isset($queryparams[$key])) {
            continue;
        }
        $value = is_array($queryparams[$key]) ? '' : (string)$queryparams[$key];
        $normalised = videoembed_normalize_bool_query_value($value);
        if ($normalised !== null) {
            $embedparams[$key] = $normalised;
        }
    }

    // Preserve extra Bunny params (for example token/expires) for protected streams.
    foreach ($queryparams as $key => $value) {
        if (in_array($key, $boolkeys, true)) {
            continue;
        }
        if (is_array($value)) {
            continue;
        }
        $key = trim((string)$key);
        $value = trim((string)$value);
        if ($key === '' || $value === '') {
            continue;
        }
        $embedparams[$key] = $value;
    }

    $embedurl = "https://player.mediadelivery.net/embed/{$libraryid}/{$videoid}";
    $embedurl .= '?' . http_build_query($embedparams, '', '&', PHP_QUERY_RFC3986);
    return $embedurl;
}

/**
 * Parse and normalise supported video URLs.
 *
 * @param string|null $videourl
 * @return array|null Null when empty URL.
 */
function videoembed_parse_video_url(?string $videourl): ?array {
    $videourl = trim((string)$videourl);
    if ($videourl === '') {
        return null;
    }

    $parts = @parse_url($videourl);
    if ($parts === false || empty($parts['host'])) {
        return ['valid' => false, 'reason' => 'invalid'];
    }

    $host = videoembed_normalize_host($parts['host']);
    $path = $parts['path'] ?? '';
    $queryparams = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $queryparams);
    }

    if ($host === 'drive.google.com') {
        $fileid = '';
        if (preg_match('~^/file/d/([a-zA-Z0-9_-]+)(?:/|$)~', $path, $matches)) {
            $fileid = $matches[1];
        } else if ($path === '/open' && !empty($queryparams['id'])) {
            $fileid = (string)$queryparams['id'];
        }

        if ($fileid === '' || !preg_match('~^[a-zA-Z0-9_-]+$~', $fileid)) {
            return ['valid' => false, 'reason' => 'invalid'];
        }

        $embedparams = [];
        if (!empty($queryparams['resourcekey'])) {
            $embedparams['resourcekey'] = (string)$queryparams['resourcekey'];
        }

        $embedurl = "https://drive.google.com/file/d/{$fileid}/preview";
        if (!empty($embedparams)) {
            $embedurl .= '?' . http_build_query($embedparams, '', '&', PHP_QUERY_RFC3986);
        }

        return [
            'valid' => true,
            'provider' => 'drive',
            'videoid' => $fileid,
            'embedurl' => $embedurl,
            'rawurl' => $videourl,
        ];
    }

    if ($host === 'vimeo.com' || $host === 'player.vimeo.com') {
        $videoid = '';
        if ($host === 'player.vimeo.com' && preg_match('~^/video/(\d+)(?:$|/)~', $path, $matches)) {
            $videoid = $matches[1];
        } else {
            if (preg_match_all('~(\d+)~', $path, $allmatches) && !empty($allmatches[1])) {
                $videoid = end($allmatches[1]);
            }
        }

        if ($videoid === '' || !preg_match('~^\d+$~', $videoid)) {
            return ['valid' => false, 'reason' => 'invalid'];
        }

        $embedparams = [];
        $allowedparams = ['h', 'autoplay', 'loop', 'muted', 'title', 'byline', 'portrait'];
        foreach ($allowedparams as $paramname) {
            if (isset($queryparams[$paramname]) && $queryparams[$paramname] !== '') {
                $embedparams[$paramname] = (string)$queryparams[$paramname];
            }
        }

        $embedurl = "https://player.vimeo.com/video/{$videoid}";
        if (!empty($embedparams)) {
            $embedurl .= '?' . http_build_query($embedparams, '', '&', PHP_QUERY_RFC3986);
        }

        return [
            'valid' => true,
            'provider' => 'vimeo',
            'videoid' => $videoid,
            'embedurl' => $embedurl,
            'rawurl' => $videourl,
        ];
    }

    if (
        $host === 'player.mediadelivery.net' ||
        $host === 'iframe.mediadelivery.net' ||
        $host === 'video.bunnycdn.com' ||
        $host === 'video.bunny.net'
    ) {
        $libraryid = '';
        $videoid = '';

        if (preg_match('~^/embed/([^/]+)/([^/?#]+)~', $path, $matches)) {
            $libraryid = $matches[1];
            $videoid = $matches[2];
        } else if (preg_match('~^/play/([^/]+)/([^/?#]+)~', $path, $matches)) {
            $libraryid = $matches[1];
            $videoid = $matches[2];
        }

        if ($libraryid === '' || $videoid === '') {
            return ['valid' => false, 'reason' => 'invalid'];
        }
        if (!preg_match('~^[a-zA-Z0-9_-]+$~', $libraryid) || !preg_match('~^[a-zA-Z0-9_-]+$~', $videoid)) {
            return ['valid' => false, 'reason' => 'invalid'];
        }

        $embedurl = videoembed_build_bunny_embed_url($libraryid, $videoid, $queryparams);

        return [
            'valid' => true,
            'provider' => 'bunny',
            'videoid' => "{$libraryid}/{$videoid}",
            'embedurl' => $embedurl,
            'rawurl' => $videourl,
        ];
    }

    if ($host === 'youtu.be' || preg_match('/(^|\.)youtube\.com$/', $host)) {
        $videoid = '';

        if ($host === 'youtu.be') {
            $segments = explode('/', trim($path, '/'));
            $videoid = $segments[0] ?? '';
        } else if ($path === '/watch' && !empty($queryparams['v'])) {
            $videoid = (string)$queryparams['v'];
        } else if (preg_match('~^/(embed|shorts)/([a-zA-Z0-9_-]+)~', $path, $matches)) {
            $videoid = $matches[2];
        }

        if ($videoid === '' || !preg_match('~^[a-zA-Z0-9_-]{6,}$~', $videoid)) {
            return ['valid' => false, 'reason' => 'invalid'];
        }

        $embedparams = [];
        $startseconds = 0;
        if (!empty($queryparams['start'])) {
            $startseconds = (int)$queryparams['start'];
        } else if (!empty($queryparams['t'])) {
            $startseconds = videoembed_parse_time_to_seconds((string)$queryparams['t']);
        }
        if ($startseconds > 0) {
            $embedparams['start'] = $startseconds;
        }

        $embedurl = "https://www.youtube.com/embed/{$videoid}";
        if (!empty($embedparams)) {
            $embedurl .= '?' . http_build_query($embedparams, '', '&', PHP_QUERY_RFC3986);
        }

        return [
            'valid' => true,
            'provider' => 'youtube',
            'videoid' => $videoid,
            'embedurl' => $embedurl,
            'rawurl' => $videourl,
        ];
    }

    return ['valid' => false, 'reason' => 'unsupported'];
}

/**
 * Best-effort probe for Google Drive permission restrictions.
 *
 * @param string $embedurl Drive embed URL.
 * @return string|null Diagnostic string, null when no restriction signature was found.
 */
function videoembed_get_drive_restriction_diagnostic(string $embedurl): ?string {
    $curl = new curl();
    $curl->setopt([
        'CURLOPT_FOLLOWLOCATION' => true,
        'CURLOPT_MAXREDIRS' => 4,
        'CURLOPT_TIMEOUT' => 6,
        'CURLOPT_CONNECTTIMEOUT' => 3,
        'CURLOPT_USERAGENT' => 'Moodle-mod_videoembed',
    ]);

    $response = $curl->get($embedurl);
    if ($response === false) {
        return null;
    }

    $info = $curl->get_info();
    $httpcode = (int)($info['http_code'] ?? 0);
    $finalurl = strtolower((string)($info['url'] ?? ''));
    $body = strtolower((string)$response);

    if ($httpcode === 401 || $httpcode === 403) {
        return get_string('videodriverestricted', 'videoembed');
    }
    if (strpos($finalurl, 'accounts.google.com') !== false || strpos($finalurl, 'servicelogin') !== false) {
        return get_string('videodriverestricted', 'videoembed');
    }

    $signatures = [
        'you need access',
        'request access',
        'access denied',
        'cannot be played',
        'unable to play this video',
    ];
    foreach ($signatures as $signature) {
        if (strpos($body, $signature) !== false) {
            return get_string('videodriverestricted', 'videoembed');
        }
    }

    return null;
}
