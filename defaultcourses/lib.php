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
 * lib.php, Library of Default Course functions
 *
 * Support enrolment of users in default courses
 *
 * 2012-06-20
 * @package      plug-in
 * @subpackage   Default_Courses
 * @copyright    2012 Andrew Zoltay, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . "/enrol/locallib.php");
defined('MOODLE_INTERNAL') || die;


function local_defaultcourses_cron(){
    dc_enrol_default_courses();
}

/**
 * Purpose: Enrol all users who are not currently enroled as students
 *          in the courses defined as default courses
 *
 * @author Andrew Zoltay
 * date    2012-06-20
 * @global object $DB Moodle database object
 * @return none
 */
function dc_enrol_default_courses() {
    global $DB;
    
    $successcount = 0;
    $failurecount = 0;
    
    // Get the Student Role ID
    $studentroleid = $DB->get_field('role', 'id', array('archetype'=>'student'), MUST_EXIST);

    // Get courseids for default courses
    $defaultcourses = explode(',',get_config('local_defaultcourses','courseids'));

    // Make sure there are some default courses to enrol users
    if ($defaultcourses) {
        //Enroling all users in default courses...
        foreach($defaultcourses as $defaultcourse) {
            $course = $DB->get_record('course', array('id'=>(int)$defaultcourse), '*', MUST_EXIST);
            $context = get_context_instance(CONTEXT_COURSE, (int)$defaultcourse, MUST_EXIST);
            $dummypage = new moodle_page(); // Need to create dummy moodle_page for course_enrolment_manager - annoying!
            $manager = new course_enrolment_manager($dummypage, $course);

            mtrace("Enrol users in default course: $course->fullname");
            // Get the enrolment id for the course
            $enrolid = $DB->get_field_select('enrol', 'id', 'courseid = ? AND roleid = ? AND enrol =?', array($course->id, $studentroleid, 'manual'));

            // Check to make sure enrolment method exists before trying to enrol users
            if ($enrolid) {
                $defaultenrolments = dc_fetch_new_enrolments($course->id);
                if (!$defaultenrolments) {
                    mtrace("No users found to be enroled in default course: $course->fullname");
                } else {
                    // Enrol each student in this default course
                    foreach($defaultenrolments as $defaultenrolment) {
                        if (dc_enrol_user($manager, $context, $course->startdate, $defaultenrolment->userid, $enrolid, $studentroleid)) {
                            $successcount++;
                        } else {
                            mtrace("ERROR - failed to enrol student: '$defaultenrolment->username");
                            $failurecount++;
                        }
                    }
                }
            } else {
                // write error to CRON log
                mtrace("Manual enrolment method is missing for default course: $course->fullname");
            }
        }
        mtrace("$successcount students were successfully enroled in default courses");
        mtrace("$failurecount students failed to be enroled in default courses");
    } else {
            // No default courses defined
        mtrace("No default courses have been defined - define defalut courses through " . 
               "Site Administration block -> Plugins -> Local Plugins -> Default Courses");   
    }

}


/**
 * Purpose: Return a resultset of all users who are not 
 *          currently enroled in the course
 * 
 * Exclude admin and guest accounts (ids 1 and 2 respectively)
 *
 * @author Andrew Zoltay
 * date    2012-06-20
 * @global object $DB Moodle database object
 * @return none
 */
function dc_fetch_new_enrolments($courseid) {
    global $DB;
    
    try {
        $sql = "SELECT
                    u.id AS userid, u.username
                FROM {user} u
                WHERE NOT EXISTS (SELECT 1 FROM {user_enrolments} ue
                                INNER JOIN {enrol} e ON (e.id = ue.enrolid)
                                WHERE ue.userid = u.id
                                AND e.courseid = $courseid)
                AND u.id NOT IN (1,2)
                ORDER BY u.id;";

         $enrolments =$DB->get_records_sql($sql);
         return $enrolments;
    }
    catch(dml_exception $e) {
        //AZRevisit - "ERROR - Failed to get missing enrolments for Default Courses");
        return false;
    }
}

/**
 * Purpose: enrol a user in a course
 *
 * @author Andrew Zoltay
 * date    2012-06-20
 * @param object $manager - course manager
 * @param object $coursecontext - context of the specific course
 * @param int $coursestart - course start date (UNIX format)
 * @param int $userid   - user id of the student to enrol
 * @param int $enrolid  - enrolment id for the course
 * @param int $roleid   - type of role
 * @return boolean - true for success, false for failure
 */
function dc_enrol_user($manager, $coursecontext, $coursestart, $userid, $enrolid, $roleid) {

    // Enrolment should start the day of creation
    $today = time();
    $timestart = make_timestamp(date('Y',$today), date('m', $today), date('d',$today),0,0,0);
    $timeend = 0;  // Enrolment should be unlimited

    $instances = $manager->get_enrolment_instances();
    $plugins = $manager->get_enrolment_plugins();
    // Ensure that instance exists for $enrolid
    if (!array_key_exists($enrolid,$instances)) {
        mtrace("ERROR - no instance found for enrolid = $enrolid");
        return false;
    }

    $enrolinstance = $instances[$enrolid];
    $enrolplugin = $plugins[$enrolinstance->enrol];
    // AZRevisit!!!! - need to figure out what to use for admin id (last parameter)
    if ($enrolplugin->allow_enrol($enrolinstance) && has_capability('enrol/'.$enrolplugin->get_name().':enrol', $coursecontext, 2)) {
        $enrolplugin->enrol_user($enrolinstance, $userid, $roleid, $timestart, $timeend);
        return true;
    } else {
        mtrace("ERROR - cannot enrol userid: $userid in course - permission denied");
        return false;
    }
    
}
