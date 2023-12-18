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
use completion_info;
use context_course;
use context_module;
use core\event\course_module_updated;
use core\event\course_viewed;
use local_digital_training_account_services\mockdatas;
use local_digital_training_account_services_tools;
use mod_chat\event\course_module_viewed;

class lib_test extends advanced_testcase{
    const MODULE_GROUPED_WELL_FORMATTED = '{"groupmodules": {"resource" : ["resource"], "msg" : ["forum"]} , '
    .'"modulegroups" : {"resource" : ["resource"], "forum" : ["msg"]}}';

    /**
     * test module_has_changed function
     * @throws coding_exception
     */
    public function test_module_has_changed() {
        $this->setup_datas();
        $this->mockdatas->set_log_store();
        $this->mockdatas->create_course();
        $this->mockdatas->enrol_student();
        // Here module were created.
        $this->setUser($this->mockdatas->get_user1()->id);
        $this->mockdatas->create_modules();
        $this->setUser($this->mockdatas->get_user2()->id);
        // Never visit course module.
        $this->assertTrue(
            local_digital_training_account_services_tools::module_has_changed(
                $this->mockdatas->get_cmresource1()->id, $this->mockdatas->get_user2()->id)
        );
        $this->assertTrue(
            local_digital_training_account_services_tools::module_has_changed(
                $this->mockdatas->get_cmresource2()->id, $this->mockdatas->get_user2()->id)
        );
        $this->waitForSecond();
        resource_view(
            $this->mockdatas->get_resource1(),
            $this->mockdatas->get_course1(),
            $this->mockdatas->get_cmresource1(),
             context_module::instance($this->mockdatas->get_resource1()->cmid)
        );
        resource_view(
            $this->mockdatas->get_resource2(),
            $this->mockdatas->get_course1(),
            $this->mockdatas->get_cmresource2(),
             context_module::instance($this->mockdatas->get_resource2()->cmid)
        );
        // Trigger associated events.
        get_log_manager(true);
        $this->assertFalse(
            local_digital_training_account_services_tools::module_has_changed(
                $this->mockdatas->get_cmresource1()->id, $this->mockdatas->get_user2()->id)
        );
        $this->assertFalse(
            local_digital_training_account_services_tools::module_has_changed(
                $this->mockdatas->get_cmresource2()->id, $this->mockdatas->get_user2()->id)
        );
        // User 1 changed cm1 and cm2.
        $this->waitForSecond();
        $this->setUser($this->setUser($this->mockdatas->get_user1()->id));
        $event = course_module_updated::create_from_cm($this->mockdatas->get_cmresource1());
        $event->trigger();
        $event = course_module_updated::create_from_cm($this->mockdatas->get_cmresource2());
        $event->trigger();
        // Trigger associated events.
        get_log_manager(true);
        // User 2 viewed cm2 but not cm1.
        $this->setUser($this->mockdatas->get_user2()->id);
        $this->waitForSecond();
        resource_view($this->mockdatas->get_resource1(),
            $this->mockdatas->get_course1(),
            $this->mockdatas->get_cmresource1(),
             context_module::instance($this->mockdatas->get_resource1()->cmid)
        );
        get_log_manager(true);
        $this->assertFalse(
            local_digital_training_account_services_tools::module_has_changed(
                $this->mockdatas->get_cmresource1()->id, $this->mockdatas->get_user2()->id)
        );
        $this->assertTrue(
            local_digital_training_account_services_tools::module_has_changed(
                $this->mockdatas->get_cmresource2()->id, $this->mockdatas->get_user2()->id)
        );
    }

    /**
     * * test module_has_changed function for course and modules activities before log stores activation
     */
    public function test_module_has_changed_after_plugin_activation() {
        $this->setup_datas();
        $this->setUser($this->mockdatas->get_user2()->id);
        $this->mockdatas->create_course();
        $this->mockdatas->create_modules();
        $this->mockdatas->enrol_student();
        // Log store plugins not again activated.
        $this->setUser($this->mockdatas->get_user2()->id);
        $this->waitForSecond();
        resource_view(
            $this->mockdatas->get_resource1(),
            $this->mockdatas->get_course1(),
            $this->mockdatas->get_cmresource1(),
             context_module::instance($this->mockdatas->get_resource1()->cmid)
        );
        resource_view($this->mockdatas->get_resource2(),
            $this->mockdatas->get_course1(),
            $this->mockdatas->get_cmresource2(),
             context_module::instance($this->mockdatas->get_resource2()->cmid)
        );
        // Flush events.
        get_log_manager(true);
        // Now activate stores.
        $this->mockdatas->set_log_store();
        // Default config for unloggedasnew -> old events before log activation not considered as new.
        $this->assertFalse(
            local_digital_training_account_services_tools::module_has_changed(
                $this->mockdatas->get_cmresource1()->id, $this->mockdatas->get_user2()->id)
        );
        $this->assertFalse(
            local_digital_training_account_services_tools::module_has_changed(
                $this->mockdatas->get_cmresource2()->id, $this->mockdatas->get_user2()->id)
        );
        // Change unloggedasnew.
        set_config('unloggedasnew', 1, 'local_digital_training_account_services');
        $this->assertTrue(
            local_digital_training_account_services_tools::module_has_changed(
                $this->mockdatas->get_cmresource1()->id, $this->mockdatas->get_user2()->id)
        );
        $this->assertTrue(
            local_digital_training_account_services_tools::module_has_changed(
                $this->mockdatas->get_cmresource2()->id, $this->mockdatas->get_user2()->id)
        );
    }

    public function test_get_course_counters_grouped() {
        $this->setup_datas();
        $this->mockdatas->set_log_store();
        $this->mockdatas->create_course();
        $this->mockdatas->enrol_student();
        // Here module were created.
        $this->setUser($this->mockdatas->get_user1()->id);
        $this->mockdatas->create_modules();
        $this->setUser($this->mockdatas->get_user2()->id);
        // Already well formatted modulegrouped.
        set_config('modulegrouped', self::MODULE_GROUPED_WELL_FORMATTED, 'local_digital_training_account_services');
        // Evrything is new.
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertCount(3, $groupedmodules);
        foreach ($groupedmodules as $groupedmodule) {
            $this->assertEquals(2, $groupedmodule['count']);
        }
        $this->waitForSecond();
        // User 2 visit ressource1, chat and 1 forum.
        resource_view(
            $this->mockdatas->get_resource1(),
            $this->mockdatas->get_course1(),
            $this->mockdatas->get_cmresource1(),
             context_module::instance($this->mockdatas->get_resource1()->cmid)
        );
        $params = array(
            'objectid' => $this->mockdatas->get_chat1()->id,
            'context' =>  context_module::instance($this->mockdatas->get_chat1()->cmid)
        );
        $event = course_module_viewed::create($params);
        $event->trigger();
        $params = array(
            'objectid' => $this->mockdatas->get_forum1()->id,
            'context' =>  context_module::instance($this->mockdatas->get_forum1()->cmid)
        );
        $event = course_module_viewed::create($params);
        $event->trigger();
        get_log_manager(true); // Reload to trigger events.
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertCount(3, $groupedmodules);
        foreach ($groupedmodules as $groupedmodule) {
            $this->assertEquals(1, $groupedmodule['count']);
        }

    }


    public function test_get_course_counters_grouped_deletioninprogress() {
        $this->setup_datas();
        $this->mockdatas->set_log_store();
        $this->mockdatas->create_course();
        $this->mockdatas->enrol_student();
        // Here module were created.
        $this->setUser($this->mockdatas->get_user1()->id);
        $this->mockdatas->create_modules();
        $this->setUser($this->mockdatas->get_user2()->id);
        // Already well formatted modulegrouped.
        set_config('modulegrouped', self::MODULE_GROUPED_WELL_FORMATTED, 'local_digital_training_account_services');
        // Evrything is new.
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertCount(3, $groupedmodules);
        foreach ($groupedmodules as $groupedmodule) {
            $this->assertEquals(2, $groupedmodule['count']);
        }
        $this->waitForSecond();
        // User 2 visit ressource1, chat and 1 forum.
        // Forum is a special case since hooked, visiting forum will no pass unread posts to read.
        resource_view(
            $this->mockdatas->get_resource1(),
            $this->mockdatas->get_course1(),
            $this->mockdatas->get_cmresource1(),
             context_module::instance($this->mockdatas->get_resource1()->cmid)
        );
        $params = array(
            'objectid' => $this->mockdatas->get_chat1()->id,
            'context' =>  context_module::instance($this->mockdatas->get_chat1()->cmid)
        );
        $event = course_module_viewed::create($params);
        $event->trigger();
        $params = array(
            'objectid' => $this->mockdatas->get_forum1()->id,
            'context' => context_module::instance($this->mockdatas->get_forum1()->cmid)
        );
        $event = course_module_viewed::create($params);
        $event->trigger();
        get_log_manager(true); // Reload to trigger events.
        // Pass modules to deletioninprogress.
        $this->setAdminUser();
        $this->mockdatas->apply_deletioninprogress();
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertCount(3, $groupedmodules);
        foreach ($groupedmodules as $groupedmodule) {
            $this->assertEquals(0, $groupedmodule['count']);
        }

    }
    public function test_get_course_counters_grouped_notvisible() {
        $this->setup_datas();
        $this->mockdatas->set_log_store();
        $this->mockdatas->create_course();
        $this->mockdatas->enrol_student();
        // Here module were created.
        $this->setUser($this->mockdatas->get_user1()->id);
        $this->mockdatas->create_modules(array('visible' => '0'));
        $this->setUser($this->mockdatas->get_user2()->id);
        // Already well formatted modulegrouped.
        set_config('modulegrouped', self::MODULE_GROUPED_WELL_FORMATTED, 'local_digital_training_account_services');
        // Evrything is new.
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertCount(3, $groupedmodules);
        foreach ($groupedmodules as $groupedmodule) {
            $this->assertEquals(0, $groupedmodule['count']);
        }
    }
    public function test_get_course_counters_grouped_notavailable() {
        $this->setup_datas();
        $this->mockdatas->set_log_store();
        $this->mockdatas->create_course();
        $this->mockdatas->enrol_student();
        // Here module were created.
        $this->setUser($this->mockdatas->get_user1()->id);
        $this->mockdatas->create_modules();
        $this->mockdatas->set_all_modules_unavailable();
        $this->setUser($this->mockdatas->get_user2()->id);
        // Already well formatted modulegrouped.
        set_config('modulegrouped', self::MODULE_GROUPED_WELL_FORMATTED, 'local_digital_training_account_services');
        // Evrything is new.
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertCount(3, $groupedmodules);
        foreach ($groupedmodules as $groupedmodule) {
            $this->assertEquals(0, $groupedmodule['count']);
        }
    }

    public function test_course_viewed() {
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
        set_config('modulegrouped', self::MODULE_GROUPED_WELL_FORMATTED, 'local_digital_training_account_services');
        // Courseviewreinit is not activated.
        set_config('courseviewreinit', 0, 'local_digital_training_account_services');
        // Evrything is new.
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertCount(3, $groupedmodules);
        foreach ($groupedmodules as $groupedmodule) {
            $this->assertEquals(2, $groupedmodule['count']);
        }
        $this->waitForSecond();
        // User 2 visit course but courseviewreinit is set to false so nothing appends.
        $eventparams = array(
            'context' => context_course::instance($this->mockdatas->get_course1()->id)
        );
        course_viewed::create($eventparams)->trigger();
        get_log_manager(true);
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertCount(3, $groupedmodules);
        foreach ($groupedmodules as $groupedmodule) {
            $this->assertEquals(2, $groupedmodule['count']);
        }
        $this->waitForSecond();
        set_config('courseviewreinit', 1, 'local_digital_training_account_services');
        course_viewed::create($eventparams)->trigger();
        get_log_manager(true);
        $groupedmodules =
            local_digital_training_account_services_tools::get_course_counters_grouped(
                $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertCount(3, $groupedmodules);
        foreach ($groupedmodules as $groupedmodule) {
            $this->assertEquals(0, $groupedmodule['count']);
        }
    }

    public function test_get_course_completion() {
        global $DB;
        $this->setup_datas();
        $this->mockdatas->create_course(array('enablecompletion' => 1));
        $this->mockdatas->create_modules(array('completion' => 1));
        $this->mockdatas->enrol_student();
        // Emulate completion.
        $this->setUser($this->mockdatas->get_user2()->id);
        $completion = new completion_info($this->mockdatas->get_course1());
        $completion->update_state(
            $this->mockdatas->get_cmresource1(), COMPLETION_COMPLETE, $this->mockdatas->get_user2()->id);
        $completion->update_state(
            $this->mockdatas->get_cmresource2(), COMPLETION_COMPLETE, $this->mockdatas->get_user2()->id);
        $coursecompletion = local_digital_training_account_services_tools::get_course_completion(
            $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertNotNull($coursecompletion);
        $this->assertContains('percentage', array_keys($coursecompletion));
        $this->assertContains('ratio', array_keys($coursecompletion));
        $this->assertEquals(33, floor($coursecompletion['percentage']));
        $this->assertEquals('2/6', $coursecompletion['ratio']);
        $completion->update_state(
            $this->mockdatas->get_cmforum1(), COMPLETION_COMPLETE, $this->mockdatas->get_user2()->id);
        $completion->update_state(
            $this->mockdatas->get_cmforum2(), COMPLETION_COMPLETE, $this->mockdatas->get_user2()->id);
        $coursecompletion = local_digital_training_account_services_tools::get_course_completion(
            $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertNotNull($coursecompletion);
        $this->assertContains('percentage', array_keys($coursecompletion));
        $this->assertContains('ratio', array_keys($coursecompletion));
        $this->assertEquals(66, floor($coursecompletion['percentage']));
        $this->assertEquals('4/6', $coursecompletion['ratio']);
        $completion->update_state(
            $this->mockdatas->get_cmchat1(), COMPLETION_COMPLETE, $this->mockdatas->get_user2()->id);
        $completion->update_state(
            $this->mockdatas->get_cmdata1(), COMPLETION_COMPLETE, $this->mockdatas->get_user2()->id);
        $coursecompletion = local_digital_training_account_services_tools::get_course_completion(
            $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertNotNull($coursecompletion);
        $this->assertContains('percentage', array_keys($coursecompletion));
        $this->assertContains('ratio', array_keys($coursecompletion));
        $this->assertEquals(100, $coursecompletion['percentage']);
        $this->assertEquals('6/6', $coursecompletion['ratio']);
    }

    public function test_get_course_final_grade() {
        global $DB;
        $this->setup_datas();
        $this->mockdatas->create_course(array('showgrades' => '1'));
        $this->mockdatas->enrol_student();
        $this->mockdatas->create_modules(array('assessed' => 1, 'scale' => 100));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->setUser($this->mockdatas->get_user2()->id);
        $this->getDataGenerator()->enrol_user(
            $this->mockdatas->get_user2()->id, $this->mockdatas->get_course1()->id, $studentrole->id);
        // Insert student grades.
        $this->mockdatas->grade_course($this->mockdatas->get_user2()->id, $this->mockdatas->get_course1()->id, 60);
        $gradeinfos = local_digital_training_account_services_tools::get_course_final_grade(
            $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id);
        $this->assertNotNull($gradeinfos);
        $this->isInstanceOf('array', $gradeinfos);
        $this->assertContains('finalGrade', array_keys($gradeinfos));
        $this->assertContains('maxGrade', array_keys($gradeinfos));
        $this->assertEquals($gradeinfos['finalGrade'], 60);
        $this->assertEquals($gradeinfos['maxGrade'], 100);
    }

    public function test_get_course_calendar_events() {
        global $DB;
        $this->setup_datas();
        $this->setAdminUser();
        // Add a course event to check that it is excluded from results.
        $this->mockdatas->create_course_action_event(SITEID, 0, 0);
        $this->setUser($this->mockdatas->get_user1()->id);
        $this->mockdatas->create_course(array('showgrades' => '1'));
        $this->mockdatas->enrol_student();
        $this->mockdatas->create_modules(array('assessed' => 1, 'scale' => 100));
        $editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user(
            $this->mockdatas->get_user1()->id, $this->mockdatas->get_course1()->id, $editingteacherrole->id);
        $this->mockdatas->create_course_action_event(
            $this->mockdatas->get_course1()->id, $this->mockdatas->get_forum1()->id, 'forum');
        $this->mockdatas->create_course_action_event(
            $this->mockdatas->get_course1()->id, $this->mockdatas->get_chat1()->id, 'chat');
        // Create course site event.
        // Add a user event to check that it is excluded from results.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->setUser($this->mockdatas->get_user2()->id);
        $this->mockdatas->create_user_action_event($this->mockdatas->get_user2()->id);
        $this->getDataGenerator()->enrol_user(
            $this->mockdatas->get_user2()->id, $this->mockdatas->get_course1()->id, $studentrole->id);
        $eventcount = local_digital_training_account_services_tools::get_course_calendar_events_count(
            $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id, null);
        $this->assertEquals(3, $eventcount); // 2 course events and 1 for chat.
        $eventcount = local_digital_training_account_services_tools::get_course_calendar_events_count(
            $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id, 24 * 3600 / 2);
        $this->assertEquals(0, $eventcount); // 2 course events and 1 for chat.
        $eventcount = local_digital_training_account_services_tools::get_course_calendar_events_count(
            $this->mockdatas->get_course1(), $this->mockdatas->get_user2()->id, 48 * 3600);
        $this->assertEquals(3, $eventcount); // 2 course events and 1 for chat.
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
}
