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
require_once($CFG->dirroot.'/local/digital_training_account_services/tests/competencies_mockdatas.php');
require_once($CFG->dirroot.'/local/metadata_tools/tests/mockdatas.php');

use advanced_testcase;
use context_system;
use local_digital_training_account_services\competencies_mockdatas;
use core_competency\api;
use local_digital_training_account_services_tools;
use required_capability_exception;

class competencies_test extends advanced_testcase{

    private $competenciesmockdatas;
    public function test_get_competencies_for_user() {
        $this->setup_datas();
        $competencies = local_digital_training_account_services_tools::get_competencies_for_user(
            $this->competenciesmockdatas->get_userstudent()->id);
        $this->assertTrue(is_array($competencies));
        $this->assertCount(2, $competencies);
        $competency1 = $competencies[
            $this->competenciesmockdatas->get_course1()->id.'_'.$this->competenciesmockdatas->get_competency1()->get('id')];
        $competency2 = $competencies[
            $this->competenciesmockdatas->get_course1()->id.'_'.$this->competenciesmockdatas->get_competency2()->get('id')];
        $this->check_structure($competency1);
        $this->check_structure($competency2);
        $this->assertCount(1, $competency1['ancestors']);
        $this->assertCount(1, $competency2['ancestors']);
        $this->assertEquals($this->competenciesmockdatas->get_course1()->id, $competency1['courseId']);
        $this->assertEquals(
            $this->competenciesmockdatas->get_course1()->id.'_'.$this->competenciesmockdatas->get_competency1()->get('id'),
            $competency1['id']);
        $this->assertEquals($this->competenciesmockdatas->get_competency1()->get('id'), $competency1['competencyId']);
        $this->assertEquals($this->competenciesmockdatas->get_course1()->id, $competency2['courseId']);
        $this->assertEquals(
            $this->competenciesmockdatas->get_course1()->id.'_'.$this->competenciesmockdatas->get_competency2()->get('id'),
            $competency2['id']);
        $this->assertEquals($this->competenciesmockdatas->get_competency2()->get('id'), $competency2['competencyId']);
        $this->assertEquals('-', $competency1['gradeName']);
        $this->assertEquals('-', $competency2['gradeName']);
        $this->assertNull($competency1['lastEvidenceNote']);
        $this->assertNull($competency2['lastEvidenceNote']);
        $this->competenciesmockdatas->grade_competencies();
        $competencies = local_digital_training_account_services_tools::get_competencies_for_user(
            $this->competenciesmockdatas->get_userstudent()->id);
        $competency1 = $competencies[
            $this->competenciesmockdatas->get_course1()->id.'_'.$this->competenciesmockdatas->get_competency1()->get('id')];
        $competency2 = $competencies[
            $this->competenciesmockdatas->get_course1()->id.'_'.$this->competenciesmockdatas->get_competency2()->get('id')];
        $this->assertEquals('-', $competency2['gradeName']);
        $this->assertNull($competency2['lastEvidenceNote']);
        $this->assertEquals('A', $competency1['gradeName']);
        $this->assertNotNull($competency1['lastEvidenceNote']);
        $this->assertEquals("Note", $competency1['lastEvidenceNote']);
    }

    public function test_get_competencies_for_capa_course_list_informations_for_other_user() {
        $this->setup_datas();
        $this->competenciesmockdatas->grade_competencies();
        $testuser = $this->getDataGenerator()->create_user();
        // Add first required capability.
        $this->setUser($testuser);
        $this->expectException(required_capability_exception::class);
        $this->expectExceptionMessage("Sorry, but you do not currently have permissions to do that (user can access to course list for other user).");
        $competencies = local_digital_training_account_services_tools::get_competencies_for_user(
            $this->competenciesmockdatas->get_userstudent()->id);
    }

    public function test_get_competencies_for_capa_usercompetencyview() {
        $this->setup_datas();
        $this->competenciesmockdatas->grade_competencies();
        $testuser = $this->getDataGenerator()->create_user();
        // Add first required capability.
        $this->expectException(required_capability_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (user can access to course list for other user).');
        $this->setUser($testuser);
        local_digital_training_account_services_tools::get_competencies_for_user(
            $this->competenciesmockdatas->get_userstudent()->id);
    }


    public function test_get_competencies_for_all_capas() {
        $this->setup_datas();
        $this->competenciesmockdatas->grade_competencies();
        $systemcontext = context_system::instance();
        $newroleid = $this->getDataGenerator()->create_role('newrole');
        $testuser = $this->getDataGenerator()->create_user();
        // Required capabilities.
        assign_capability('local/digital_training_account_services:course_list_informations_for_other_user',
            CAP_ALLOW,
            $newroleid, $systemcontext->id, true);
        assign_capability('moodle/competency:usercompetencyview',
            CAP_ALLOW, $newroleid, $systemcontext->id, true);
        role_assign($newroleid, $testuser->id, $systemcontext->id);
        $this->setUser($testuser);
        local_digital_training_account_services_tools::get_competencies_for_user(
            $this->competenciesmockdatas->get_userstudent()->id);
    }



    private function setup_datas() {
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.
        $this->setup_mockdatas();
        $this->setAdminUser();
        $this->competenciesmockdatas->initiate_tests();
    }

    private function setup_mockdatas() {
        $this->competenciesmockdatas = new competencies_mockdatas($this->getDataGenerator());
    }

    /**
     * @param $competency
     */
    private function check_structure($competency): void {
        $this->assertTrue(array_key_exists('id', $competency));
        $this->assertTrue(array_key_exists('courseId', $competency));
        $this->assertTrue(array_key_exists('userId', $competency));
        $this->assertTrue(array_key_exists('competencyId', $competency));
        $this->assertTrue(array_key_exists('shortName', $competency));
        $this->assertTrue(array_key_exists('idNumber', $competency));
        $this->assertTrue(array_key_exists('gradeName', $competency));
        $this->assertTrue(array_key_exists('lastEvidenceNote', $competency));
        $this->assertTrue(array_key_exists('framework', $competency));
        $this->assertTrue(array_key_exists('ancestors', $competency));
        $this->assertTrue(is_array($competency['ancestors']));
        $framework = $competency['framework'];
        $this->assertTrue(array_key_exists('shortName', $framework));
        $this->assertTrue(array_key_exists('idNumber', $framework));
    }
}