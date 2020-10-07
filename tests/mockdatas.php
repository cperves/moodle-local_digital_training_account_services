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
 * local_digital_training_account_services mockdatas for tests.
 *
 * @package    local_digital_training_account_services
 * @copyright  2020 Université de Strasbourg {@link https://unistra.fr}
 * @author  Céline Pervès <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_digital_training_account_services\tests;
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../locallib.php');

class mockdatas {
    private $user1;
    private $user2;
    private $course1;
    private $resource1;
    private $resourcecontext1;
    private $cmresource1;
    private $resource2;
    private $resourcecontext2;
    private $cmresource2;
    private $forum1;
    private $forum2;
    private $cmforum1;
    private $cmforum2;
    private $chat1;
    private $data1;
    private $cmchat1;
    private $cmdata1;

    private $datagenerator;
    public $output;

    /**
     * moockdatas constructor.
     */
    public function __construct($datagenerator) {
        global $OUTPUT;
        $this->datagenerator = $datagenerator;
        $this->output = $OUTPUT;

    }

    private function getDataGenerator() {
        return $this->datagenerator;
    }

    private function setUser($userid) {
        \advanced_testcase::setUser($userid);
    }
    private static function setAdminUser() {
        \advanced_testcase::setUser(2);
    }

    public function setup_users() {
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
    }

    public static function create_user_action_event($userid, $timestart = null) {
        $event = new \stdClass();
        $event->name = 'Calendar event';
        $event->modulename  = '';
        $event->courseid = 0;
        $event->userid = $userid;
        $event->instance = 0;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = 'user';
        if ($timestart) {
            $event->timestart = $timestart;
        } else {
            $event->timestart = time() + 86400;
        }
        $event->timesort = $event->timestart;
        $event->context = \context_user::instance($userid);
        return \calendar_event::create($event);
    }

    public function set_log_store() {
        set_config('enabled_stores', '', 'tool_log');
        // Enable logging plugin.
        set_config('enabled_stores', 'logstore_last_updated_course_module,logstore_last_viewed_course_module', 'tool_log');
        // Force reload.
        get_log_manager(true);
    }

    public function enrol_student() {
        global $DB;
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($this->get_user2()->id, $this->get_course1()->id, $studentrole->id);
    }

    public function create_modules($options = array()) {
        $this->resource1 = $this->getDataGenerator()->create_module('resource', array('course' => $this->course1) + $options);
        $this->resourcecontext1 = \context_module::instance($this->resource1->cmid);
        $this->cmresource1 = get_coursemodule_from_instance('resource', $this->resource1->id);
        $this->resource2 = $this->getDataGenerator()->create_module('resource', array('course' => $this->course1) + $options);
        $this->resourcecontext2 = \context_module::instance($this->resource2->cmid);
        $this->cmresource2 = get_coursemodule_from_instance('resource', $this->resource2->id);
        $this->forum1 = $this->getDataGenerator()->create_module('forum',
                array('course' => $this->course1, 'trackingtype' => FORUM_TRACKING_FORCED) + $options);
        $this->forum2 = $this->getDataGenerator()->create_module('forum',
                array('course' => $this->course1, 'trackingtype' => FORUM_TRACKING_FORCED) + $options);
        $this->cmforum1 = get_coursemodule_from_instance('forum', $this->forum1->id);
        $this->cmforum2 = get_coursemodule_from_instance('forum', $this->forum2->id);
        $this->chat1 = $this->getDataGenerator()->create_module('chat',
            array('course' => $this->course1, 'schedule' => 1, 'chattime' => time() + 24 * 3600) + $options);
        $this->data1 = $this->getDataGenerator()->create_module('data', array('course' => $this->course1) + $options);
        $this->cmchat1 = get_coursemodule_from_instance('chat', $this->chat1->id);
        $this->cmdata1 = get_coursemodule_from_instance('data', $this->data1->id);
        // Populate forums.
        set_config('forum_allowforcedreadtracking', 1); // To force tracking.
        $this->populate_forum($this->forum1, $this->course1);
        $this->populate_forum($this->forum2, $this->course1);
        // Retrieve log_manager and flush to trigger events.
        get_log_manager(true);
    }
    public function set_all_modules_unavailable() {
        global $DB;
        $notavailable = '{"op":"|","show":true,"c":[{"type":"mock","a":false}]}';
        $DB->set_field('course_modules', 'availability',
                $notavailable, array('id' => $this->cmresource1->id));
        $DB->set_field('course_modules', 'availability',
                $notavailable, array('id' => $this->cmresource2->id));
        $DB->set_field('course_modules', 'availability',
                $notavailable, array('id' => $this->cmforum1->id));
        $DB->set_field('course_modules', 'availability',
                $notavailable, array('id' => $this->cmforum2->id));
        $DB->set_field('course_modules', 'availability',
                $notavailable, array('id' => $this->cmchat1->id));
        $DB->set_field('course_modules', 'availability',
                $notavailable, array('id' => $this->cmdata1->id));
        get_fast_modinfo($this->course1, 0, true);

    }

    public function apply_deletioninprogress() {
        global $DB;
        $this->cmresource1->deletioninprogress = 1;
        $DB->update_record('course_modules', $this->cmresource1);
        $event = \core\event\course_module_updated::create_from_cm($this->cmresource1);
        $event->trigger();
        $this->cmresource2->deletioninprogress = 1;
        $DB->update_record('course_modules', $this->cmresource2);
        $event = \core\event\course_module_updated::create_from_cm($this->cmresource2);
        $event->trigger();
        $this->cmforum1->deletioninprogress = 1;
        $DB->update_record('course_modules', $this->cmforum1);
        $event = \core\event\course_module_updated::create_from_cm($this->cmforum1);
        $event->trigger();
        $this->cmforum2->deletioninprogress = 1;
        $DB->update_record('course_modules', $this->cmforum2);
        $event = \core\event\course_module_updated::create_from_cm($this->cmforum2);
        $event->trigger();
        $this->cmchat1->deletioninprogress = 1;
        $DB->update_record('course_modules', $this->cmchat1);
        $event = \core\event\course_module_updated::create_from_cm($this->cmchat1);
        $event->trigger();
        $this->cmdata1->deletioninprogress = 1;
        $DB->update_record('course_modules', $this->cmdata1);
        $event = \core\event\course_module_updated::create_from_cm($this->cmdata1);
        $event->trigger();
        get_log_manager(true); // To trigger events.
    }

    private function populate_forum($forum, $course) {
        // Add discussions to course  started by user1.
        $record = new \stdClass();
        $record->course = $course->id;
        $record->userid = $this->user1->id;
        $record->forum = $forum->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);
        // Add post to course by user2.
        $record = new \stdClass();
        $record->course = $course->id;
        $record->userid = $this->user2->id;
        $record->forum = $forum->id;
        $record->discussion = $discussion->id;
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);
    }

    public function create_course($options = array()) {
        $this->course1 = $this->getDataGenerator()->create_course($options);
        // Retrieve log_manager and flush to trigger events.
        get_log_manager(true);
    }

    public static function grade_module($userid, $coursemodule, $grade, $maxgrade=100, $mingrade=0) {
        $gradeitem = grade_item::fetch(array(
                'itemtype' => 'mod', 'itemmodule' => $coursemodule->modname,
                'iteminstance' => $coursemodule->instance, 'courseid' => $coursemodule->course
        ));
        $gradegrade = new grade_grade();
        $gradegrade->itemid = $gradeitem->id;
        $gradegrade->userid = $userid;
        $gradegrade->rawgrade = $grade;
        $gradegrade->finalgrade = $grade;
        $gradegrade->rawgrademax = $maxgrade;
        $gradegrade->rawgrademin = $mingrade;
        $gradegrade->timecreated = time();
        $gradegrade->timemodified = time();
        $gradegrade->insert();
    }

    public static function grade_course($userid, $courseid, $grade, $maxgrade=100, $mingrade=0) {
        $coursegradeitem = \grade_item::fetch_course_item($courseid);
        $gradegrade = new \grade_grade();
        $gradegrade->itemid = $coursegradeitem->id;
        $gradegrade->userid = $userid;
        $gradegrade->rawgrade = $grade;
        $gradegrade->finalgrade = $grade;
        $gradegrade->rawgrademax = $maxgrade;
        $gradegrade->rawgrademin = $mingrade;
        $gradegrade->timecreated = time();
        $gradegrade->timemodified = time();
        $gradegrade->insert();
    }


    public static function create_course_action_event($courseid, $moduleinstanceid, $modulename, $timestart = null) {
        $event = new \stdClass();
        $event->name = 'Calendar event';
        $event->modulename  = $modulename;
        $event->courseid = $courseid;
        $event->instance = $moduleinstanceid;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $courseid == SITEID ? 'site' : 'course';
        if ($timestart) {
            $event->timestart = $timestart;
        } else {
            $event->timestart = time() + 86400; // 24h.
        }
        $event->timesort = $event->timestart;

        return \calendar_event::create($event);
    }

    public function setup_externallib_datas() {
        global $DB;
        $this->setAdminUser();

        $this->setUser($this->user1->id);
        $this->create_course(array('showgrades' => '1', 'enablecompletion' => 1));
        $this->create_modules(array('assessed' => 1, 'scale' => 100, 'completion' => 1));
        $editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course1->id, $editingteacherrole->id);
        self::create_course_action_event($this->course1->id, $this->forum1->id, 'forum');
        self::create_course_action_event($this->course1->id, $this->chat1->id, 'chat');
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course1->id, $studentrole->id);
        // Grade course.
        self::grade_course($this->user2->id, $this->course1->id, 60);

        $this->setUser($this->user2->id);
        // Completion.
        $completion = new \completion_info($this->course1);
        $completion->update_state($this->cmresource1, COMPLETION_COMPLETE, $this->user2->id);
        $completion->update_state($this->cmresource2, COMPLETION_COMPLETE, $this->user2->id);
        set_config('courseviewreinit', 1, 'local_digital_training_account_services');
        get_log_manager(true);
    }

    /**
     * @return mixed
     */
    public function get_user1() {
        return $this->user1;
    }

    /**
     * @return mixed
     */
    public function get_user2() {
        return $this->user2;
    }

    /**
     * @return mixed
     */
    public function get_course1() {
        return $this->course1;
    }

    /**
     * @return mixed
     */
    public function get_resource1() {
        return $this->resource1;
    }

    /**
     * @return mixed
     */
    public function get_resourcecontext1() {
        return $this->resourcecontext1;
    }

    /**
     * @return mixed
     */
    public function get_cmresource1() {
        return $this->cmresource1;
    }

    /**
     * @return mixed
     */
    public function get_resource2() {
        return $this->resource2;
    }

    /**
     * @return mixed
     */
    public function get_resourcecontext2() {
        return $this->resourcecontext2;
    }

    /**
     * @return mixed
     */
    public function get_cmresource2() {
        return $this->cmresource2;
    }

    /**
     * @return mixed
     */
    public function get_forum1() {
        return $this->forum1;
    }

    /**
     * @return mixed
     */
    public function get_forum2() {
        return $this->forum2;
    }

    /**
     * @return mixed
     */
    public function get_cmforum1() {
        return $this->cmforum1;
    }

    /**
     * @return mixed
     */
    public function get_cmforum2() {
        return $this->cmforum2;
    }

    /**
     * @return mixed
     */
    public function get_chat1() {
        return $this->chat1;
    }

    /**
     * @return mixed
     */
    public function get_data1() {
        return $this->data1;
    }

    /**
     * @return mixed
     */
    public function get_cmchat1() {
        return $this->cmchat1;
    }

    /**
     * @return mixed
     */
    public function get_cmdata1() {
        return $this->cmdata1;
    }

}

