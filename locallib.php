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
 * local digital training account local
 * library
 *
 * @package    local_digital_training_account_services
 * @author Céline Pervès <cperves@unistra.fr>
 * @copyright Université de Strasbourg 2020 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
use core_competency\api;
use core_competency\external\performance_helper;
use core_competency\user_competency;
use tool_lp\external;

global $CFG;
require_once("$CFG->dirroot/enrol/externallib.php");
require_once("$CFG->libdir/completionlib.php");


/**
 * Class digital_training_account_services_exception
 */
class local_digital_training_account_services_exception extends moodle_exception{
    /**
     * digital_training_account_services_exception constructor.
     * @param $errorcode
     * @param null $a
     * @param null $debuginfo
     */

    public function __construct($errorcode, $a = null, $debuginfo = null) {
        parent::__construct($errorcode, 'local_digital_training_account_services', '', $a, $debuginfo);
    }
}

class local_digital_training_account_services_tools {

    public const OTHER_GROUP = 'other';
    public const LOCAL_DIGITAL_TRAINING_ACCOUNT_SERVICES_ROLE = 'digital_training_account_services_ws';
    public const LOCAL_DIGITAL_TRAINING_ACCOUNT_SERVICES_DEFAULT_USER = 'digital_training_account_services_user';

    public static function is_last_updated_course_logstore_enabled() {
        return self::is_logstore_enabled('logstore_last_updated_course_module');
    }

    public static function is_last_viewed_course_logstore_enabled() {
        return self::is_logstore_enabled('logstore_last_viewed_course_module');
    }

    private static function is_logstore_enabled($store) {
        $enabled = get_config('tool_log', 'enabled_stores');
        return in_array($store, explode(',', $enabled));
    }
    /**
     * @param $user
     * @throws dml_exception
     */
    public static function get_courses_informations($user, $timelimit=null) {
        $coursesinformations = array();
        $systemcontext = context_system::instance();
        require_capability('local/digital_training_account_services:course_list_informations_for_other_user', $systemcontext);
        $courses = enrol_get_users_courses($user->id, true, 'id, shortname, fullname, idnumber, visible,
                   summary, summaryformat, format, showgrades, lang, enablecompletion, category, startdate, enddate');
        foreach ($courses as $course) {
            // Now we'll complete all informations.
            $coursesinformations[$course->id] = self::get_course_all_informations($course, $user->id, $timelimit);
        }
        return $coursesinformations;
    }
    private static function get_course_all_informations($course, $userid, $timelimit) {
        $courseinfo = self::get_course_info($course, $userid);
        $courseinfo['groupedModulesCounters'] = self::get_course_counters_grouped($course, $userid);
        $courseinfo['courseGrade'] = self::get_course_final_grade($course, $userid);
        $courseinfo['completion'] = self::get_course_completion($course, $userid);
        $courseinfo['upcomingEventsCount'] = self::get_course_calendar_events_count($course, $userid, $timelimit);
        return $courseinfo;
    }

    public static function get_course_completion($course, $userid) {
        if (!$course->visible) {
            array('percentage' => null, 'ratio' => null);
        }
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            array('percentage' => null, 'ratio' => null);
        }
        $percentage = \core_completion\progress::get_course_progress_percentage($course, $userid); // Return null if not enabled.
        if (!is_null($percentage)) {
            $percentage = $percentage;
        }
        $ratio = self::get_course_progress_ratio($course, $userid);
        return array('percentage' => $percentage, 'ratio' => $ratio);
    }

    public static function get_course_progress_ratio($course, $userid) {
        $completion = new \completion_info($course);
        // First, let's make sure completion is enabled.
        if (!$completion->is_enabled()) {
            return null;
        }
        // Get the number of modules that support completion.
        $modules = $completion->get_activities();
        $count = count($modules);
        if (!$count) {
            return 0;
        }
        // Get the number of modules that have been completed.
        $completed = 0;
        foreach ($modules as $module) {
            $data = $completion->get_data($module, true, $userid);
            $completed += $data->completionstate == COMPLETION_INCOMPLETE ? 0 : 1;
        }

        return "$completed/$count";
    }

    public static function get_course_final_grade($course, $userid) {
        global $DB;
        $finalgraderecord = false;
        if ($course->visible && $course->showgrades) {
            $sql = 'select finalgrade, rawgrademax from {grade_items} gi inner join {grade_grades} gg on gg.itemid=gi.id
                        where gi.courseid=:courseid and gg.userid=:userid and gi.itemtype=\'course\'';
            $params = array(
                    'courseid' => $course->id,
                    'userid' => $userid
            );
            $finalgraderecord = $DB->get_record_sql($sql, $params);
        }
        $finalgrade = (!$finalgraderecord ? null : ($finalgraderecord->finalgrade === '' ? null : $finalgraderecord->finalgrade));
        $maxgrade = (!$finalgraderecord ? null : ($finalgraderecord->rawgrademax === '' ? null : $finalgraderecord->rawgrademax));
        return array( 'finalGrade' => $finalgrade, 'maxGrade' => $maxgrade);
    }

    private static function get_course_info($course, $userid) {
        $infos = array();
        $infos['courseId'] = $course->id;
        $infos['shortName'] = $course->shortname;
        $infos['fullName'] = $course->fullname;
        $roles = get_user_roles(context_course::instance($course->id), $userid);
        $rolesshortnames = array();
        foreach ($roles as $role) {
            $rolesshortnames[$role->shortname] = $role->shortname;
        }
        $infos['roles'] = $rolesshortnames;
        return $infos;
    }

    public static function get_course_calendar_events_count($course, $userid, $timelimit) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/calendar/lib.php');
        require_once($CFG->dirroot.'/calendar/classes/local/event/container.php');
        $calendarlimitnumber =
                get_config('local_digital_training_account_services', 'calendarlimitnumber');
        $calendarlimitnumber = (int) $calendarlimitnumber;
        $vault = \core_calendar\local\event\container::get_event_vault();
        \core_calendar\local\event\container::set_requesting_user($userid);
        $user = $DB->get_record('user', array('id' => $userid));
        $currentime = time();
        $timeto = is_null($timelimit) ? null : $currentime + $timelimit;
        $events = $vault->get_action_events_by_course($user, $course, $currentime, $timeto, null, $calendarlimitnumber );
        //calendar events
        $groupings = groups_get_user_groups($course->id, $user->id);
        $stdevents =  array_values(
            $vault->get_events(
                null,
                null,
                null,
                $timeto,
                null,
                null,
                $calendarlimitnumber,
                CALENDAR_EVENT_TYPE_STANDARD,
                [$user->id],
                $groupings[0] ? $groupings[0] : null,
                [$course->id],
                [],
                true,
                true,
                function ($event) use ($course) {
                    return $event instanceof \core_calendar\local\event\entities\event && $event->get_course()->get('id') == $course->id;
                }
            )
        );
        $events = array_merge($events, $stdevents);
        return count($events);
    }

    public static function get_course_counters_grouped($course, $userid) {
        global $CFG;
        require_once($CFG->dirroot.'/local/digital_training_account_services/hooklib.php');
        require_once($CFG->dirroot.'/availability/classes/info_module.php');
        require_once($CFG->dirroot.'/availability/tests/fixtures/mock_condition.php');

        $coursecontext = context_course::instance($course->id);
        $counterhookedmodulesstr = get_config('local_digital_training_account_services', 'counterhookedmodules');
        $counterhookedmodules = explode(',', $counterhookedmodulesstr);
        $moduleandgroups = json_decode(get_config('local_digital_training_account_services', 'modulegrouped') ?? '', true);
        $modulegroups = $moduleandgroups['modulegroups'];
        $groupmodules = $moduleandgroups['groupmodules'];
        $groupcounters = self::init_group_counters($groupmodules);
        $rawmodules = get_course_mods($course->id);
        foreach ($rawmodules as $rawmodule) {
            if (empty($rawmodule->deletioninprogress)
                    && (!empty($rawmodule->visible)
                            || has_capability('moodle/course:viewhiddenactivities', $coursecontext, $userid))
                    && \core_availability\info_module::is_user_visible($rawmodule->id, $userid)) {
                $increment = 0;
                // Currentmodule has changed.
                if (!in_array('mod_' . $rawmodule->modname, $counterhookedmodules)) {
                    $increment = self::module_has_changed($rawmodule->id, $userid) ? 1 : 0;
                } else {
                    $functionname = 'mod_' . $rawmodule->modname . '_counter_special_implementation';
                    if (function_exists($functionname)) {
                        $increment = $functionname($rawmodule->id, $userid);
                    }
                }
                if ($increment) {
                    if (array_key_exists($rawmodule->modname, $modulegroups)) {
                        foreach ($modulegroups[$rawmodule->modname] as $group) {
                            self::increment_group($group, $groupcounters, $increment);
                        }
                        // One or more groups.
                    } else {
                        // Group is other one.
                        self::increment_group(self::OTHER_GROUP, $groupcounters, $increment);
                    }
                }
            }
        }
        $normalizedgroupcounters = array();
        foreach ($groupcounters as $index => $groupcounter) {
            $normalizedgroupcounters[] = array('groupName' => $groupcounter->get_counter_name(),
                'count' => $groupcounter->get_counter());
        }
        return  $normalizedgroupcounters;
    }

    public static function module_has_changed($cmid, $userid, $context=null) {
        global $DB;
        // Unloggedeventsasnew , old events not already in og as considered as new.
        $unloggedasnew = (boolean)get_config('local_digital_training_account_services', 'unloggedasnew');
        $viewed = $DB->get_record('logstore_lastviewed_log', array('userid' => $userid, 'cmid' => $cmid));
        $updated = $DB->get_record('logstore_lastupdated_log', array('cmid' => $cmid));
        if (!$updated) {
            // Never create or updated, maybe done before log tracking.
            return !$unloggedasnew ? false : (!$viewed ? true : false);
        }
        if (!$viewed) {
            return true;
        }
        // Possible update case.
        if ($updated->lasttimeupdated > $viewed->lasttimeviewed ) {
            return true;
        }
        return false;
    }

    /**
     * @param $group
     * @param array $groupcounters
     * @return array
     */
    private static function increment_group($group, &$groupcounters, $increment=1) {
        $groupcounters[$group]->increment($increment);
    }

    private static function init_group_counters($groupmodules) {
        $groupcounters = array();
        $groupcounters[self::OTHER_GROUP] = new local_digital_training_account_services_counter(self::OTHER_GROUP);
        foreach (array_keys($groupmodules) as $group) {
            $groupcounters[$group] = new local_digital_training_account_services_counter($group);
        }
        return $groupcounters;
    }

    public static function install_webservice_completely() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/webservice/lib.php');
        $systemcontext = context_system::instance();
        $rolerecord = $DB->get_record('role', array('shortname' => self::LOCAL_DIGITAL_TRAINING_ACCOUNT_SERVICES_ROLE));
        $wsroleid = 0;
        if ($rolerecord) {
            $wsroleid = $rolerecord->id;
            cli_writeln('role '.self::LOCAL_DIGITAL_TRAINING_ACCOUNT_SERVICES_ROLE.' already exists, we\'ll use it');
        } else {
            $wsroleid = create_role(self::LOCAL_DIGITAL_TRAINING_ACCOUNT_SERVICES_ROLE,
                self::LOCAL_DIGITAL_TRAINING_ACCOUNT_SERVICES_ROLE,
                self::LOCAL_DIGITAL_TRAINING_ACCOUNT_SERVICES_ROLE);
        }
        assign_capability('local/digital_training_account_services:course_list_informations_for_other_user', CAP_ALLOW,
                $wsroleid, $systemcontext->id, true);
        assign_capability('local/digital_training_account_services:positioning_tests_list_informations_for_other_user', CAP_ALLOW,
                $wsroleid, $systemcontext->id, true);
        assign_capability('moodle/competency:usercompetencyview', CAP_ALLOW,
                $wsroleid, $systemcontext->id, true);
        // Allow role assignmrnt on system.
        set_role_contextlevels($wsroleid, array(10 => 10));
        $wsuser = $DB->get_record('user', array('username' => self::LOCAL_DIGITAL_TRAINING_ACCOUNT_SERVICES_DEFAULT_USER));
        if (!$wsuser) {
            $wsuser = create_user_record(self::LOCAL_DIGITAL_TRAINING_ACCOUNT_SERVICES_DEFAULT_USER, generate_password(20));
            $wsuser->firstname = 'wsuser';
            $wsuser->lastname = self::LOCAL_DIGITAL_TRAINING_ACCOUNT_SERVICES_DEFAULT_USER;
            $wsuser->email = 'ws_dtas'.$CFG->noreplyaddress;
            $DB->update_record('user', $wsuser);
        } else {
            cli_writeln('user '.self::LOCAL_DIGITAL_TRAINING_ACCOUNT_SERVICES_DEFAULT_USER.'already exists, we\'ll use it');
        }
        role_assign($wsroleid, $wsuser->id, $systemcontext->id);
        $service = $DB->get_record('external_services', array('shortname' => 'wsdigitaltrainingaccountservice'));
        // Assign user to webservice.
        $webservicemanager = new webservice();
        $serviceuser = new stdClass();
        $serviceuser->externalserviceid = $service->id;
        $serviceuser->userid = $wsuser->id;
        $webservicemanager->add_ws_authorised_user($serviceuser);

        $params = array(
                'objectid' => $serviceuser->externalserviceid,
                'relateduserid' => $serviceuser->userid
        );
        $event = \core\event\webservice_service_user_added::create($params);
        $event->trigger();
        return true;
    }


    /**
     * retireve position tests included in courses where user ins enrolled in
     * @throws dml_exception
     */
    public static function get_positionning_tests_for_user($userid) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/metadata_tools/lib.php');
        require_once($CFG->dirroot.'/local/metadata_tools/classes/database/metadata_value.php');
        require_once($CFG->dirroot.'/mod/quiz/attemptlib.php');
        $systemcontext = context_system::instance();
        require_capability('local/digital_training_account_services:positioning_tests_list_informations_for_other_user',
            $systemcontext);
        $positioningmetadatafield = get_config('local_digital_training_account_services', 'positioning_metadata_field');
        if (! isset( $positioningmetadatafield) || empty($positioningmetadatafield)) {
            throw new local_digital_training_account_services_exception('empty_positioning_metadatafield');
        }
        $positiongtests = array();
        $allpositiongtests = local_metadata_tools::get_contextids_for_contextlevel_field_and_value(
                CONTEXT_MODULE, $positioningmetadatafield, 1);
        foreach ($allpositiongtests as $positiongtest) {
            $cm = $DB->get_record('course_modules', array('id' => $positiongtest->instanceid));
            if (!$cm) {
                throw new local_digital_training_account_services_exception('positioning_unrecognized_cm');
            }
            $modulecontext =  \context_module::instance($cm->id);
            $coursecontext = context_course::instance($cm->course);
            if (is_enrolled(context_course::instance($cm->course), $userid)
                    && has_capability('mod/quiz:attempt', $modulecontext, $userid)
                    && empty($cm->deletioninprogress)
                    && (!empty($cm->visible)
                            || has_capability('moodle/course:viewhiddenactivities', $coursecontext, $userid))
                    && \core_availability\info_module::is_user_visible($cm->id, $userid)
            ) {
                $course = $DB->get_record('course', array('id' => $cm->course));
                $quiz = $DB->get_record('quiz', array('id' => $cm->instance));
                $quizobj = new \quiz($quiz, $cm, $course);
                $positiongtests[$cm->id] = array_merge(self::get_quiz_infos($quizobj, $userid),
                    self::get_metadatas_values($modulecontext->id, 'eole', [], ['eolepositioning']));
            }
        }
        return $positiongtests;
    }

    public static function get_competencies_for_user($userid) {
        global $CFG;
        require_once($CFG->dirroot.'/competency/classes/api.php');
        $systemcontext = context_system::instance();
        require_capability('local/digital_training_account_services:course_list_informations_for_other_user', $systemcontext);
        $courses = enrol_get_users_courses($userid, true, 'id, shortname, fullname, idnumber, visible,
                   summary, summaryformat, format, showgrades, lang, enablecompletion, category, startdate, enddate');
        $coursecompetencies = array();
        $helper = new performance_helper();
        foreach ($courses as $course) {
            // Now we'll complete all informations.
            $usercompetencies = \core_competency\api::list_user_competencies_in_course($course->id, $userid);
            foreach ($usercompetencies as $usercompetency) {
                $competencyid = $usercompetency->get('competencyid');
                $competency = api::read_competency($competencyid);
                $competencyframework = $helper->get_framework_from_competency($competency);
                $scale = $helper->get_scale_from_competency($competency);
                $usergrade = $usercompetency->get('grade');
                $gradename = '-';
                if (!is_null($usergrade)) {
                    $gradename = $scale->scale_items[$usergrade - 1];
                }
                $competencyinfos = array();
                $competencyinfos['id'] = $course->id.'_'.$competency->get('id');
                $competencyinfos['courseId'] = $course->id;
                $competencyinfos['courseShortName'] = $course->shortname;
                $competencyinfos['userId'] = $userid;
                $competencyinfos['competencyId'] = $competency->get('id');
                $competencyinfos['shortName'] = $competency->get('shortname');
                $competencyinfos['idNumber'] = $competency->get('idnumber');
                $competencyinfos['gradeName'] = $gradename;
                $competencyinfos['framework'] = array(
                        'shortName' => $competencyframework->get('shortname'),
                        'idNumber' => $competencyframework->get('idnumber')
                );
                $ancestorsobjects = $competency->get_ancestors();
                $ancestors = [];
                foreach ($ancestorsobjects as $ancestorsobject) {
                    $ancestors[] = $ancestorsobject->get('shortname');
                }
                $competencyinfos['ancestors'] = $ancestors;

                $evidencies = api::list_evidence($userid, $usercompetency->get('competencyid'));// Timecreated order.
                $evidence = array_pop($evidencies);
                $competencyinfos['lastEvidenceNote'] = null;
                if (!empty($evidence)) {
                    $competencyinfos['lastEvidenceNote'] = $evidence->get('note');
                }
                $coursecompetencies[$course->id.'_'.$competency->get('id')] = $competencyinfos;
            }

        }
        return $coursecompetencies;
    }

    private static function user_prefered_language($userid) {
        global $DB;
        $user = $DB->get_record('user', array('id' => $userid));
        if ($user && !empty($user->lang)) {
            return $user->lang;
        }
        return core_user::get_property_default('lang');
    }

    /**
     * @param $quizobj quiz class object
     * @param $cm
     * @param $userid
     * @return array
     * @throws coding_exception
     */
    public static function get_quiz_infos($quizobj, $userid) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        $quizinfo = [];
        $quizinfo['id'] = $quizobj->get_cmid();
        $quizinfo['name'] = $quizobj->get_quiz_name();
        list($grade, $feedback) = self::get_quiz_grade_feedback($quizobj, $userid);
        $quizinfo['grade'] = $grade;
        list($quizinfo['feedback'], $quizinfo['history']) = self::get_quiz_attempts($quizobj, $userid);
        if (!empty($feedback)) {
            $quizinfo['feedback'] = $feedback;
        }
        $quizinfo['attemptsinfos'] = self::get_quiz_attempts_infos($userid, $quizobj);
        return $quizinfo;
    }


    /**
     * @param $userid
     * @param $quizobj
     * @param $cm
     * @return array
     * @throws coding_exception
     */
    private static function get_quiz_grades($userid, $quizobj, $cm) {
        $grades = grade_get_grades($cm->course, 'mod', 'quiz', $cm->instance, $userid);
        return $grades;
    }
    private static function get_quiz_attempts($quizobj, $userid) {
        global $DB;
        $course = $DB->get_records('course', array('id' => $quizobj->get_cm()->course));
        $attempthistory = array();
        $attempts = quiz_get_user_attempts($quizobj->get_quizid(), $userid, 'finished', true);
        $context =  \context_module::instance($quizobj->get_cmid());
        $finalattemptfeedback = '';
        $maxattemptgrade = 0;
        $maxattemptgradefeedback = '';
        $attemptcounter = 0;
        $quiz = $quizobj->get_quiz();
        list($someoptions, $alloptions) = quiz_get_combined_reviewoptions($quiz, $attempts);
        $viewattemptsfeedback = quiz_has_feedback($quiz) && $alloptions->overallfeedback;
        foreach ($attempts as $attempt) {
            $attemptcounter++;
            $attemptobj = new quiz_attempt($attempt, $quiz, $quizobj->get_cm(), $course, false); // Grade is in sumgrades.
            $time = $attemptobj->get_attempt()->timefinish;
            $attemptgrade = quiz_rescale_grade($attemptobj->get_sum_marks(), $quiz, false);
            $attemptfeedback = quiz_feedback_for_grade($attemptgrade, $quiz, $context);
            if ($attemptgrade > $maxattemptgrade) {
                $maxattemptgrade = $attemptgrade;
                $maxattemptgradefeedback = $attemptfeedback;
            }
            $attempthistory[$attemptobj->get_attempt()->id] = array(
                    'id' => $attemptobj->get_attempt()->id,
                    'mark' => empty($attemptgrade) ? null : ''.round($attemptgrade, $quizobj->get_quiz()->decimalpoints),
                    'time' => $time
            );
            if ($attemptcounter == 0 && $quiz->grademethod == QUIZ_ATTEMPTFIRST) {
                $finalattemptfeedback = $attemptfeedback;
            } else if ($attemptcounter == count($attempts) && $quiz->grademethod == QUIZ_ATTEMPTLAST) {
                $finalattemptfeedback = $attemptfeedback;
            }
        }
        if ($quiz->grademethod == QUIZ_GRADEHIGHEST) {
            $finalattemptfeedback = $maxattemptgradefeedback;
        }
        return [$viewattemptsfeedback ? $finalattemptfeedback : '', $attempthistory];
    }

    private static function get_quiz_grade_feedback($quizobj, $userid) {
        global $CFG;
        require_once($CFG->libdir.'/gradelib.php');
        // Get final grade for quiz.
        $mygrade = null;

        // Take override by teacher to calculate final grade.
        $gradinginfo = grade_get_grades($quizobj->get_cm()->course, 'mod', 'quiz', $quizobj->get_quizid(), $userid);
        $gradebookfeedback = '';
        if (!empty($gradinginfo->items)) {
            $item = $gradinginfo->items[0];
            if (isset($item->grades[$userid])) {
                $grade = $item->grades[$userid];
                $mygrade = $grade->grade + 0; // Convert to number.
                if (!empty($grade->str_feedback)) {
                    $gradebookfeedback = $grade->str_feedback;
                }
            }
        }
        $quiz = $quizobj->get_quiz();
        $mygrade = empty($mygrade) ? null : ''.round($mygrade, $quiz->decimalpoints);
        return array( $mygrade, $gradebookfeedback);
    }

    /**
     * @param $userid
     * @param $quizobj
     * @return array
     */
    private static function get_quiz_attempts_infos($userid, $quizobj) {
        global $CFG;
        require_once($CFG->dirroot.'/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/accessmanager.php');
        $quizaccessmanager = $quizobj->get_access_manager(time());
        $lastattempttime = 0;
        $quizattempts = quiz_get_user_attempts($quizobj->get_quizid(), $userid);
        $alreadymadeattempts = count($quizattempts);
        if ($quizattempts) {
            $lastattempttime = array_pop($quizattempts)->timemodified;
        }
        $attemptsreasons = $quizaccessmanager->prevent_new_attempt(
                $alreadymadeattempts, $lastattempttime);
        $hasotherattempts = count($attemptsreasons) > 0 ? false : true;
        return array(
                'numberAllowedAttempts' => $quizobj->get_num_attempts_allowed(),
                'alreadyMadeAttempts' => $alreadymadeattempts,
                'hasOtherAttempts' => $hasotherattempts);
    }

    public static function get_metadatas_values($modulecontextid, $metadataprefix= '' , $metadatafieldlist=[], $excludedfields=[]) {
        global $CFG;
        require_once($CFG->dirroot.'/local/metadata_tools/lib.php');
        $metadatavalues = local_metadata_tools::get_all_metadata_values_from_contextid($modulecontextid, $metadataprefix,
            $metadatafieldlist,  $excludedfields);
        $metadatasreturnlist = array();
        foreach ($metadatavalues as $metadatavalue) {
            $metadatasreturnlist[$metadatavalue->get_id()] = array(
                'fieldId' => $metadatavalue->get_field()->get_id(),
                'fieldName' => $metadatavalue->get_field()->get_name(),
                'value' => $metadatavalue->get_data()
            );
        }
        return array ('metadatas' => $metadatasreturnlist);
    }
}

class local_digital_training_account_services_counter {

    private $countername;
    private $count = 0;
    public function __construct($countername) {
        $this->countername = $countername;
    }
    public function increment($increment=1, $modulename=null) {
        // TODO possible hook here depending on modulename.
        $this->count = $this->count + $increment;
    }

    public function get_counter() {
        return $this->count;
    }

    public function get_counter_name() {
        return $this->countername;
    }
}

