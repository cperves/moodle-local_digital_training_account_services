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
 * local digital training account services hook lib for counters
 *
 *
 * @package    local_digital_training_account_services
 * @author Céline Pervès <cperves@unistra.fr>
 * @copyright Université de Strasbourg 2020 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function mod_forum_counter_special_implementation($cmid, $userid) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/forum/lib.php');
    require_once($CFG->libdir.'/grouplib.php');
    $cm = $DB->get_record('course_modules', array('id' => $cmid));
    $course = $DB->get_record('course', array('id' => $cm->course));
    $forumid = $cm->instance;
    // Retrieve all posts since last user viewed of associated module instance.
    $modulelastviewed = $DB->get_record('logstore_lastviewed_log', array('userid' => $userid, 'cmid' => $cmid));
    // In case of courseviewreinit already taken in charge.
    // Search now for new or modified posts since user last visit on this module.
    $sql = 'select d.forum,count(p.id) as unread from {forum_discussions} d inner join {forum_posts} p on d.id=p.discussion
                where d.forum=:forumid and p.userid <> :userid';
    $timeconditionsql = ' and ((timeend=0 and timestart=0)'
        .' or (timeend=0 and timestart<>0 and timestart<=:currenttime1) or (timestart=0 and timeend<>0 and timeend>=:currenttime2)'
        .' or (timestart<>0 and timeend<>0 and timestart<=:currenttime3 and timeend>=:currenttime4))';
    $groupby = ' GROUP BY d.forum';
    $currenttime = time();
    $params = array(
        'userid' => $userid,
        'forumid' => $forumid,
        'currenttime1' => $currenttime,
        'currenttime2' => $currenttime,
        'currenttime3' => $currenttime,
        'currenttime4' => $currenttime,
    );
    $timesql = '';
    if ($modulelastviewed) {
        $timesql = ' AND p.modified > :lastmodulevisit';
        $params['lastmodulevisit'] = $modulelastviewed->lasttimeviewed;
    }
    $return = $DB->get_record_sql($sql.$timeconditionsql.$timesql.$groupby, $params);
    if ($return) {
        return $return->unread;
    }
    return 0;
}
// Followed comment code is a hook example.
/*
function mod_forum_course_view_special_implementation($cmid, $userid) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/forum/lib.php');
    $cm = $DB->get_record('course_modules', array('id' => $cmid));
    $forumid = $cm->instance;
    //get posts
    $unreadforumposts = custom_forum_tp_get_course_unread_posts($userid, $forumid);
    foreach ($unreadforumposts as $unreadforumpost) {
        forum_tp_mark_post_read($userid, $unreadforumpost);
    }
}*/
