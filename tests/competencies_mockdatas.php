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
namespace local_digital_training_account_services;
use advanced_testcase;
use context_system;
use core_competency\api;
use core_competency\evidence;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once(__DIR__.'/../locallib.php');
require_once($CFG->dirroot.'/mod/quiz/attemptlib.php');
require_once($CFG->dirroot.'/question/engine/lib.php');

class competencies_mockdatas {
    protected $userteacher;
    protected $userstudent;
    protected $course1;
    protected $course2;
    protected $assign1;
    protected $assign2;
    protected $modulecompetency1;
    protected $modulecompetency2;
    protected $coursecompetency1;
    protected $coursecompetency2;
    protected $competency1;
    protected $competency2;
    protected $parentcompetency;

    protected $datagenerator;

    /**
     * @return mixed
     */
    public function get_assign1() {
        return $this->assign1;
    }

    /**
     * @return mixed
     */
    public function get_assign2() {
        return $this->assign2;
    }

    /**
     * @return mixed
     */
    public function get_userteacher() {
        return $this->userteacher;
    }

    /**
     * @return mixed
     */
    public function get_userstudent() {
        return $this->userstudent;
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
    public function get_course2() {
        return $this->course2;
    }

    /**
     * @return mixed
     */
    public function get_modulecompetency1() {
        return $this->modulecompetency1;
    }

    /**
     * @return mixed
     */
    public function get_modulecompetency2() {
        return $this->modulecompetency2;
    }

    /**
     * @return mixed
     */
    public function get_coursecompetency1() {
        return $this->coursecompetency1;
    }

    /**
     * @return mixed
     */
    public function get_coursecompetency2() {
        return $this->coursecompetency2;
    }

    /**
     * @return mixed
     */
    public function get_competency1() {
        return $this->competency1;
    }

    /**
     * @return mixed
     */
    public function get_competency2() {
        return $this->competency2;
    }



    /**
     * @return mixed
     */
    public function getDataGenerator() {
        return $this->datagenerator;
    }




    /**
     * moockdatas constructor.
     */
    public function __construct($datagenerator) {
        $this->datagenerator = $datagenerator;

    }

    private function setUser($userid) {
        advanced_testcase::setUser($userid);
    }
    private static function setAdminUser() {
        advanced_testcase::setUser(2);
    }

    public function initiate_tests() {
        $this->setAdminUser();
        $this->create_users();
        $this->create_courses();
        $this->enrol_users();
        $this->create_modules();
        $this->create_comptencies();
        get_log_manager(true);
    }

    private function create_users() {
        $this->userteacher = $this->getDataGenerator()->create_user();
        $this->userstudent = $this->getDataGenerator()->create_user();
    }

    private function create_courses() {
        $this->course1 = $this->getDataGenerator()->create_course();
        $this->course2 = $this->getDataGenerator()->create_course();
    }

    private function create_modules($options = array()) {
        $this->assign1 = $this->getDataGenerator()->create_module('assign', ['course' => $this->course1]);
        $this->assign2 = $this->getDataGenerator()->create_module('assign', ['course' => $this->course1]);
    }

    private function create_comptencies() {
        $lpg = $this->datagenerator->get_plugin_generator('core_competency');
        $framework = $lpg->create_framework();
        $this->parentcompetency = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id')));
        $this->competency1 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id'),
            'parentid' => $this->parentcompetency->get('id')));
        $this->competency2 = $lpg->create_competency(array('competencyframeworkid' => $framework->get('id'),
            'parentid' => $this->parentcompetency->get('id')));
        $this->coursecompetency1 = $lpg->create_course_competency(
            array('competencyid' => $this->competency1->get('id'), 'courseid' => $this->course1->id));
        $this->coursecompetency2 = $lpg->create_course_competency(
            array('competencyid' => $this->competency2->get('id'), 'courseid' => $this->course1->id));
        $this->modulecompetency1 = $lpg->create_course_module_competency(
            ['competencyid' => $this->competency1->get('id'), 'cmid' => $this->assign1->cmid]);
        $this->modulecompetency2 = $lpg->create_course_module_competency(
            ['competencyid' => $this->competency1->get('id'), 'cmid' => $this->assign2->cmid]);
        $this->usercompetency1 = $lpg->create_user_competency_course(
            ['competencyid' => $this->competency1->get('id'), 'courseid' => $this->course1->id,
                'userid' => $this->userstudent->id]);
        $this->usercompetency2 = $lpg->create_user_competency_course(
            ['competencyid' => $this->competency2->get('id'), 'courseid' => $this->course1->id,
                'userid' => $this->userstudent->id]);
    }

    public function grade_competencies() {
        api::grade_competency_in_course(
            $this->course1->id, $this->userstudent->id, $this->competency1->get('id'), 1, 'Note');
        get_log_manager(true);
    }


    public function enrol_users() {
        global $DB;
        $systemcontext = context_system::instance();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        // Add a capability for gradable.
        assign_capability('moodle/competency:coursecompetencygradable', CAP_ALLOW,
                $studentrole->id, $systemcontext->id, true);
        $edititingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($this->userteacher->id, $this->course1->id, $edititingteacherrole->id);
        $this->getDataGenerator()->enrol_user($this->userstudent->id, $this->course1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->userteacher->id, $this->course2->id, $edititingteacherrole->id);
        $this->getDataGenerator()->enrol_user($this->userstudent->id, $this->course2->id, $studentrole->id);
        accesslib_clear_all_caches_for_unit_testing();
    }


    public function wait_for_second() {
        $starttime = time();
        while (time() == $starttime) {
            usleep(50000);
        }
    }
}

