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
 * Page module admin settings and defaults
 *
 * @package mod_videogdrive
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_OPEN, RESOURCELIB_DISPLAY_POPUP));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_OPEN);

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configmultiselect('videogdrive/displayoptions',
        get_string('displayoptions', 'videogdrive'), get_string('configdisplayoptions', 'videogdrive'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('pagemodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('videogdrive/printintro',
        get_string('printintro', 'videogdrive'), get_string('printintroexplain', 'videogdrive'), 0));
    $settings->add(new admin_setting_configcheckbox('videogdrive/printlastmodified',
        get_string('printlastmodified', 'videogdrive'), get_string('printlastmodifiedexplain', 'videogdrive'), 1));
    $settings->add(new admin_setting_configselect('videogdrive/display',
        get_string('displayselect', 'videogdrive'), get_string('displayselectexplain', 'videogdrive'), RESOURCELIB_DISPLAY_OPEN, $displayoptions));
    $settings->add(new admin_setting_configtext('videogdrive/popupwidth',
        get_string('popupwidth', 'videogdrive'), get_string('popupwidthexplain', 'videogdrive'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('videogdrive/popupheight',
        get_string('popupheight', 'videogdrive'), get_string('popupheightexplain', 'videogdrive'), 450, PARAM_INT, 7));
}
