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
 * Backup steps for mod_videoembed.
 *
 * @package   mod_videoembed
 * @category  backup
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright 2026 Rurak
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the backup_videoembed_activity_task
 */

/**
 * Define the complete page structure for backup, with file and id annotations
 */
class backup_videoembed_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure of the backup workflow.
     *
     * @return mixed
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $page = new backup_nested_element('videoembed', ['id'], [
            'name', 'intro', 'introformat', 'videourl', 'content', 'contentformat',
            'legacyfiles', 'legacyfileslast', 'display', 'displayoptions',
            'revision', 'timemodified']);

        // Build the tree.
        // (love this).

        // Define sources.
        $page->set_source_table('videoembed', ['id' => backup::VAR_ACTIVITYID]);

        // Define id annotations.
        // (none).

        // Define file annotations.
        $page->annotate_files('mod_videoembed', 'intro', null); // This file areas haven't itemid.
        $page->annotate_files('mod_videoembed', 'content', null); // This file areas haven't itemid.

        // Return the root element (page), wrapped into standard activity structure.
        return $this->prepare_activity_structure($page);
    }
}
