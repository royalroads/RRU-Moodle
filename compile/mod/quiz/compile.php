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
 * Compile quiz module
 *
 * @package    local_compile
 * @subpackage mod_quiz
 * @author     Gerald Albion
 * @copyright  2014 Royal Roads University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../../../config.php");
require_once('../../lib.php');

defined('MOODLE_INTERNAL') || die;  // Must load config.php first.

$modname = 'quiz'; // Set to the name of the module.
$id = optional_param('id', 0, PARAM_INT); // Get Course Module ID.
if ($id) {
    if (! $cm = get_coursemodule_from_id($modname, $id)) {
        die(get_string('invalidcoursemodule', 'error'));
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        die(get_string('coursemisconf', 'error'));
    }

    if (! $instance = $DB->get_record($modname, array("id" => $cm->instance))) {
        die(get_string('invalidcoursemodule', 'error'));
    }
} else {
    die(get_string('invalidcoursemodule', 'error'));
}

$intro = compile_activity_intro($cm);

// Output module content: Intro (description).
print get_string('intro', 'local_compile', $intro);

// Output Quiz open and close dates.
$timeopen = $instance->timeopen;
$timeclose = $instance->timeclose;
$dateformat = get_string('compile_dateformat', 'local_compile');

// Open the dates wrapper.
print get_string('quiz_openwrap', 'local_compile');

// Display the open date (or "No open date" if zero).
if ($timeopen == 0) {
    print get_string('quiz_noopen', 'local_compile');
} else {
    print get_string('quiz_open', 'local_compile', date($dateformat, $timeopen));
}

// Display the close date (or "No close date" if zero).
if ($timeclose == 0) {
    print get_string('quiz_noclose', 'local_compile');
} else {
    print get_string('quiz_close', 'local_compile', date($dateformat, $timeclose));
}
// Close the dates wrapper.
print get_string('quiz_closewrap', 'local_compile');
