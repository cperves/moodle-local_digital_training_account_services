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
 * local_digital_training_account_services tests.
 *
 * @package    local_digital_training_account_services
 * @copyright  2020 Université de Strasbourg {@link https://unistra.fr}
 * @author  Céline Pervès <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_digital_training_account_services;
global $CFG;

require_once(__DIR__.'/../locallib.php');
require_once(__DIR__.'/../externallib.php');
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot.'/local/digital_training_account_services/tests/mockdatas.php');

use advanced_testcase;
use context_module;
use local_digital_training_account_services\mockdatas;
use local_digital_training_account_services_tools;
use mod_forum\event\course_module_viewed;
use mod_forum\event\discussion_created;
use stdClass;

class forum_counters_test extends advanced_testcase{
    const MODULE_GROUPED_CONFIG_WELL_FORMATTED = '{"groupmodules": {"resource" : ["resource"], "msg" : ["forum"]} , '
    .'"modulegroups" : {"resource" : ["resource"], "forum" : ["msg"]}}';

    public function test_get_course_counters_forum_advanced() {
        $this->setup_datas();
        $this->mockdatas->set_log_store();
        $this->mockdatas->create_course();
        $this->mockdatas->enrol_student();
        // Here module were created.
        $this->setUser($this->mockdatas->get_user1()->id);
        $this->mockdatas->create_modules();
        $this->setUser($this->mockdatas->get_user2()->id);
        // Already well formatted modulegrouped.
        set_config('modulegrouped',
            self::MODULE_GROUPED_CONFIG_WELL_FORMATTED,
            'local_digital_training_account_services');
        // Evrything is new.
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertCount(3, $groupedmodules);
        foreach ($groupedmodules as $groupedmodule) {
            $this->assertEquals(2, $groupedmodule['count']);
        }
        // User 2 visit 1 forum.
        $this->waitForSecond();
        self::view_forum($this->mockdatas->get_forum1());
        get_log_manager(true); // Reload to trigger events.
        $groupedmodules = local_digital_training_account_services_tools::get_course_counters_grouped(
            $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertCount(3, $groupedmodules);
        foreach ($groupedmodules as $groupedmodule) {
            if ($groupedmodule['groupName'] == 'msg') {
                $this->assertEquals(1 , $groupedmodule['count']);
            }
        }
        // User 2 visit other forum.
        $this->waitForSecond();
        self::view_forum($this->mockdatas->get_forum2());
        get_log_manager(true); // Reload to trigger events.
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertCount(3, $groupedmodules);
        foreach ($groupedmodules as $groupedmodule) {
            if ($groupedmodule['groupName'] == 'msg') {
                $this->assertEquals(0 , $groupedmodule['count']);
            }
        }
        $this->create_discussion($this->mockdatas->get_user1(), $this->mockdatas->get_forum1()); // Reload to trigger events.
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        foreach ($groupedmodules as $groupedmodule) {
            if ($groupedmodule['groupName'] == 'msg') {
                $this->assertEquals(1 , $groupedmodule['count']);
            }
        }
        // User 2 write a post => nothing supposed to change.
        $this->create_discussion($this->mockdatas->get_user2(), $this->mockdatas->get_forum1());
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        // 1 new message.
        foreach ($groupedmodules as $groupedmodule) {
            if ($groupedmodule['groupName'] == 'msg') {
                $this->assertEquals(1 , $groupedmodule['count']);
            }
        }
    }

    public function test_get_course_counters_forum_add_deleted_discussion() {
        global $DB;
        $this->setup_datas();
        $this->mockdatas->set_log_store();
        $this->mockdatas->create_course();
        $this->mockdatas->enrol_student();
        // Here module were created.
        $this->setUser($this->mockdatas->get_user1()->id);
        $this->mockdatas->create_modules();
        $this->setUser($this->mockdatas->get_user2()->id);
        // Already well formatted modulegrouped.
        set_config('modulegrouped',
            self::MODULE_GROUPED_CONFIG_WELL_FORMATTED,
            'local_digital_training_account_services');
        // Everything is new.
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped($this->mockdatas->get_course1(),
                $this->mockdatas->get_user2()->id);
        $this->assertCount(3, $groupedmodules);
        foreach ($groupedmodules as $groupedmodule) {
            if ($groupedmodule['groupName'] == 'msg') {
                $this->assertEquals(2 , $groupedmodule['count']);
            }
        }
        // User2 read message by viewing forum1 and forum2.
        $this->waitForSecond();
        $this->view_forum($this->mockdatas->get_forum1());
        $this->view_forum($this->mockdatas->get_forum2());
        get_log_manager(true); // Reload to trigger events.
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        foreach ($groupedmodules as $groupedmodule) {
            if ($groupedmodule['groupName'] == 'msg') {
                $this->assertEquals(0 , $groupedmodule['count']);
            }
        }
        // User1 create a discussion on forum1.
        $discussion = $this->create_discussion($this->mockdatas->get_user1(), $this->mockdatas->get_forum1());
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        foreach ($groupedmodules as $groupedmodule) {
            if ($groupedmodule['groupName'] == 'msg') {
                $this->assertEquals(1 , $groupedmodule['count']);
            }
        }
        // Immediately delete discussion.
        $this->waitForSecond();
        $this->delete_discussion(
            $discussion, $this->mockdatas->get_forum1(), $this->mockdatas->get_cmforum1(), $this->mockdatas->get_course1());
        $this->waitForSecond();
        $groupedmodules = local_digital_training_account_services_tools::get_course_counters_grouped(
            $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        foreach ($groupedmodules as $groupedmodule) {
            if ($groupedmodule['groupName'] == 'msg') {
                $this->assertEquals(0 , $groupedmodule['count']);
            }
        }
    }

    private function setup_mockdatas() {
        $this->mockdatas = new mockdatas($this->getDataGenerator());
    }
    private function setup_datas() {
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.
        $this->setup_mockdatas();
        $this->setAdminUser();
        $this->mockdatas->setup_users();
    }

    private function create_discussion($user, $forum) {
        $this->waitForSecond();
        // User 1 add new discussion in forum.
        $record = new  stdClass();
        $record->course = $forum->course;
        $record->userid = $user->id;
        $record->forum = $forum->id;
        $record = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);
        // Trigger event.
        $eventparams = array(
            'context' =>  context_module::instance($this->mockdatas->get_forum1()->cmid),
            'other' => array('forumid' => $this->mockdatas->get_forum1()->id),
            'objectid' => $record->id
        );
        discussion_created::create($eventparams)->trigger();
        get_log_manager(true); // Reload to trigger events.
        return $record;
    }

    private function delete_discussion($discussion, $forum, $cm, $course) {
        forum_delete_discussion($discussion, true, $course, $cm, $forum);
        get_log_manager(true); // Reload to trigger events.
    }

    /**
     * @return array
     * @throws coding_exception
     */
    private function view_forum($forum) {
        $params = array(
            'objectid' => $forum->id,
            'context' =>  context_module::instance($forum->cmid)
        );
        $event = course_module_viewed::create($params);
        $event->trigger();
    }
}
