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
 * Compile resource module
 *
 * @package    local_compile
 * @subpackage mod_resource
 * @copyright  2014 Royal Roads University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../../config.php');
require_once('../../lib.php');
require_once($CFG->dirroot.'/mod/resource/locallib.php');
defined('MOODLE_INTERNAL') || die(); // Must occur after loading config.php.

// Get and qualify Course Module ID.
$modname = 'resource'; // Set to the name of the module.
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

$resource = $DB->get_record('resource', array('id' => $cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

$fs = get_file_storage();

// Although there should only ever be 0 or 1 file(s) associated with this module instance,
// the File API only supports getting an array of them by instance context id.
$files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder');
if (count($files) < 1) {
    resource_print_filenotfound($resource, $cm, $course);
    die;
} else {
    $file = array_pop($files); // There can be only one.
}

$intro = compile_activity_intro($cm);

// Output module content: Intro (description).
print get_string('intro', 'local_compile', $intro);

// Output the actual filename.
$filename = $file->get_filename();
print get_string('resource_filename', 'local_compile', $filename);

