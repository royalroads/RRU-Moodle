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
 * Compile the assignment module
 *
 * 2011-06-03
 * @package      plug-in
 * @subpackage   RRU_Compile
 * @copyright    2011 Steve Beaudry, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require_once("../../../../config.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/plagiarismlib.php');

$id = optional_param('id', 0, PARAM_INT);  // Course Module ID
$a  = optional_param('a', 0, PARAM_INT);   // Assignment ID

$url = new moodle_url('/mod/assignment/view.php');
if ($id) {
    if (! $cm = get_coursemodule_from_id('assignment', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $assignment = $DB->get_record("assignment", array("id"=>$cm->instance))) {
        print_error('invalidid', 'assignment');
    }

    if (! $course = $DB->get_record("course", array("id"=>$assignment->course))) {
        print_error('coursemisconf', 'assignment');
    }
    $url->param('id', $id);
} else {
    if (!$assignment = $DB->get_record("assignment", array("id"=>$a))) {
        print_error('invalidid', 'assignment');
    }
    if (! $course = $DB->get_record("course", array("id"=>$assignment->course))) {
        print_error('coursemisconf', 'assignment');
    }
    if (! $cm = get_coursemodule_from_instance("assignment", $assignment->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
    $url->param('a', $a);
}

$PAGE->set_url($url);
require_login($course, true, $cm);

$PAGE->requires->js('/mod/assignment/assignment.js');

require ("$CFG->dirroot/mod/assignment/type/$assignment->assignmenttype/assignment.class.php");
$assignmentclass = "assignment_$assignment->assignmenttype";
$assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);

// Actually display what we want from the assignment!
global $USER, $OUTPUT;
require_capability('mod/assignment:view', $assignmentinstance->context);
$assignmentinstance->view_intro();
$assignmentinstance->view_dates();
// Something about the feedback is breaking the PDF creation.  It's not a requirement, so I'm
// commenting it out.
//$assignmentinstance->view_feedback();

?>
