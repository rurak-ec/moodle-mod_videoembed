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
 * Video Embed module view
 *
 * @package mod_videoembed
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/videoembed/lib.php');
require_once($CFG->dirroot.'/mod/videoembed/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$p       = optional_param('p', 0, PARAM_INT);  // Video Embed instance ID
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);

if ($p) {
    if (!$page = $DB->get_record('videoembed', array('id'=>$p))) {
        throw new \moodle_exception('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('videoembed', $page->id, $page->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('videoembed', $id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    $page = $DB->get_record('videoembed', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/videoembed:view', $context);

// Completion and trigger events.
videoembed_view($page, $course, $cm, $context);

$PAGE->set_url('/mod/videoembed/view.php', array('id' => $cm->id));

$options = empty($page->displayoptions) ? [] : (array) unserialize_array($page->displayoptions);
$printintro = !empty($options['printintro']);

$activityheader = ['hidecompletion' => false];
if (!$printintro) {
    $activityheader['description'] = '';
}

if ($inpopup and $page->display == RESOURCELIB_DISPLAY_POPUP) {
    $PAGE->set_pagelayout('popup');
    $PAGE->set_title($course->shortname.': '.$page->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($page);

    // Manually populate module intro, which would otherwise not be shown in the popup.
    if ($printintro) {
        $activityheader['description'] = format_module_intro('videoembed', $page, $cm->id);
    }
} else {
    $PAGE->add_body_class('limitedwidth');
    $PAGE->set_title($course->shortname.': '.$page->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($page);
    if (!$PAGE->activityheader->is_title_allowed()) {
        $activityheader['title'] = "";
    }
}
$PAGE->activityheader->set_attrs($activityheader);

$parsedvideo = videoembed_parse_video_url($page->videourl ?? '');
$videoembedhtml = '';
$videoserverwarning = '';
$youtubeplayerid = '';
$youtubewarningid = '';
if ($parsedvideo !== null) {
    if (empty($parsedvideo['valid'])) {
        $videoserverwarning = get_string('invalidvideourlview', 'videoembed');
    } else {
        $embedurl = $parsedvideo['embedurl'];
        $allow = 'autoplay; fullscreen; picture-in-picture';
        $iframeattrs = [
            'style' => 'position: absolute; top: 0; left: 0; width: 100%; height: 100%;',
            'src' => $embedurl,
            'frameborder' => '0',
            'allow' => $allow,
            'allowfullscreen' => 'allowfullscreen',
        ];

        if ($parsedvideo['provider'] === 'youtube') {
            $embedmoodleurl = new moodle_url($embedurl);
            $embedmoodleurl->param('enablejsapi', '1');
            $embedmoodleurl->param('origin', $CFG->wwwroot);
            $iframeattrs['src'] = $embedmoodleurl->out(false);
            $iframeattrs['allow'] = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
            $iframeattrs['referrerpolicy'] = 'strict-origin-when-cross-origin';
            $youtubeplayerid = 'videoembed-yt-player-' . $cm->id;
            $youtubewarningid = 'videoembed-yt-warning-' . $cm->id;
            $iframeattrs['id'] = $youtubeplayerid;
        } else if ($parsedvideo['provider'] === 'vimeo') {
            $iframeattrs['allow'] = 'autoplay; fullscreen; picture-in-picture; encrypted-media';
        } else if ($parsedvideo['provider'] === 'bunny') {
            $iframeattrs['style'] = 'border:0;position:absolute;top:0;height:100%;width:100%;';
            $iframeattrs['allow'] = 'accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;';
            $iframeattrs['allowfullscreen'] = 'true';
            $iframeattrs['loading'] = 'lazy';
        } else if ($parsedvideo['provider'] === 'drive') {
            $videoserverwarning = videoembed_get_drive_restriction_diagnostic($embedurl) ?? '';
        }

        $iframe = html_writer::tag('iframe', '', $iframeattrs);
        $videoembedhtml = html_writer::tag('div', $iframe, ['style' => 'position:relative;padding-top:56.25%;']);
    }
}

if (!empty($youtubeplayerid) && !empty($youtubewarningid)) {
    $PAGE->requires->js(new moodle_url('https://www.youtube.com/iframe_api'), true);

    $yterrormap = [
        '100' => get_string('videoyterror100', 'videoembed'),
        '101' => get_string('videoyterror101', 'videoembed'),
        '150' => get_string('videoyterror101', 'videoembed'),
        '153' => get_string('videoyterror153', 'videoembed'),
    ];

    $js = <<<JS
(function() {
    var playerid = %s;
    var warningid = %s;
    var errormap = %s;
    var genericmessage = %s;
    var playerinstance = null;

    function showWarning(code) {
        var node = document.getElementById(warningid);
        if (!node) {
            return;
        }
        var key = String(code);
        node.textContent = errormap[key] || genericmessage;
        node.style.display = 'block';
    }

    function initPlayer() {
        if (playerinstance || !window.YT || !YT.Player) {
            return;
        }
        var iframe = document.getElementById(playerid);
        if (!iframe) {
            return;
        }
        playerinstance = new YT.Player(playerid, {
            events: {
                'onError': function(event) {
                    showWarning(event.data);
                }
            }
        });
    }

    var previousready = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = function() {
        if (typeof previousready === 'function') {
            previousready();
        }
        initPlayer();
    };

    if (window.YT && YT.Player) {
        initPlayer();
    }
})();
JS;
    $PAGE->requires->js_init_code(sprintf(
        $js,
        json_encode($youtubeplayerid),
        json_encode($youtubewarningid),
        json_encode($yterrormap),
        json_encode(get_string('videoyterrorgeneric', 'videoembed'))
    ));
}

echo $OUTPUT->header();

if ($videoembedhtml !== '') {
    echo $OUTPUT->box($videoembedhtml, 'generalbox center clearfix videoembed-video-embed');
}

if ($videoserverwarning !== '') {
    echo $OUTPUT->notification($videoserverwarning, 'warning');
}

if (!empty($youtubewarningid)) {
    echo html_writer::div('', 'alert alert-warning', [
        'id' => $youtubewarningid,
        'style' => 'display:none;',
    ]);
}

$rawcontent = trim((string)($page->content ?? ''));
if ($rawcontent !== '') {
    $content = file_rewrite_pluginfile_urls($page->content, 'pluginfile.php', $context->id, 'mod_videoembed', 'content', $page->revision);
    $formatoptions = new stdClass;
    $formatoptions->noclean = true;
    $formatoptions->overflowdiv = true;
    $formatoptions->context = $context;
    $content = format_text($content, $page->contentformat, $formatoptions);
    echo $OUTPUT->box($content, "generalbox center clearfix");
}

if (!isset($options['printlastmodified']) || !empty($options['printlastmodified'])) {
    $strlastmodified = get_string("lastmodified");
    echo html_writer::div("$strlastmodified: " . userdate($page->timemodified), 'modified');
}

echo $OUTPUT->footer();
