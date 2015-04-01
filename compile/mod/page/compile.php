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
 * Compile page module
 *
 * @package    local_compile
 * @subpackage mod_page
 * @author     Gerald Albion
 * @copyright  2014 Royal Roads University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../../../config.php");
require_once("../../../../mod/page/lib.php");
require_once("../../../../mod/page/locallib.php");

defined('MOODLE_INTERNAL') || die();  // Must load config.php first.

// Get and qualify Course Module.
$modname = 'page';
$id = optional_param('id', 0, PARAM_INT); // Get Course Module ID.
if ($id) {
    // Get the course module.  If we can't, error out.
    if (! $cm = get_coursemodule_from_id($modname, $id)) {
        die(get_string('invalidcoursemodule', 'error'));
    }

    // Get the course.  If we can't, error out.
    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        die(get_string('coursemisconf', 'error'));
    }

    // Get the module instance.  If we can't, error out.
    if (! $instance = $DB->get_record($modname, array("id" => $cm->instance))) {
        die(get_string('invalidcoursemodule', 'error'));
    }
} else {
    die(get_string('invalidcoursemodule', 'error'));
}

// Get the raw content from the instance, converting encoded URLs to actual URLs.
$context = context_module::instance($cm->id);
$content = file_rewrite_pluginfile_urls($instance->content,
        'pluginfile.php',
        $context->id,
        'mod_page',
        'content',
        $instance->revision);

// The filtered content is the final product added to the compiled course.
print $content;
