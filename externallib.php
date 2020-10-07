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
 * local digital training account services web services implementation
 *
 * @package    local_digital_training_account_services
 * @author Céline Pervès <cperves@unistra.fr>
 * @copyright Université de Strasbourg 2020 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/local/digital_training_account_services/locallib.php");

class local_digital_training_account_services_external extends external_api{
    public static function get_courses_infos($susername, $timelimit=null) {
        global $DB, $CFG;
        $params = self::validate_parameters(self::get_courses_infos_parameters(),
            array(  'username' => $susername,
                    'timelimit' => $timelimit
            )
        );
        // Check that user ecxists.
        $user = $DB->get_record('user', array('username' => $susername));
        if (!$user) {
            throw new local_digital_training_account_services_exception('notexistinguser');
        }
        return local_digital_training_account_services_tools::get_courses_informations($user, $timelimit);
    }
    public static function get_courses_infos_parameters() {
        return new external_function_parameters(
            array(
                'username' => new external_value(PARAM_TEXT, 'username'),
                'timelimit' => new external_value(PARAM_INT, 'timelimit', VALUE_DEFAULT, null)
            )
        );
    }
    public static function get_courses_infos_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'courseId'     => new external_value(PARAM_INT, 'internal courseid', VALUE_REQUIRED),
                    'shortName'     => new external_value(PARAM_TEXT, 'course shortname', VALUE_REQUIRED),
                    'fullName'     => new external_value(PARAM_TEXT, 'course fullname', VALUE_REQUIRED),
                    'roles'     => new external_multiple_structure(
                        new external_value(PARAM_TEXT, 'course roles for user', VALUE_REQUIRED)
                    ),
                    'groupedModulesCounters'     => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'groupName' => new external_value(PARAM_TEXT, 'groupName', VALUE_REQUIRED),
                                    'count' => new external_value(PARAM_INT, 'count', VALUE_REQUIRED),
                                )
                        )
                    ),
                    'courseGrade'     => new external_single_structure(
                        array(
                            'finalGrade' => new external_value(PARAM_FLOAT, 'final course grade', VALUE_REQUIRED),
                            'maxGrade' => new external_value(PARAM_FLOAT, 'course max grade', VALUE_REQUIRED),
                        )
                    ),
                    'completion'     => new external_single_structure(
                        array(
                            'percentage' => new external_value(PARAM_FLOAT, 'course completion percentage', VALUE_REQUIRED),
                            'ratio' => new external_value(PARAM_TEXT, 'course completion ratio', VALUE_REQUIRED),
                        )
                    ),
                    'upcomingEventsCount'     => new external_value(PARAM_INT, 'upcomingEventsCount', VALUE_REQUIRED)
                )
            )
        );
    }

    public static function get_positioning_tests($username) {
        global $DB;
        $params = self::validate_parameters(self::get_positioning_tests_parameters(),
                array(  'username' => $username)
        );
        // Check that user ecxists.
        $user = $DB->get_record('user', array('username' => $username));
        if (!$user) {
            throw new local_digital_training_account_services_exception('notexistinguser');
        }
        return local_digital_training_account_services_tools::get_positionning_tests_for_user($user->id);
    }

    public static function get_positioning_tests_parameters() {
        return new external_function_parameters(
                array(
                    'username' => new external_value(PARAM_TEXT, 'username'),
                )
            , VALUE_DEFAULT, []);
    }
    public static function get_positioning_tests_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'     => new external_value(PARAM_INT, 'internal quiz id', VALUE_REQUIRED),
                    'name'     => new external_value(PARAM_TEXT, 'quiz name', VALUE_REQUIRED),
                    'grade' => new external_value(PARAM_TEXT, 'quiz grade', VALUE_REQUIRED),
                    'feedback' => new external_value(PARAM_RAW, 'quiz feedback', VALUE_REQUIRED),
                    'history'     => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'id' => new external_value(PARAM_TEXT, 'groupName', VALUE_REQUIRED),
                                'mark' => new external_value(PARAM_TEXT, 'attenpt grade', VALUE_REQUIRED),
                                'time' => new external_value(PARAM_INT, 'time', VALUE_REQUIRED),
                            )
                        )
                    ),
                    'attemptsinfos'     => new external_single_structure(
                        array(
                            'numberAllowedAttempts' => new external_value(PARAM_INT,
                                'number allowed attempts for this quiz', VALUE_REQUIRED),
                            'alreadyMadeAttempts' => new external_value(PARAM_INT,
                                ',number of already made attempts', VALUE_REQUIRED),
                            'hasOtherAttempts' => new external_value(PARAM_BOOL,
                                'has other attempts possibility', VALUE_REQUIRED),
                        )
                    ),
                    'metadatas' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'fieldId' => new external_value(PARAM_INT, 'metadata field id', VALUE_REQUIRED),
                                'fieldName' => new external_value(PARAM_TEXT, 'metadata field name', VALUE_REQUIRED),
                                'value' => new external_value(PARAM_CLEANHTML, 'metadata field value', VALUE_REQUIRED),
                            )
                        )
                    )
                )
            )
        );
    }

    public static function get_competencies($username) {
        global $DB;
        $params = self::validate_parameters(self::get_positioning_tests_parameters(),
                array(  'username' => $username)
        );
        // Check that user exists.
        $user = $DB->get_record('user', array('username' => $username));
        if (!$user) {
            throw new local_digital_training_account_services_exception('notexistinguser');
        }
        return local_digital_training_account_services_tools::get_competencies_for_user($user->id);
    }

    public static function get_competencies_parameters() {
        return new external_function_parameters(
            array(
                    'username' => new external_value(PARAM_TEXT, 'username'),
            )
            , VALUE_DEFAULT, []);
    }
    public static function get_competencies_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'     => new external_value(PARAM_TEXT, 'unique id :  courseid_competencyid', VALUE_REQUIRED),
                    'courseId'     => new external_value(PARAM_INT, 'course id', VALUE_REQUIRED),
                    'courseShortName' => new external_value(PARAM_TEXT, 'course short name', VALUE_REQUIRED),
                    'userId'     => new external_value(PARAM_INT, 'user id for url construction', VALUE_REQUIRED),
                    'competencyId'     => new external_value(PARAM_INT, 'competency id', VALUE_REQUIRED),
                    'shortName'     => new external_value(PARAM_TEXT, 'competency short name', VALUE_REQUIRED),
                    'idNumber'     => new external_value(PARAM_TEXT, 'competency id number', VALUE_REQUIRED),
                    'gradeName' => new external_value(PARAM_TEXT, 'grade name composed from grade and scale', VALUE_REQUIRED),
                    'framework'     => new external_single_structure(
                        array(
                            'shortName' => new external_value(PARAM_TEXT, 'competency framework short name', VALUE_REQUIRED),
                            'idNumber' => new external_value(PARAM_TEXT, 'competency framework id number', VALUE_REQUIRED),
                        )
                    ),
                    'ancestors' => new external_multiple_structure(
                            new external_value(PARAM_TEXT, 'competency ancestor', VALUE_REQUIRED)
                    )
                    ,
                    'lastEvidenceNote' => new external_value(PARAM_TEXT, 'evidency id', VALUE_REQUIRED),
                )
            )
        );
    }
}