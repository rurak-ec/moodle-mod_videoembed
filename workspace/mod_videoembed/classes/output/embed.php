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

namespace mod_videoembed\output;

use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Prepares the responsive video iframe (and YouTube error handling) for templating.
 *
 * @package    mod_videoembed
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class embed implements renderable, templatable {
    /** @var array|null Parsed video data from videoembed_parse_video_url(). */
    protected $parsed;

    /** @var int Course module id, used to build unique DOM ids. */
    protected $cmid;

    /** @var string Activity name, used as the iframe accessible title. */
    protected $activityname;

    /** @var string Absolute wwwroot, used for the YouTube origin param. */
    protected $wwwroot;

    /**
     * Constructor.
     *
     * @param array|null $parsed Result of videoembed_parse_video_url(); null when no URL is set.
     * @param int $cmid Course module id (for unique DOM ids).
     * @param string $activityname Activity name, used as the iframe title.
     * @param string $wwwroot Site wwwroot for the YouTube origin param.
     */
    public function __construct(?array $parsed, int $cmid, string $activityname, string $wwwroot) {
        $this->parsed = $parsed;
        $this->cmid = $cmid;
        $this->activityname = $activityname;
        $this->wwwroot = $wwwroot;
    }

    /**
     * Whether the YouTube provider is in use (player/warning handling required).
     *
     * @return bool
     */
    public function is_youtube(): bool {
        return is_array($this->parsed)
            && !empty($this->parsed['valid'])
            && ($this->parsed['provider'] ?? '') === 'youtube';
    }

    /**
     * The DOM id of the YouTube iframe (empty unless YouTube).
     *
     * @return string
     */
    public function get_player_id(): string {
        return $this->is_youtube() ? 'videoembed-yt-player-' . $this->cmid : '';
    }

    /**
     * The DOM id of the YouTube warning box (empty unless YouTube).
     *
     * @return string
     */
    public function get_warning_id(): string {
        return $this->is_youtube() ? 'videoembed-yt-warning-' . $this->cmid : '';
    }

    /**
     * The localised YouTube error code to message map (empty unless YouTube).
     *
     * @return array
     */
    public function get_error_map(): array {
        if (!$this->is_youtube()) {
            return [];
        }
        return [
            '100' => get_string('videoyterror100', 'videoembed'),
            '101' => get_string('videoyterror101', 'videoembed'),
            '150' => get_string('videoyterror101', 'videoembed'),
            '153' => get_string('videoyterror153', 'videoembed'),
        ];
    }

    /**
     * The localised generic YouTube error message.
     *
     * @return string
     */
    public function get_generic_message(): string {
        return get_string('videoyterrorgeneric', 'videoembed');
    }

    /**
     * Export a flat context for templates/embed.mustache.
     *
     * @param renderer_base $output The renderer requesting the export.
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $context = [
            'hasvideo' => false,
            'invalidwarning' => '',
            'embedurl' => '',
            'title' => get_string('iframetitle', 'videoembed', format_string($this->activityname)),
            'allow' => '',
            'allowfullscreen' => true,
            'referrerpolicy' => '',
            'loading' => 'lazy',
            'frameid' => '',
            'isbunny' => false,
            'warningid' => '',
            'drivewarning' => '',
        ];

        if ($this->parsed === null) {
            // No URL configured: render nothing.
            return $context;
        }

        if (empty($this->parsed['valid'])) {
            $context['invalidwarning'] = get_string('invalidvideourlview', 'videoembed');
            return $context;
        }

        $context['hasvideo'] = true;
        $provider = $this->parsed['provider'];
        $embedurl = $this->parsed['embedurl'];
        $context['allow'] = 'autoplay; fullscreen; picture-in-picture';

        if ($provider === 'youtube') {
            $url = new moodle_url($embedurl);
            $url->param('enablejsapi', '1');
            $url->param('origin', $this->wwwroot);
            $embedurl = $url->out(false);
            $context['allow'] = 'accelerometer; autoplay; clipboard-write; encrypted-media; '
                . 'gyroscope; picture-in-picture; web-share';
            $context['referrerpolicy'] = 'strict-origin-when-cross-origin';
            $context['frameid'] = $this->get_player_id();
            $context['warningid'] = $this->get_warning_id();
        } else if ($provider === 'vimeo') {
            $context['allow'] = 'autoplay; fullscreen; picture-in-picture; encrypted-media';
        } else if ($provider === 'bunny') {
            $context['isbunny'] = true;
            $context['allow'] = 'accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture';
        } else if ($provider === 'drive') {
            // Only probe Google Drive permissions if user can manage the activity.
            // Avoids blocking outbound HTTP requests on student views.
            $modcontext = \context_module::instance($this->cmid, IGNORE_MISSING);
            if ($modcontext && has_capability('moodle/course:manageactivities', $modcontext)) {
                $context['drivewarning'] = videoembed_get_drive_restriction_diagnostic($embedurl) ?? '';
            }
        }

        $context['embedurl'] = $embedurl;
        return $context;
    }
}
