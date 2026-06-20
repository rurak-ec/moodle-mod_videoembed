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
 * Restore steps for mod_videoembed.
 *
 * @package   mod_videoembed
 * @category  backup
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_videoembed_activity_task
 */

/**
 * Structure step to restore one videoembed activity
 */
class restore_videoembed_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure of the restore workflow.
     *
     * @return mixed
     */
    protected function define_structure() {

        $paths = [];
        $paths[] = new restore_path_element('videoembed', '/activity/videoembed');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process a restored videoembed record.
     *
     * @param array|object $data The data to restore.
     * @return void
     */
    protected function process_videoembed($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.

        // Insert the videoembed record.
        $newitemid = $DB->insert_record('videoembed', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Actions to run after the restore step executes.
     *
     * @return void
     */
    protected function after_execute() {
        // Add page related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_videoembed', 'intro', null);
        $this->add_related_files('mod_videoembed', 'content', null);
    }
}
