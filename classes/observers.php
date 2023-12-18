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
 * local digital training account services observers
 *
 * @package    local_digital_training_account_services
 * @author Céline Pervès <cperves@unistra.fr>
 * @copyright Université de Strasbourg 2020 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_digital_training_account_services;

use Box\Spout\Common\Helper\GlobalFunctionsHelper;

defined('MOODLE_INTERNAL') || die();

class observers {
    public static function course_viewed(\core\event\course_viewed $event) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/local/digital_training_account_services/hooklib.php');
        require_once($CFG->dirroot.'/local/digital_training_account_services/locallib.php');
        if (\local_digital_training_account_services_tools::is_last_viewed_course_logstore_enabled()) {
            $courseviewhookedmodulesstr = get_config('local_digital_training_account_services', 'courseviewhookedmodules');
            $courseviewhookedmodules = explode(',', $courseviewhookedmodulesstr);
            if (!isguestuser() && !\core\session\manager::is_loggedinas()) {
                if ((boolean) get_config('local_digital_training_account_services', 'courseviewreinit')) {
                    $rawmodules = get_course_mods($event->courseid);
                    if ($rawmodules) {
                        foreach ($rawmodules as $rawmodule) {
                            $functionname = 'mod_' . $rawmodule->modname . '_course_view_special_implementation';
                            if (in_array('mod_' . $rawmodule->modname, $courseviewhookedmodules)
                                    && function_exists($functionname)) {
                                $functionname($rawmodule->id, $event->userid);
                            } else {
                                $record = $DB->get_record('logstore_lastviewed_log',
                                        array('cmid' => $rawmodule->id, 'userid' => $event->userid));
                                if ($record) {
                                    $record->lasttimeviewed = time();
                                    $DB->update_record('logstore_lastviewed_log', $record);
                                } else {
                                    $record = new \stdClass();
                                    $record->cmid = $rawmodule->id;
                                    $record->userid = $event->userid;
                                    $record->lasttimeviewed = time();
                                    $DB->insert_record('logstore_lastviewed_log', $record);
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    public static function discussion_or_post_created(\core\event\base $event) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/digital_training_account_services/locallib.php');
        if (\local_digital_training_account_services_tools::is_last_updated_course_logstore_enabled()) {
            require_once($CFG->dirroot . '/mod/forum/classes/event/discussion_created.php');
            require_once($CFG->dirroot . '/mod/forum/classes/event/post_created.php');
            if (!$event instanceof \mod_forum\event\discussion_created && !$event instanceof \mod_forum\event\post_created ) {
                throw new moodle_exception('discussion_or_post_created error : event is not an isntance of discussion_created or post_created');
            }
            self::create_or_update_lastupdated($event);
        }
    }

    public static function discussion_or_post_updated(\core\event\base $event) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/digital_training_account_services/locallib.php');
        if (\local_digital_training_account_services_tools::is_last_updated_course_logstore_enabled()) {
            require_once($CFG->dirroot . '/mod/forum/classes/event/discussion_updated.php');
            require_once($CFG->dirroot . '/mod/forum/classes/event/post_updated.php');
            if (!$event instanceof \mod_forum\event\discussion_updated && !$event instanceof \mod_forum\event\post_updated ) {
                throw new moodle_exception('discussion_or_post_created error : event is not an isntance of discussion_created or post_created');
            }
            self::create_or_update_lastupdated($event);
        }
    }

    /**
     * @param \core\event\event $event
     * @param \moodle_database $DB
     * @throws \dml_exception
     */
    private static function create_or_update_lastupdated($event) {
        global $DB;
        $record = $DB->get_record('logstore_lastupdated_log',
                array('cmid' => $event->contextinstanceid));// As a matter of fact this is cmid and not instance.
        if (!$record) {
            $record = new \stdClass();
            $record->cmid = $event->contextinstanceid;// Here this is cmid and not instance.
            $record->lasttimeupdated = $event->timecreated;
            $DB->insert_record('logstore_lastupdated_log', $record);
        } else {
            $record->lasttimeupdated = $event->timecreated;
            $DB->update_record('logstore_lastupdated_log', $record);
        }
    }

}