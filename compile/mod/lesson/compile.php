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
 * Compile lesson module
 *
 * @package    local_compile
 * @subpackage mod_lesson
 * @author     Carlos Chiarella
 * @copyright  2014 Royal Roads University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once('../../lib.php');
require_once($CFG->dirroot.'/mod/lesson/lib.php');

defined('MOODLE_INTERNAL') || die(); // Must load config.php before checking MOODLE_INTERNAL.
$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
$highlightrequired = false;

// Validations checks. It validates that the course module id, course id and feedback record exist.
if ($id) {
    if (! $cm = get_coursemodule_from_id('lesson', $id)) {
        die(get_string('invalidcoursemodule', 'error'));
    }
    if (! $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST)) {
        die(print_error('coursemisconf'));
    }
    if (! $lesson = $DB->get_record('lesson', array('id' => $cm->instance), '*', MUST_EXIST)) {
        die(print_error('invalidcoursemodule'));
    }
} else {
    die("Invalid course module ID.");
}

// The objects $context and $PAGE are set because they are needed for $OUTPUT.
$context = context_module::instance($cm->id);
$PAGE->set_context($context);

// Output module content: Intro (description).

if ($lesson->dependency > 0) {
    $lessondependency = $DB->get_record('lesson', array('id' => $lesson->dependency), '*', MUST_EXIST);
    print get_string('dependencyon', 'lesson') .': ' . $lessondependency->name;
}


