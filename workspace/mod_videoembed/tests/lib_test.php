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
 * Unit tests for mod_videoembed lib
 *
 * @package    mod_videoembed
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
namespace mod_videoembed;

defined('MOODLE_INTERNAL') || die();


/**
 * Unit tests for mod_videoembed lib
 *
 * @package    mod_videoembed
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
final class lib_test extends \advanced_testcase {

    /**
     * Prepares things before this test case is initialised
     * @return void
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/videoembed/lib.php');
        require_once($CFG->dirroot . '/mod/videoembed/locallib.php');
        parent::setUpBeforeClass();
    }

    /**
     * Test videoembed_view
     * @return void
     */
    public function test_page_view(): void {
        global $CFG;

        $CFG->enablecompletion = 1;
        $this->resetAfterTest();

        // Setup test data.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $page = $this->getDataGenerator()->create_module('videoembed', array('course' => $course->id),
                                                            array('completion' => 2, 'completionview' => 1));
        $context = \context_module::instance($page->cmid);
        $cm = get_coursemodule_from_instance('videoembed', $page->id);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $this->setAdminUser();
        videoembed_view($page, $course, $cm, $context);

        $events = $sink->get_events();
        // 2 additional events thanks to completion.
        $this->assertCount(3, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_videoembed\event\course_module_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $moodleurl = new \moodle_url('/mod/videoembed/view.php', array('id' => $cm->id));
        $this->assertEquals($moodleurl, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());

        // Check completion status.
        $completion = new \completion_info($course);
        $completiondata = $completion->get_data($cm);
        $this->assertEquals(1, $completiondata->completionstate);
    }

    public function test_page_core_calendar_provide_event_action(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create the activity.
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('videoembed', array('course' => $course->id));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $page->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_videoembed_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('view'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_page_core_calendar_provide_event_action_already_completed(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->enablecompletion = 1;

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $page = $this->getDataGenerator()->create_module('videoembed', array('course' => $course->id),
            array('completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS));

        // Get some additional data.
        $cm = get_coursemodule_from_instance('videoembed', $page->id);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $page->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_videoembed_core_calendar_provide_event_action($event, $factory);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    /**
     * Test mod_videoembed_core_calendar_provide_event_action with user override
     */
    public function test_page_core_calendar_provide_event_action_user_override(): void {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $CFG->enablecompletion = 1;

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $page = $this->getDataGenerator()->create_module('videoembed', array('course' => $course->id),
            array('completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS));

        // Get some additional data.
        $cm = get_coursemodule_from_instance('videoembed', $page->id);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $page->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_videoembed_core_calendar_provide_event_action($event, $factory, $USER->id);

        // Decorate action with a userid override.
        $actionevent2 = mod_videoembed_core_calendar_provide_event_action($event, $factory, $user->id);

        // Ensure result was null because it has been marked as completed for the associated user.
        // Logic was brought across from the "_already_completed" function.
        $this->assertNull($actionevent);

        // Confirm the event was decorated.
        $this->assertNotNull($actionevent2);
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent2);
        $this->assertEquals(get_string('view'), $actionevent2->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent2->get_url());
        $this->assertEquals(1, $actionevent2->get_item_count());
        $this->assertTrue($actionevent2->is_actionable());
    }

    /**
     * Creates an action event.
     *
     * @param int $courseid The course id.
     * @param int $instanceid The instance id.
     * @param string $eventtype The event type.
     * @return bool|calendar_event
     */
    private function create_action_event($courseid, $instanceid, $eventtype) {
        $event = new \stdClass();
        $event->name = 'Calendar event';
        $event->modulename  = 'videoembed';
        $event->courseid = $courseid;
        $event->instance = $instanceid;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->timestart = time();

        return \calendar_event::create($event);
    }

    /**
     * Test Bunny Stream play URL conversion into canonical embed URL.
     */
    public function test_parse_bunny_play_url_is_normalized_to_embed(): void {
        $parsed = \videoembed_parse_video_url(
            'https://player.mediadelivery.net/play/620387/836e1b83-f6dc-4484-aca8-63b48c252e5c'
        );

        $this->assertNotEmpty($parsed);
        $this->assertTrue($parsed['valid']);
        $this->assertSame('bunny', $parsed['provider']);
        $this->assertSame(
            'https://player.mediadelivery.net/embed/620387/836e1b83-f6dc-4484-aca8-63b48c252e5c' .
            '?autoplay=false&loop=false&muted=false&preload=true&responsive=true',
            $parsed['embedurl']
        );
    }

    /**
     * Test Bunny Stream bool params normalisation while preserving extra params.
     */
    public function test_parse_bunny_play_url_query_params_are_normalized_and_preserved(): void {
        $parsed = \videoembed_parse_video_url(
            'https://player.mediadelivery.net/play/620387/836e1b83-f6dc-4484-aca8-63b48c252e5c' .
            '?autoplay=1&muted=0&token=abc123'
        );

        $this->assertNotEmpty($parsed);
        $this->assertTrue($parsed['valid']);
        $this->assertStringContainsString('autoplay=true', $parsed['embedurl']);
        $this->assertStringContainsString('muted=false', $parsed['embedurl']);
        $this->assertStringContainsString('loop=false', $parsed['embedurl']);
        $this->assertStringContainsString('preload=true', $parsed['embedurl']);
        $this->assertStringContainsString('responsive=true', $parsed['embedurl']);
        $this->assertStringContainsString('token=abc123', $parsed['embedurl']);
    }
}
