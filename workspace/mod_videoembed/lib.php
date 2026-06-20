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
 * Library of interface functions and constants for the videoembed module.
 *
 * @package    mod_videoembed
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;

/**
 * List of features supported in the Video Embed module.
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function videoembed_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_ARCHETYPE => MOD_ARCHETYPE_RESOURCE,
        FEATURE_GROUPS => false,
        FEATURE_GROUPINGS => false,
        FEATURE_MOD_INTRO => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_GRADE_HAS_GRADE => false,
        FEATURE_GRADE_OUTCOMES => false,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_CONTENT,
        default => null,
    };
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function videoembed_reset_userdata($data) {

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.

    return [];
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function videoembed_get_view_actions() {
    return ['view', 'view all'];
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function videoembed_get_post_actions() {
    return ['update', 'add'];
}

/**
 * Add a Video Embed activity instance.
 * @param stdClass $data
 * @param mod_videoembed_mod_form $mform
 * @return int New activity instance id
 */
function videoembed_add_instance($data, $mform = null) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");
    require_once("$CFG->dirroot/mod/videoembed/locallib.php");

    $cmid = $data->coursemodule;
    $editorfield = isset($data->videoembed) && is_array($data->videoembed) ? $data->videoembed : null;

    $data->timemodified = time();
    $data->videourl = trim((string)($data->videourl ?? ''));
    if ($data->videourl === '') {
        throw new moodle_exception('videourlrequired', 'videoembed');
    }
    $parsedvideo = videoembed_parse_video_url($data->videourl);
    if (empty($parsedvideo['valid'])) {
        throw new moodle_exception('invalidvideourl', 'videoembed');
    }
    $displayoptions = [];
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    $displayoptions['printintro']   = $data->printintro;
    $displayoptions['printlastmodified'] = $data->printlastmodified;
    $data->displayoptions = serialize($displayoptions);

    if ($mform && !empty($editorfield)) {
        $data->content       = $editorfield['text'];
        $data->contentformat = $editorfield['format'];
    }

    $data->id = $DB->insert_record('videoembed', $data);

    // We need to use context now, so we need to make sure all needed info is already in db.
    $DB->set_field('course_modules', 'instance', $data->id, ['id' => $cmid]);
    $context = context_module::instance($cmid);

    if ($mform && !empty($editorfield['itemid'])) {
        $draftitemid = $editorfield['itemid'];
        $data->content = file_save_draft_area_files(
            $draftitemid,
            $context->id,
            'mod_videoembed',
            'content',
            0,
            videoembed_get_editor_options($context),
            $data->content
        );
        $DB->update_record('videoembed', $data);
    }

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'videoembed', $data->id, $completiontimeexpected);

    return $data->id;
}

/**
 * Update a Video Embed activity instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function videoembed_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");
    require_once("$CFG->dirroot/mod/videoembed/locallib.php");

    $cmid        = $data->coursemodule;
    $editorfield = isset($data->videoembed) && is_array($data->videoembed) ? $data->videoembed : null;
    $draftitemid = $editorfield['itemid'] ?? 0;

    $data->timemodified = time();
    $data->id           = $data->instance;
    $data->revision++;
    $data->videourl = trim((string)($data->videourl ?? ''));
    if ($data->videourl === '') {
        throw new moodle_exception('videourlrequired', 'videoembed');
    }
    $parsedvideo = videoembed_parse_video_url($data->videourl);
    if (empty($parsedvideo['valid'])) {
        throw new moodle_exception('invalidvideourl', 'videoembed');
    }

    $displayoptions = [];
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    $displayoptions['printintro']   = $data->printintro;
    $displayoptions['printlastmodified'] = $data->printlastmodified;
    $data->displayoptions = serialize($displayoptions);

    if (!empty($editorfield)) {
        $data->content       = $editorfield['text'];
        $data->contentformat = $editorfield['format'];
    }

    $DB->update_record('videoembed', $data);

    $context = context_module::instance($cmid);
    if ($draftitemid) {
        $data->content = file_save_draft_area_files(
            $draftitemid,
            $context->id,
            'mod_videoembed',
            'content',
            0,
            videoembed_get_editor_options($context),
            $data->content
        );
        $DB->update_record('videoembed', $data);
    }

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'videoembed', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Delete a Video Embed activity instance.
 * @param int $id
 * @return bool true
 */
function videoembed_delete_instance($id) {
    global $DB;

    if (!$page = $DB->get_record('videoembed', ['id' => $id])) {
        return false;
    }

    $cm = get_coursemodule_from_instance('videoembed', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'videoembed', $id, null);

    // Note: all context files are deleted automatically.

    $DB->delete_records('videoembed', ['id' => $page->id]);

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link course_modinfo::get_array_of_activities()}
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info Info to customise the main activity display
 */
function videoembed_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if (
        !$page = $DB->get_record(
            'videoembed',
            ['id' => $coursemodule->instance],
            'id, name, display, displayoptions, intro, introformat'
        )
    ) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $page->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('videoembed', $page, $coursemodule->id, false);
    }

    if ($page->display != RESOURCELIB_DISPLAY_POPUP) {
        return $info;
    }

    $fullurl = "$CFG->wwwroot/mod/videoembed/view.php?id=$coursemodule->id&amp;inpopup=1";
    $options = empty($page->displayoptions) ? [] : (array) unserialize_array($page->displayoptions);
    $width  = empty($options['popupwidth']) ? 620 : $options['popupwidth'];
    $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
    $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,"
        . "directories=no,scrollbars=yes,resizable=yes";
    $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";

    return $info;
}


/**
 * Lists all browsable file areas
 *
 * @package  mod_videoembed
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return array
 */
function videoembed_get_file_areas($course, $cm, $context) {
    $areas = [];
    $areas['content'] = get_string('content', 'videoembed');
    return $areas;
}

/**
 * File browsing support for the Video Embed content area.
 *
 * @package  mod_videoembed
 * @category files
 * @param file_browser $browser file browser instance
 * @param stdClass $areas file areas
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param int $itemid item ID
 * @param string $filepath file path
 * @param string $filename file name
 * @return file_info instance or null if not found
 */
function videoembed_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // Students can not peek here.
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'content') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot . '/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_videoembed', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' && $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_videoembed', 'content', 0);
            } else {
                // Not found.
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/videoembed/locallib.php");
        return new videoembed_content_file_info(
            $browser,
            $context,
            $storedfile,
            $urlbase,
            $areas[$filearea],
            true,
            true,
            true,
            false
        );
    }

    // Note: videoembed_intro handled in file_browser automatically.

    return null;
}

/**
 * Serves Video Embed files.
 *
 * @package  mod_videoembed
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function videoembed_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/videoembed:view', $context)) {
        return false;
    }

    if ($filearea !== 'content') {
        // Intro is handled automatically in pluginfile.php.
        return false;
    }

    // The arg could be revision number or index.html.
    $arg = array_shift($args);
    if ($arg == 'index.html' || $arg == 'index.htm') {
        // Serve activity content.
        $filename = $arg;

        if (!$page = $DB->get_record('videoembed', ['id' => $cm->instance], '*', MUST_EXIST)) {
            return false;
        }

        // We need to rewrite the pluginfile URLs so the media filters can work.
        $content = file_rewrite_pluginfile_urls(
            $page->content,
            'webservice/pluginfile.php',
            $context->id,
            'mod_videoembed',
            'content',
            $page->revision
        );
        $formatoptions = new stdClass();
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;
        $content = format_text($content, $page->contentformat, $formatoptions);

        // Reverse the rewrite so the plugin file placeholders can be stripped from the content.
        $options = ['reverse' => true];
        $content = file_rewrite_pluginfile_urls(
            $content,
            'webservice/pluginfile.php',
            $context->id,
            'mod_videoembed',
            'content',
            $page->revision,
            $options
        );
        $content = str_replace('@@PLUGINFILE@@/', '', $content);

        send_file($content, $filename, 0, 0, true, true);
    } else {
        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_videoembed/$filearea/0/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) || $file->is_directory()) {
            $page = $DB->get_record('videoembed', ['id' => $cm->instance], 'id, legacyfiles', MUST_EXIST);
            if ($page->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
                return false;
            }
            if (
                !$file = resourcelib_try_file_migration(
                    '/' . $relativepath,
                    $cm->id,
                    $cm->course,
                    'mod_videoembed',
                    'content',
                    0
                )
            ) {
                return false;
            }
            // File migrated, update the flag.
            $page->legacyfileslast = time();
            $DB->update_record('videoembed', $page);
        }

        // Finally send the file.
        send_stored_file($file, null, 0, $forcedownload, $options);
    }
}

/**
 * Return a list of module page types.
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function videoembed_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = ['mod-videoembed-*' => get_string('videoembed-mod-videoembed-x', 'videoembed')];
    return $modulepagetype;
}

/**
 * Export activity resource contents.
 *
 * @return array of file content
 */
function videoembed_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    $contents = [];
    $context = context_module::instance($cm->id);

    $page = $DB->get_record('videoembed', ['id' => $cm->instance], '*', MUST_EXIST);

    // Activity contents.
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_videoembed', 'content', 0, 'sortorder DESC, id ASC', false);
    foreach ($files as $fileinfo) {
        $file = [];
        $file['type']         = 'file';
        $file['filename']     = $fileinfo->get_filename();
        $file['filepath']     = $fileinfo->get_filepath();
        $file['filesize']     = $fileinfo->get_filesize();
        $file['fileurl']      = url::make_webservice_pluginfile_url(
            contextid: $context->id,
            component: 'mod_videoembed',
            area: 'content',
            itemid: $page->revision,
            pathname: $fileinfo->get_filepath(),
            filename: $fileinfo->get_filename(),
            forcedownload: true
        )->out();
        $file['timecreated']  = $fileinfo->get_timecreated();
        $file['timemodified'] = $fileinfo->get_timemodified();
        $file['sortorder']    = $fileinfo->get_sortorder();
        $file['userid']       = $fileinfo->get_userid();
        $file['author']       = $fileinfo->get_author();
        $file['license']      = $fileinfo->get_license();
        $file['mimetype']     = $fileinfo->get_mimetype();
        $file['isexternalfile'] = $fileinfo->is_external_file();
        if ($file['isexternalfile']) {
            $file['repositorytype'] = $fileinfo->get_repository_type();
        }
        $contents[] = $file;
    }

    // Activity HTML content.
    $filename = 'index.html';
    $pagefile = [];
    $pagefile['type']         = 'file';
    $pagefile['filename']     = $filename;
    $pagefile['filepath']     = '/';
    $pagefile['filesize']     = 0;
    $pagefile['fileurl']      = url::make_webservice_pluginfile_url(
        contextid: $context->id,
        component: 'mod_videoembed',
        area: 'content',
        itemid: null,
        pathname: '/',
        filename: $filename,
        forcedownload: true
    )->out();
    $pagefile['timecreated']  = null;
    $pagefile['timemodified'] = $page->timemodified;
    // Mark this file as the main file.
    $pagefile['sortorder']    = 1;
    $pagefile['userid']       = null;
    $pagefile['author']       = null;
    $pagefile['license']      = null;
    $contents[] = $pagefile;

    return $contents;
}

/**
 * Register the ability to handle drag and drop file uploads.
 *
 * Video Embed intentionally does not register text/html drops because a Video URL is required.
 * @return array containing details of the files / types the mod can handle
 */
function videoembed_dndupload_register() {
    return ['types' => []];
}

/**
 * Handle a file that has been uploaded.
 *
 * @throws moodle_exception because Video URL is required and DnD text uploads are disabled.
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int Instance id of the newly created module
 */
function videoembed_dndupload_handle($uploadinfo) {
    throw new moodle_exception('videourlrequired', 'videoembed');
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $page       Video Embed activity object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function videoembed_view($page, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = [
        'context' => $context,
        'objectid' => $page->id,
    ];

    $event = \mod_videoembed\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('videoembed', $page);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function videoembed_check_updates_since(cm_info $cm, $from, $filter = []) {
    $updates = course_check_module_updates_since($cm, $from, ['content'], $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_videoembed_core_calendar_provide_event_action(
    calendar_event $event,
    \core_calendar\action_factory $factory,
    $userid = 0
) {
    global $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['videoembed'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/videoembed/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * Given an array with a file path, it returns the itemid and the filepath for the defined filearea.
 *
 * @param  string $filearea The filearea.
 * @param  array  $args The path (the part after the filearea and before the filename).
 * @return array The itemid and the filepath inside the $args path, for the defined filearea.
 */
function mod_videoembed_get_path_from_pluginfile(string $filearea, array $args): array {
    // Video Embed does not use itemid (the number represents the revision and is not stored in the database).
    array_shift($args);

    // Get the filepath.
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    return [
        'itemid' => 0,
        'filepath' => $filepath,
    ];
}
