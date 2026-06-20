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
 * Video Embed module admin settings and defaults
 *
 * @package mod_videoembed
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions([RESOURCELIB_DISPLAY_OPEN, RESOURCELIB_DISPLAY_POPUP]);
    $defaultdisplayoptions = [RESOURCELIB_DISPLAY_OPEN];

    // General settings.
    $settings->add(new admin_setting_configmultiselect(
        'videoembed/displayoptions',
        get_string('displayoptions', 'videoembed'),
        get_string('configdisplayoptions', 'videoembed'),
        $defaultdisplayoptions,
        $displayoptions
    ));

    // Mod edit defaults.
    $settings->add(new admin_setting_heading(
        'pagemodeditdefaults',
        get_string('modeditdefaults', 'admin'),
        get_string('condifmodeditdefaults', 'admin')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'videoembed/printintro',
        get_string('printintro', 'videoembed'),
        get_string('printintroexplain', 'videoembed'),
        0
    ));
    $settings->add(new admin_setting_configcheckbox(
        'videoembed/printlastmodified',
        get_string('printlastmodified', 'videoembed'),
        get_string('printlastmodifiedexplain', 'videoembed'),
        1
    ));
    $settings->add(new admin_setting_configselect(
        'videoembed/display',
        get_string('displayselect', 'videoembed'),
        get_string('displayselectexplain', 'videoembed'),
        RESOURCELIB_DISPLAY_OPEN,
        $displayoptions
    ));
    $settings->add(new admin_setting_configtext(
        'videoembed/popupwidth',
        get_string('popupwidth', 'videoembed'),
        get_string('popupwidthexplain', 'videoembed'),
        620,
        PARAM_INT,
        7
    ));
    $settings->add(new admin_setting_configtext(
        'videoembed/popupheight',
        get_string('popupheight', 'videoembed'),
        get_string('popupheightexplain', 'videoembed'),
        450,
        PARAM_INT,
        7
    ));
}
