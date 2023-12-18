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

use context_system;
use external_api;
use externallib_advanced_testcase;
use local_digital_training_account_services\mockdatas;
use local_digital_training_account_services_external;
use required_capability_exception;

class externallib_test extends externallib_advanced_testcase{

    public function test_get_courses_infos_externallib() {
        $this->setup_datas();
        $this->mockdatas->setup_externallib_datas();
        // Launched with concerned user i.e user2 => will see everything.
        $this->setAdminUser();
        $coursesinfos = local_digital_training_account_services_external::get_courses_infos(
            $this->mockdatas->get_user2()->username);
        $coursesinfos = external_api::clean_returnvalue(
            local_digital_training_account_services_external::get_courses_infos_returns(), $coursesinfos);
        $this->assertCount(1, $coursesinfos);
        $courseinfo = array_pop($coursesinfos);
        $this->check_datas($courseinfo);

    }

    public function test_get_courses_infos_externallib_admin() {
        $this->setup_datas();
        $this->mockdatas->setup_externallib_datas();
        // Launched by admin.
        $this->setAdminUser();
        $coursesinfos = local_digital_training_account_services_external::get_courses_infos(
            $this->mockdatas->get_user2()->username);
        $coursesinfos = external_api::clean_returnvalue(
            local_digital_training_account_services_external::get_courses_infos_returns(), $coursesinfos);
        $this->assertCount(1, $coursesinfos);
        $courseinfo = array_pop($coursesinfos);
        $this->check_datas($courseinfo);

    }

    public function test_get_courses_infos_externallib_missing_capability() {
        $this->setAdminUser();
        $this->setup_datas();
        $this->mockdatas->setup_externallib_datas();
        $testuser = $this->getDataGenerator()->create_user();
        $this->setUser($testuser);
        $this->expectException(required_capability_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (user can access to course list for other user).');
        local_digital_training_account_services_external::get_courses_infos($this->mockdatas->get_user2()->username);
    }

    public function test_get_courses_infos_externallib_having_capability() {
        global $CFG;
        $systemcontext = context_system::instance();
        $this->setup_datas();
        $this->setAdminUser();
        $this->mockdatas->setup_externallib_datas();
        $rolewsid = $this->getDataGenerator()->create_role('WS');
        $testuser = $this->getDataGenerator()->create_user();
        // Add first required capability.
        assign_capability('local/digital_training_account_services:course_list_informations_for_other_user', CAP_ALLOW,
            $rolewsid, $systemcontext->id, true);
        role_assign($rolewsid, $testuser->id, $systemcontext->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($testuser);
        $coursesinfos = local_digital_training_account_services_external::get_courses_infos(
            $this->mockdatas->get_user2()->username);
        $this->assertCount(1, $coursesinfos);
        $courseinfo = array_pop($coursesinfos);
        $this->check_datas($courseinfo, $CFG);
    }

    private function setup_mockdatas() {
        $this->mockdatas = new mockdatas($this->getDataGenerator());
    }
    private function setup_datas() {
        $this->resetAfterTest(true);
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.
        $this->setup_mockdatas();
        $this->setAdminUser();
        $this->mockdatas->setup_users();
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
        $groupedModulesCounterselt = $courseinfo['groupedModulesCounters'][0];
        $this->assertContains('groupName', array_keys($groupedModulesCounterselt));
        $this->assertContains('count', array_keys($groupedModulesCounterselt));
        $this->assertContains('finalGrade', array_keys($courseinfo['courseGrade']));
        $this->assertContains('maxGrade', array_keys($courseinfo['courseGrade']));
        $this->assertContains('percentage', array_keys($courseinfo['completion']));
        $this->assertContains('ratio', array_keys($courseinfo['completion']));
        $this->check_datas_in_details($courseinfo);
    }

    private function check_datas_in_details($courseinfo) {
        global $CFG;
        foreach ($courseinfo['groupedModulesCounters'] as $groupedmodulescounter) {
            switch($groupedmodulescounter['groupName']) {
                case 'other':
                    $this->assertEquals(0, $groupedmodulescounter['count']);
                    break;
                case 'resource':
                    $this->assertEquals(0, $groupedmodulescounter['count']);
                    break;
                case 'msg':
                    $this->assertEquals(2, $groupedmodulescounter['count']);
                    break;
            }
        }
        $this->assertEquals(60.0, $courseinfo['courseGrade']['finalGrade']);
        $this->assertEquals(100.0, $courseinfo['courseGrade']['maxGrade']);
        $this->assertEquals(33, floor($courseinfo['completion']['percentage']));
        $this->assertEquals('2/6', $courseinfo['completion']['ratio']);
    }
}
