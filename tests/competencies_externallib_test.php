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
use external_api;
use local_digital_training_account_services\competencies_mockdatas;
use core_competency\api;
use local_digital_training_account_services_external;

class competencies_externallib_test extends advanced_testcase{

    private $competenciesmockdatas;
    public function test_get_competencies_for_user() {
        $this->setup_datas();
        $this->setUser($this->competenciesmockdatas->get_userteacher());
        $this->competenciesmockdatas->grade_competencies();
        // Set web service user.
        $systemcontext = context_system::instance();
        $wsroleid = $this->getDataGenerator()->create_role('newrole');
        $wsuser = $this->getDataGenerator()->create_user();
        // Add first required capability.
        assign_capability('local/digital_training_account_services:course_list_informations_for_other_user', CAP_ALLOW,
                $wsroleid, $systemcontext->id, true);
        assign_capability('moodle/competency:usercompetencyview', CAP_ALLOW, $wsroleid, $systemcontext->id, true);
        role_assign($wsroleid, $wsuser->id, $systemcontext->id);
        $this->setUser($wsuser);
        $competencies = local_digital_training_account_services_external::get_competencies(
            $this->competenciesmockdatas->get_userstudent()->username);
        external_api::clean_returnvalue(
            local_digital_training_account_services_external::get_competencies_returns(), $competencies);
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
        $this->assertTrue(array_key_exists('shortname', $competency));
        $this->assertTrue(array_key_exists('idnumber', $competency));
        $this->assertTrue(array_key_exists('grade', $competency));
        $this->assertTrue(array_key_exists('gradename', $competency));
        $this->assertTrue(array_key_exists('lastEvidenceNote', $competency));
    }
}