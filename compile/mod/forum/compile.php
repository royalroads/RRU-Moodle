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
 * Compile forum module
 *
 * @package    local_compile
 * @subpackage mod_forum
 * @author     Gerald Albion
 * @copyright  2014 Royal Roads University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once('../../lib.php');

defined('MOODLE_INTERNAL') || die();  // Must load config.php before checking MOODLE_INTERNAL.

// Get and qualify Course Module ID.
$modname = 'forum'; // Set to the name of the module.
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

// Output disclaimer.
print get_string('forum_disclaimer', 'local_compile');
