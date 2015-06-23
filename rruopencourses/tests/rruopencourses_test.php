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
 * Unit test for RRU Open Courses local plugin
 *
 * @package    local_rruopencourses
 * @category   phpunit
 * @copyright  2012 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/rruopencourses/lib.php');

class local_rruopencourses_testcase extends advanced_testcase {
public static $includecoverage = array('lib/moodlelib.php');


	/**
    * If no courses were going to be opened return a string
    *
    */
	public function test_coursetable_empty() {
		$courses 		= array();
		$coursetable   	= coursetable($courses);
		$this->assertEquals('    <p>Although courses were scheduled to open today, no courses were opened.</p>', $coursetable);
	}

	/*
	* Verify that an array is returned when there is an array of courses that
	* are going to be opened. It checks that the array is not empty.
	*/
	public function test_coursetable_nonempty() {
		$courses 			= array();
        //Create data for course 1
		$rowdata        	= new stdClass ();
		$rowdata->id    	= 100;
		$rowdata->idnumber 	= 'IHMN440__Y1314P-01';
		$rowdata->fullname 	= 'TEST Course 1';
		$courses[]			= $rowdata;
		//Create data for course 2
		$rowdata        	= new stdClass ();
		$rowdata->id    	= 200;
		$rowdata->idnumber 	= 'BUSA522__Y1314S-01';
		$rowdata->fullname 	= 'TEST Course 2';
		$courses[]        	= $rowdata;

		$coursetable 		= coursetable($courses);
		$this->assertNotEmpty($coursetable);
	}

	/**
	* Verify that an array is returned and it matches the content that
	* was created
	*/
	public function test_coursetable_content() {
		global $CFG;
		$courses = array();
		$contentrows = '';
		$contenthead =  get_string('emailgridhead', 'local_rruopencourses');
		$contentfoot =  get_string('emailgridfoot', 'local_rruopencourses');
		$rowdata                = new stdClass ();
		$rowdata->id            = 100;
		$rowdata->idnumber      = 'IHMN440__Y1314P-01';
		$rowdata->fullname      = 'TEST Course 1';
		$rowdata->url      	= $CFG->wwwroot.'/course/view.php?id=100';
		$courses[]              = $rowdata;
		$contentrows             = get_string('emailgridrow', 'local_rruopencourses', $rowdata);
		$content = $contenthead . $contentrows .  $contentfoot;
		$coursetable = coursetable($courses);
		$this->assertEquals($content, $coursetable);
	}

	/**
	* Verify the email functionality for the plugin
	*/
	public function test_local_rrusendemailto() {
		global $CFG;

		$headerfields = new stdClass();
		$headerfields->fromemail   = 'Course Notification <' . $CFG->noreplyaddress . '>';
		$headerfields->ver         = phpversion();
		$headers                   = get_string('emailheaders','local_rruopencourses', $headerfields);

		$this->resetAfterTest();

		$email = 'websystems@royalroads.ca';

		$subject = 'Courses opened';
		$messagetext = '<table><thead><th>ID Number</th><th>Course Name</th></thead><tr><td>IHMN440__Y1314P-01</td><td>Environmental Studies</td></tr></table>';
		//An email should be sent to the websystems account. Verify the email.
		$err = local_rrusendmailto($email,$subject,$messagetext,$headers);
		$this->assertEquals('', $err);
	}
}
?>
