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
 * Unit test for Default Courses local plugin
 *
 * @package    local_defaultcourses
 * @category   phpunit
 * @copyright  2012 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/defaultcourses/lib.php');

class local_dc_test extends advanced_testcase {

    protected function setUp() {
        // Reset the database state after the test is done.
        $this->resetAfterTest(true);

        // Generate a dummy category for the dummy course.
        $this->dummycategory = $this->getDataGenerator()->create_category(array('name' => 'Dummy category',
                        'parent' => null));

        // Generate a dummy course.
        $this->dummycourse = $this->getDataGenerator()->create_course(array('name' => 'Dummy Course',
                        'category' => $this->dummycategory->id));

        // Get the dummy course's context.
        $this->dummycoursecontext = context_course::instance($this->dummycourse->id);

    }


    /**
     * Create a new course, fetch new enrolments, assert that the new enrolments
     * match the unenroled test users.  Tests db_fetch_new_enrolments().
     */
    public function test_fetch_new_enrolments() {

        // Fetch new enrolments for the dummy course.
        $initialenrolments = dc_fetch_new_enrolments($this->dummycourse->id);

        // Expected: no new enrolments.
        $this->assertEquals(0, count($initialenrolments));

        // Create three dummy users.
        $this->user  = $this->getDataGenerator()->create_user(array('email' => 'gerald.albion@royalroads.ca',
                        'username' => 'dctestuser'));
        $this->user2 = $this->getDataGenerator()->create_user(array('email' => 'gerald.albion@royalroads.ca',
                        'username' => 'dctestuser2'));
        $this->user3 = $this->getDataGenerator()->create_user(array('email' => 'gerald.albion@royalroads.ca',
                        'username' => 'dctestuser3'));

        // Fetch new enrolments for the dummy course.
        $subsequentenrolments = dc_fetch_new_enrolments($this->dummycourse->id);

        // Expected: 3 new enrolments.
        $this->assertEquals(3, count($subsequentenrolments));

    }

    /**
     * Enrol user in a new empty course, assert that the user
     * has been enrolled.  Tests dc_enrol_user().
     *
     * global object $DB The Moodle database object.
     */
    public function test_enrol_user() {
        global $DB;

        // Create a dummy user.
        $this->user  = $this->getDataGenerator()->create_user(array('email' => 'gerald.albion@royalroads.ca',
                        'username' => 'dctestuser'));

        // Create a dummy page, required to get a dummy course enrolment manager.
        $dummypage = new moodle_page();

        // Get a dummy course enrolment manager.
        $manager = new course_enrolment_manager($dummypage, $this->dummycourse);

        // Get a student role id for the dummy student.
        $studentroleid = $DB->get_field('role',
                                        'id',
                                        array('archetype' => 'student'),
                                        MUST_EXIST);

        // Get the enrolment id.
        $enrolid = $DB->get_field_select('enrol',
                                         'id',
                                         'courseid = ? AND roleid = ? AND enrol =?',
                                         array($this->dummycourse->id, $studentroleid, 'manual'));

        // Attempt to enrol the student using dc_enrol_user().
        dc_enrol_user($manager,
                      $this->dummycoursecontext,
                      $this->user->id,
                      $enrolid,
                      $studentroleid);

        // How many students enrolled in the course?
        $enrolcount = count_enrolled_users($this->dummycoursecontext);

        // Expected: one.
        $this->assertEquals(1, $enrolcount);

    }

    /**
     * Create new users, enrol them in default courses, assert that each default
     * course has the new users.
     */
    public function test_enrol_default_courses() {

        // Make the dummy course a default course.
        set_config('courseids', $this->dummycourse->id, 'local_defaultcourses');

        // Try to enrol users (none) in the default course.
        dc_enrol_default_courses();

        // How many enrolments are there?
        $initialenrolcount = count_enrolled_users($this->dummycoursecontext);

        // Expected: no enrolments.
        $this->assertEquals(0, $initialenrolcount);

        // Create three dummy users.
        $this->user  = $this->getDataGenerator()->create_user(array('email' => 'gerald.albion@royalroads.ca',
                        'username' => 'dctestuser'));
        $this->user2 = $this->getDataGenerator()->create_user(array('email' => 'gerald.albion@royalroads.ca',
                        'username' => 'dctestuser2'));
        $this->user3 = $this->getDataGenerator()->create_user(array('email' => 'gerald.albion@royalroads.ca',
                        'username' => 'dctestuser3'));

        // Try again to enrol users (three) in the default course.
        dc_enrol_default_courses();

        // How many enrolments are there now?
        $subsequentenrolcount = count_enrolled_users($this->dummycoursecontext);

        // Expected: three enrolments.
        $this->assertEquals(3, $subsequentenrolcount);

    }

}