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
require_once($CFG->dirroot.'/local/digital_training_account_services/tests/positioning_mockdatas.php');

use context_system;
use external_api;
use externallib_advanced_testcase;
use local_digital_training_account_services\positioning_mockdatas;
use local_digital_training_account_services_exception;
use local_digital_training_account_services_external;
use required_capability_exception;

class positioning_tests_externallib_test extends externallib_advanced_testcase{
    private $positioningmockdatas;
    public function test_get_positioning_tests_externallib() {
        $this->setup_datas();
        $this->positioningmockdatas->create_courses();
        $this->positioningmockdatas->enrol_users();
        $this->positioningmockdatas->create_tests();
        $this->positioningmockdatas->pass_tests_as_positioning();
        $positioningtests = local_digital_training_account_services_external::get_positioning_tests(
            $this->positioningmockdatas->get_user2()->username);
        $positioningtests = external_api::clean_returnvalue(
            local_digital_training_account_services_external::get_positioning_tests_returns(), $positioningtests);
        $this->assertCount(2, $positioningtests);
        $positioningtest = array_pop($positioningtests);
        $this->check_result_structure($positioningtest);
    }

    public function test_get_positioning_tests_externallib_missing_capability() {
        $this->setup_datas();
        $this->positioningmockdatas->create_courses();
        $this->positioningmockdatas->enrol_users();
        $this->positioningmockdatas->create_tests();
        $this->positioningmockdatas->pass_tests_as_positioning();
        $testuser = $this->getDataGenerator()->create_user();
        $systemcontext = context_system::instance();
        $this->setUser($testuser);
        $this->expectException(required_capability_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (user can access to positioning test list for other user).');
        $positioningtests = local_digital_training_account_services_external::get_positioning_tests(
            $this->positioningmockdatas->get_user2()->username);
        $positioningtests = external_api::clean_returnvalue(
            local_digital_training_account_services_external::get_positioning_tests_returns(), $positioningtests);
        $this->assertCount(2, $positioningtests);
    }


    public function test_empty_positioning_metadata_field() {
        global $CFG;
        require_once($CFG->libdir.'/adminlib.php');
        $this->setup_datas();
        $this->positioningmockdatas->create_courses();
        $this->positioningmockdatas->enrol_users();
        $this->positioningmockdatas->create_tests();
        $this->positioningmockdatas->pass_tests_as_positioning();
        $newroleid = $this->getDataGenerator()->create_role('newrole');
        $testuser = $this->getDataGenerator()->create_user();
        $systemcontext = context_system::instance();
        assign_capability('local/digital_training_account_services:positioning_tests_list_informations_for_other_user',
            CAP_ALLOW,
            $newroleid, $systemcontext->id, true);
        role_assign($newroleid, $testuser->id, $systemcontext->id);
        $this->setUser($testuser);
        set_config('positioning_metadata_field', '', 'local_digital_training_account_services');
        get_log_manager(true);
        $this->expectException(local_digital_training_account_services_exception::class);
        $this->expectExceptionMessage("empty_positioning_metadatafield");
        local_digital_training_account_services_external::get_positioning_tests($this->positioningmockdatas->get_user2()->username);
    }

    public function test_without_positioning_tests_marked() {
        global $CFG;
        require_once($CFG->libdir.'/adminlib.php');
        $this->setup_datas();
        $this->positioningmockdatas->create_courses();
        $this->positioningmockdatas->enrol_users();
        $this->positioningmockdatas->create_tests();
        $positioningtests = local_digital_training_account_services_external::get_positioning_tests(
            $this->positioningmockdatas->get_user2()->username);
        $this->assertTrue(is_array($positioningtests));
        $this->assertCount(0, $positioningtests);
    }

    public function test_not_enrolled_user() {
        global $CFG;
        require_once($CFG->libdir.'/adminlib.php');
        $this->setup_datas();
        $this->positioningmockdatas->create_courses();
        $this->positioningmockdatas->enrol_users();
        $this->positioningmockdatas->create_tests();
        $this->positioningmockdatas->pass_tests_as_positioning();
        $positioningtests = local_digital_training_account_services_external::get_positioning_tests(
            $this->positioningmockdatas->get_user1()->username);
        $this->assertTrue(is_array($positioningtests));
        $this->assertCount(0, $positioningtests);
    }

    private function setup_datas() {
        $this->resetAfterTest(true);
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.
        $this->setup_mockdatas();
        $this->setAdminUser();
        $this->positioningmockdatas->setup_users();
    }

    /**
     * @param $courseinfo
     * @param $CFG
     */
    private function check_datas($courseinfo) {
        global $CFG;
        $this->assertContains('upcomingEventsCount', array_keys($courseinfo));
        $this->assertContains('courseId', array_keys($courseinfo));
        $this->assertContains('shortName', array_keys($courseinfo));
        $this->assertContains('fullName', array_keys($courseinfo));
        $this->assertContains('roles', array_keys($courseinfo));
        $this->assertContains('groupedModulesCounters', array_keys($courseinfo));
        $this->assertContains('courseGrade', array_keys($courseinfo));
        $this->assertContains('completion', array_keys($courseinfo));
        $this->assertEquals($this->mockdatas->get_course1()->id, $courseinfo['courseId']);
        $this->assertEquals($this->mockdatas->get_course1()->shortname, $courseinfo['shortName']);
        $this->assertEquals($this->mockdatas->get_course1()->fullname, $courseinfo['fullName']);
        $this->assertCount(1, $courseinfo['roles']);
        $this->assertEquals('student', array_shift($courseinfo['roles']));
        $this->assertCount(3, $courseinfo['groupedModulesCounters']);
        $this->assertContains('other', array_keys($courseinfo['groupedModulesCounters']));
        $this->assertContains('msg', array_keys($courseinfo['groupedModulesCounters']));
        $this->assertContains('resource', array_keys($courseinfo['groupedModulesCounters']));
        $this->assertContains('finalGrade', array_keys($courseinfo['courseGrade']));
        $this->assertContains('maxGrade', array_keys($courseinfo['courseGrade']));
        $this->assertContains('percentage', array_keys($courseinfo['completion']));
        $this->assertContains('ratio', array_keys($courseinfo['completion']));
        $this->check_datas_in_details($courseinfo);
    }

    private function setup_mockdatas() {
        $this->positioningmockdatas = new positioning_mockdatas($this->getDataGenerator());
    }


    private function check_result_structure($quizinfos) {
        $quizinfoskeys = array_keys($quizinfos);
        $this->assertContains('id', $quizinfoskeys);
        $this->assertContains('name', $quizinfoskeys);
        $this->assertContains('grade', $quizinfoskeys);
        $this->assertContains('feedback', $quizinfoskeys);
        $this->assertContains('history', $quizinfoskeys);
        $this->assertTrue(is_array($quizinfos['history']));
        $this->assertContains('attemptsinfos', $quizinfoskeys);
        $this->assertTrue(is_array($quizinfos['attemptsinfos']));
        $attemptsinfoskeys = array_keys($quizinfos['attemptsinfos']);
        $this->assertContains('numberAllowedAttempts', $attemptsinfoskeys);
        $this->assertContains('alreadyMadeAttempts', $attemptsinfoskeys);
        $this->assertContains('hasOtherAttempts', $attemptsinfoskeys);
        $this->assertTrue($quizinfos['attemptsinfos']['hasOtherAttempts']);
        $this->assertContains('metadatas', $quizinfoskeys);
    }
}
