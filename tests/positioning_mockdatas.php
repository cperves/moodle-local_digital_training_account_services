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
global $CFG;
require_once(__DIR__.'/../locallib.php');
require_once($CFG->dirroot.'/mod/quiz/attemptlib.php');
require_once($CFG->dirroot.'/question/engine/lib.php');

class positioning_mockdatas {
    protected $user1;
    protected $user2;
    protected $course1;
    protected $course2;
    protected $quiz11;
    protected $quiz21;
    protected $quiz22;
    protected $metadatafield;
    protected $datagenerator;

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
    public function get_course2() {
        return $this->course2;
    }

    /**
     * @return mixed
     */
    public function get_quiz11() {
        return $this->quiz11;
    }

    /**
     * @return mixed
     */
    public function get_quiz21() {
        return $this->quiz21;
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
        global $OUTPUT;
        $this->datagenerator = $datagenerator;

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

    public function create_tests() {
        $layout = array(
                array('TF1', 1, 'truefalse'),
                array('TF2', 1, 'truefalse'));
        $this->quiz11 = $this->create_test_quiz($this->course1, $layout);
        $this->quiz21 = $this->create_test_quiz($this->course1, $layout);
        $this->quiz22 = $this->create_test_quiz($this->course1, $layout);
    }

    /**
     * create quiz
     * @param $courseid
     * @param $layout array( array(name, int, qtype),)
     * @throws \coding_exception
     */
    private function create_test_quiz($course, $layout) {
        // Make a quiz.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');

        $quiz = $quizgenerator->create_instance(array('course' => $course->id, 'questionsperpage' => 0,
                'grade' => 100.0, 'sumgrades' => 2, 'preferredbehaviour' => 'immediatefeedback', 'attempts' => 2));
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $headings = array();
        $slot = 1;
        $lastpage = 0;
        foreach ($layout as $item) {
            if (is_string($item)) {
                if (isset($headings[$lastpage + 1])) {
                    throw new coding_exception('Sections cannot be empty.');
                }
                $headings[$lastpage + 1] = $item;

            } else {
                list($name, $page, $qtype) = $item;
                if ($page < 1 || !($page == $lastpage + 1 ||
                                (!isset($headings[$lastpage + 1]) && $page == $lastpage))) {
                    throw new coding_exception('Page numbers wrong.');
                }
                $q = $questiongenerator->create_question($qtype, null,
                        array('name' => $name, 'category' => $cat->id));

                quiz_add_quiz_question($q->id, $quiz, $page);
                $lastpage = $page;
            }
        }
        $quizobj = new \quiz($quiz, $cm, $course);
        $structure = \mod_quiz\structure::create_for_quiz($quizobj);
        if (isset($headings[1])) {
            list($heading, $shuffle) = $this->parse_section_name($headings[1]);
            $sections = $structure->get_sections();
            $firstsection = reset($sections);
            $structure->set_section_heading($firstsection->id, $heading);
            $structure->set_section_shuffle($firstsection->id, $shuffle);
            unset($headings[1]);
        }

        foreach ($headings as $startpage => $heading) {
            list($heading, $shuffle) = $this->parse_section_name($heading);
            $id = $structure->add_section_heading($startpage, $heading);
            $structure->set_section_shuffle($id, $shuffle);
        }

        return $quizobj;
    }

    public function pass_tests_as_positioning() {
        $this->create_positioning_metadata_field();
        $metadatavalue = new \local_metadata_tools\database\metadata_value();
        $data = new \stdClass();
        $data->instanceid = $this->get_quiz11()->get_cmid();
        $data->fieldid = $this->metadatafield->get_id();
        $data->data = true;
        $metadatavalue->set_datas($data);
        $metadatavalue->save_datas();
        $metadatavalue = new \local_metadata_tools\database\metadata_value();
        $data->instanceid = $this->get_quiz21()->get_cmid();
        $metadatavalue->set_datas($data);
        $metadatavalue->save_datas();

    }

    public function attempt_submit($quizobj, $userid) {
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $attempts = quiz_get_user_attempts($quizobj->get_quizid(), $userid, 'finished', true);
        $attemptnumber = count($attempts);
        $attemptnumber ++;
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, $attemptnumber , false, $timenow, false, $userid);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        $attemptobj = \quiz_attempt::create($attempt->id);
        get_log_manager(true);
        $this->wait_for_second();
        $timefinish = time();
        $attemptobj->process_finish($timefinish, false);
        get_log_manager(true);
    }

    public function grade_quiz($cm, $userid, $finalgrade=null, $feedback= '') {
        global $DB;
        $gradeitem = \grade_item::fetch(array(
                'itemtype' => 'mod',
                'itemmodule' => 'quiz',
                'iteminstance' => $cm->instance,
                'courseid' => $cm->course
            ));
        $gradeitem->update_final_grade($userid, $finalgrade, 'unittest', $feedback, FORMAT_PLAIN);
        get_log_manager(true);
    }

    private function  fetch_grade_or_default($item, $userid) {
        $grade = grade_grade::fetch(array(
                'itemid' => $item->id, 'userid' => $userid
        ));
        if (!$grade) {
            $default = new stdClass;

            $default->userid = $userid;
            $default->itemid = $item->id;
            $default->feedback = '';
            $grade = new grade_grade($default, false);
        }

        $grade->grade_item = $item;
        return $grade;
    }

    public function enrol_users() {
        global $DB;
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $edititingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course1->id, $edititingteacherrole->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course2->id, $edititingteacherrole->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course2->id, $studentrole->id);
    }

    public function create_courses($options = array()) {
        $this->course1 = $this->getDataGenerator()->create_course($options);
        $this->course2 = $this->getDataGenerator()->create_course($options);
        // Retrieve log_manager and flush to trigger events.
        get_log_manager(true);
    }

    private function create_positioning_metadata_field() {
        $metadatacategory = new \local_metadata_tools\database\metadata_category;
        $data = new \stdClass();
        $data->contextlevel = CONTEXT_MODULE;
        $data->name = 'category';
        $data->sortorder = 1;
        $data->contextlevel = CONTEXT_MODULE;
        $metadatacategory->set_datas($data);
        $metadatacategory->save_datas();
        $metadatafield = new \local_metadata_tools\database\metadata_field;
        $data = new \stdClass();
        $data->shortname = 'eolepositioning';
        $data->name = 'Eole positioning';
        $data->datatype = 'checkbox';
        $data->contextlevel = CONTEXT_MODULE;
        $data->categoryid = $metadatacategory->get_id();
        $data->sortorder = 1;
        $metadatafield->set_datas($data);
        $metadatafield->save_datas();
        $this->metadatafield = $metadatafield;
    }

    public function wait_for_second() {
        $starttime = time();
        while (time() == $starttime) {
            usleep(50000);
        }
    }
}

