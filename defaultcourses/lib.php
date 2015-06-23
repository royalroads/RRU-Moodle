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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . "/enrol/locallib.php");

/**
 * DC_ADMIN_ACCOUNT - the id number of the Moodle admin (mdladmin) account.
 */
define('DC_ADMIN_ACCOUNT', 2);

/**
 * Cron interface to enrol users in default courses.
 *
 * @param none.
 * @return none.
 */
function local_defaultcourses_cron() {
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

    // Initialize enrolment results object.
    $results = new stdClass();

    // Get the Student Role ID.
    $studentroleid = $DB->get_field('role', 'id', array('archetype' => 'student'), MUST_EXIST);

    // Get courseids for default courses.
    $defaultcourses = explode(',', get_config('local_defaultcourses', 'courseids'));

    // Make sure there are some default courses to enrol users.
    if ($defaultcourses) {
        // Enroling all users in default courses...
        foreach ($defaultcourses as $defaultcourse) {
            $course = $DB->get_record('course', array('id' => (int)$defaultcourse), '*', MUST_EXIST);

            mtrace("Enrol users in default course: $course->fullname");
            // Get the enrolment id for the course.
            $enrolid = $DB->get_field_select('enrol',
                                             'id',
                                             'courseid = ? AND roleid = ? AND enrol =?',
                                             array($course->id, $studentroleid, 'manual'));

            // Check to make sure enrolment method exists before trying to enrol users.
            if ($enrolid) {
                $defaultenrolments = dc_fetch_new_enrolments($course->id);
                if (!$defaultenrolments) {
                    mtrace("No users found to be enroled in default course: $course->fullname");
                } else {
                    $results = dc_enrol_users($defaultenrolments, $course, $enrolid, $studentroleid);
                }
            } else {
                // Write error to CRON log.
                mtrace("Manual enrolment method is missing for default course: $course->fullname");
            }
        }
        $tmp = (array) $results; // Cast as array so empty() is meaningful...
        if (!empty($tmp)) {
            mtrace("$results->successcount students were successfully enroled in default courses");
            mtrace("$results->failurecount students failed to be enroled in default courses");
        }
    } else {
            // No default courses defined.
        mtrace("No default courses have been defined - define defalut courses through " .
               "Site Administration block -> Plugins -> Local Plugins -> Default Courses");
    }
}

/**
 * Helper function for dc_enrol_default_courses - enrols a list of users in a course.
 *
 * @param array $users -
 * @param object $course - The course into which the users are enrolled
 * @param int $enrolid - enrolment type id
 * @param int $studentroleid - id of the role in which the user is being enrolled (student)
 * @return stdClass
 */
function dc_enrol_users($users, $course, $enrolid, $studentroleid) {

    // Set up result object.
    $results = new stdClass();
    $results->successcount = 0;
    $results->failurecount = 0;

    // Set up parameters for dc_enrol_user.
    $context = context_course::instance($course->id, MUST_EXIST);
    $dummypage = new moodle_page(); // Need to create dummy moodle_page for course_enrolment_manager - annoying!
    $manager = new course_enrolment_manager($dummypage, $course);

    // Enrol each student in the default course.
    foreach ($users as $user) {
        if (dc_enrol_user($manager, $context,
                $user->userid, $enrolid, $studentroleid)) {
            $results->successcount++;
        } else {
            mtrace("ERROR - failed to enrol student: '$user->username");
            $results->failurecount++;
        }
    }
    return $results;
}

/**
 * Purpose: Return a resultset of all users who are not
 *          currently enroled in the course
 *
 * Exclude admin and guest accounts (ids 1 and 2 respectively)
 * Exclude deleted accounts (2014-06-02 gta)
 *
 * @author Andrew Zoltay
 * date    2012-06-20
 * @global object $DB Moodle database object
 * @param int $courseid ID number of the course
 * @return array A list of users who are not currently enrolled,
 * @return bool  or FALSE if there is a DML exception trying to get the users
 */
function dc_fetch_new_enrolments($courseid) {
    global $DB;
    $sql = "SELECT
        u.id AS userid, u.username
        FROM {user} u
        WHERE NOT EXISTS (SELECT 1 FROM {user_enrolments} ue
        INNER JOIN {enrol} e ON (e.id = ue.enrolid)
        WHERE ue.userid = u.id
        AND e.courseid = $courseid)
        AND u.id NOT IN (1,2) -- Excluding Moodle guest and admin accounts
        AND u.deleted <> 1
        ORDER BY u.id;";

    try {
         $enrolments = $DB->get_records_sql($sql);
         return $enrolments;
    } catch (dml_exception $e) {
        mtrace("ERROR - Database error (code {$e->getCode()}) attempting to get missing enrolments " .
               "for Default Courses. Error message: {$e->getMessage()}");
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
 * @param int $userid   - user id of the student to enrol
 * @param int $enrolid  - enrolment id for the course
 * @param int $roleid   - type of role
 * @return boolean - true for success, false for failure
 */
function dc_enrol_user($manager, $coursecontext, $userid, $enrolid, $roleid) {

    // Enrolment should start the day of creation.
    $today = time();
    $timestart = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);
    $timeend = 0;  // Enrolment should be unlimited.

    $instances = $manager->get_enrolment_instances();
    $plugins = $manager->get_enrolment_plugins();
    // Ensure that instance exists for $enrolid.
    if (!array_key_exists($enrolid, $instances)) {
        mtrace("ERROR - no instance found for enrolid = $enrolid");
        return false;
    }

    $enrolinstance = $instances[$enrolid];
    $enrolplugin = $plugins[$enrolinstance->enrol];
    if ($enrolplugin->allow_enrol($enrolinstance) &&
            has_capability('enrol/'.$enrolplugin->get_name().':enrol', $coursecontext, DC_ADMIN_ACCOUNT)) {
        $enrolplugin->enrol_user($enrolinstance, $userid, $roleid, $timestart, $timeend);
        return true;
    } else {
        mtrace("ERROR - cannot enrol userid: $userid in course - permission denied");
        return false;
    }

}
