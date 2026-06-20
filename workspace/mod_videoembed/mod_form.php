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
 * Video Embed configuration form
 *
 * @package mod_videoembed
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/videoembed/locallib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Settings form for the videoembed activity module.
 *
 * @package    mod_videoembed
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_videoembed_mod_form extends moodleform_mod {
    /**
     * Define the activity settings form fields.
     *
     * @return void
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $config = get_config('videoembed');

        // -------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => '48']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');
        $this->standard_intro_elements();

        // -------------------------------------------------------
        $mform->addElement('header', 'contentsection', get_string('contentheader', 'videoembed'));
        $mform->addElement('text', 'videourl', get_string('videourl', 'videoembed'), ['size' => '80']);
        $mform->setType('videourl', PARAM_URL);
        $mform->addHelpButton('videourl', 'videourl', 'videoembed');
        $mform->addRule('videourl', get_string('videourlrequired', 'videoembed'), 'required', null, 'client');
        $mform->addElement(
            'editor',
            'videoembed',
            get_string('content', 'videoembed'),
            null,
            videoembed_get_editor_options($this->context)
        );

        // -------------------------------------------------------
        $mform->addElement('header', 'appearancehdr', get_string('appearance'));

        if ($this->current->instance) {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions), $this->current->display);
        } else {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions));
        }
        if (count($options) == 1) {
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else {
            $mform->addElement('select', 'display', get_string('displayselect', 'videoembed'), $options);
            $mform->setDefault('display', $config->display);
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'videoembed'), ['size' => 3]);
            if (count($options) > 1) {
                $mform->hideIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'videoembed'), ['size' => 3]);
            if (count($options) > 1) {
                $mform->hideIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
        }

        $mform->addElement('advcheckbox', 'printintro', get_string('printintro', 'videoembed'));
        $mform->setDefault('printintro', $config->printintro);
        $mform->addElement('advcheckbox', 'printlastmodified', get_string('printlastmodified', 'videoembed'));
        $mform->setDefault('printlastmodified', $config->printlastmodified);

        $this->standard_coursemodule_elements();

        // -------------------------------------------------------
        $this->add_action_buttons();

        // -------------------------------------------------------
        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
    }

    /**
     * Enforce defaults here.
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('videoembed');
            $defaultvalues['videoembed']['format'] = $defaultvalues['contentformat'];
            $defaultvalues['videoembed']['text']   = file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'mod_videoembed',
                'content',
                0,
                videoembed_get_editor_options($this->context),
                $defaultvalues['content']
            );
            $defaultvalues['videoembed']['itemid'] = $draftitemid;
        }
        if (!empty($defaultvalues['displayoptions'])) {
            $displayoptions = (array) unserialize_array($defaultvalues['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $defaultvalues['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printlastmodified'])) {
                $defaultvalues['printlastmodified'] = $displayoptions['printlastmodified'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $defaultvalues['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $defaultvalues['popupheight'] = $displayoptions['popupheight'];
            }
        }
    }

    /**
     * Validate module form values.
     *
     * @param array $data submitted data
     * @param array $files submitted files
     * @return array list of errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $videourl = trim((string)($data['videourl'] ?? ''));
        if ($videourl === '') {
            $errors['videourl'] = get_string('videourlrequired', 'videoembed');
            return $errors;
        }

        $parsedvideo = videoembed_parse_video_url($videourl);
        if (empty($parsedvideo) || empty($parsedvideo['valid'])) {
            $errors['videourl'] = get_string('invalidvideourl', 'videoembed');
        }

        return $errors;
    }
}
