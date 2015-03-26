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
 * Compile all the information of a course into pdf or screen
 *
 * 2011-06-03
 * @package      local_compile
 * @copyright    2014 Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once('lib.php');
require_once('classes/event/log_compile.php');

defined('MOODLE_INTERNAL') || die(''); // MUST occur AFTER require_once() in this file.

if (optional_param('submit_type', '', PARAM_RAW) == 'Compile to PDF') {
    $compilepdf = true;
} else {
    $compilepdf = false;
}

$id          = optional_param('id', 0, PARAM_INT);
$name        = optional_param('name', '', PARAM_RAW);
$edit        = optional_param('edit', -1, PARAM_BOOL);
$hide        = optional_param('hide', 0, PARAM_INT);
$show        = optional_param('show', 0, PARAM_INT);
$idnumber    = optional_param('idnumber', '', PARAM_RAW);
$section     = optional_param('section', 0, PARAM_INT);
$move        = optional_param('move', 0, PARAM_INT);
$marker      = optional_param('marker', -1, PARAM_INT);
$switchrole  = optional_param('switchrole', -1, PARAM_INT);
$checkboxlist = optional_param_array('checkboxlist', '', PARAM_RAW);

if (empty($id) && empty($name) && empty($idnumber)) {
    print_error('unspecifycourseid', 'error');
}

if (!empty($name)) {
    if (! ($course = $DB->get_record('course', array('shortname' => $name)))) {
        print_error('invalidcoursenameshort', 'error');
    }
} else if (!empty($idnumber)) {
    if (! ($course = $DB->get_record('course', array('idnumber' => $idnumber)))) {
        print_error('invalidcourseid', 'error');
    }
} else {
    if (! ($course = $DB->get_record('course', array('id' => $id)))) {
        print_error('invalidcourseid', 'error');
    }
}

context_helper::preload_course($course->id);

if (!$context = context_course::instance($course->id)) {
    print_error('nocontext');
}

// Remove any switched roles before checking login.
if ($switchrole == 0 && confirm_sesskey()) {
    role_switch($switchrole, $context);
}

require_login($course);

// Switchrole - sanity check in cost-order...
$resetuserallowedediting = false;
if ($switchrole > 0 && confirm_sesskey() &&
    has_capability('moodle/role:switchroles', $context)) {
    // Is this role assignable in this context?
    $aroles = get_switchable_roles($context);
    if (is_array($aroles) && isset($aroles[$switchrole])) {
        role_switch($switchrole, $context);
        // Double check that this role is allowed here.
        require_login($course->id);
    }
    // Reset course page state - this prevents some weird problems...
    $USER->activitycopy = false;
    $USER->activitycopycourse = null;
    unset($USER->activitycopyname);
    unset($SESSION->modform);
    $USER->editing = 0;
    $resetuserallowedediting = true;
}

// Log event: User compiled a course.
$event = \compile\event\log_compile::create(array(
                'objectid' => $course->id,
                'context' => $context
));
$event->trigger();

$course->format = clean_param($course->format, PARAM_ALPHA);
if (!file_exists($CFG->dirroot.'/course/format/'.$course->format.'/format.php')) {
    $course->format = 'weeks';  // Default format is weeks.
}

// We don't allow editing to the page under any circumstances.
$USER->editing = 0;

if ($course->id == SITEID) {
    // This course is not a real course.
    redirect($CFG->wwwroot .'/');
}

if ($compilepdf) {
    $pdfhtml = compile_create_pdf_header();
} else {
    $pdfhtml = ''; // Initialize.
}

// Output a course header.
if ($compilepdf) {
    $hdrparams = new stdClass();
    // Split fullname on hyphen to make better use of background space.
    $hdrparams->title = str_replace(' - ', '<br>', $course->fullname);
    $hdrparams->moodleroot = $CFG->wwwroot;
    $pdfhtml .= get_string('pdfheader', 'local_compile', $hdrparams);
    $pdfhtml .= get_string('wrapper_start', 'local_compile');
} else {
    $pdfhtml .= get_string('wrapper_start', 'local_compile');
}

$modinfo   = get_fast_modinfo($course->id);
$mods      = $modinfo->get_cms();
$sections  = $modinfo->get_section_info_all();
$sectionid = false;

foreach ($checkboxlist as $index => $modid) {
    foreach ($mods as $mod) {
        if ($mod->id == $modid) {
            // If section id has changed, print a section header.
            if ($mod->section != $sectionid) {
                $sectionid = $mod->section;
                foreach ($sections as $thissection) {
                    if ($thissection->id == $sectionid) {
                        break;
                    }
                }
                if ($sectionid !== false) {
                    $sectionname = get_section_name($course, $thissection);
                    $sectionheaderoutputhtml = get_string('sectionheader', 'local_compile', $sectionname);
                    $pdfhtml .= $sectionheaderoutputhtml;
                }
            } // Section id change.

            // Is this course-module available to the user?
            if (!$mod->uservisible &&
            (empty($mod->showavailability) || empty($mod->availableinfo))) {
                continue; // Not available, render nothing.
            }

            // Get data about this course-module.
            $content = $mod->get_formatted_content(array('overflowdiv' => true, 'noclean' => true));

            if (strlen($content) > 0) {
                $instancename = strip_tags($content);
            } else {
                $instancename = $mod->get_formatted_name(array('overflowdiv' => true, 'noclean' => true));
            }

            $fullinstancename = urldecode($instancename);

            if ($mod->modname != 'resource') {
                $instancelogo = "<img src=\"{$CFG->wwwroot}/mod/$mod->modname/pix/icon.png\" alt=\"Resource\">";
            } else {
                require_once($CFG->dirroot.'/mod/resource/lib.php');
                $info = resource_get_coursemodule_info($mod);
                $instancelogo = "<img src=\"{$CFG->wwwroot}/pix/{$info->icon}.png\" alt=\"Resource\">";
            }

            // First, we'll look for a custom compile.php page for the module.
            // If that doesn't exist, we'll fall back to the view.php page.
            if (file_exists($CFG->dirroot."/local/compile/mod/" . $mod->modname . "/compile.php")) {

                // Does this module have a config file?  If so load it.
                $configfile = $CFG->dirroot."/local/compile/mod/" . $mod->modname . "/config.php";
                if (isset($modconfig)) { // Discard config from previous module if present.
                    unset($modconfig);
                }
                $modconfig = new stdClass(); // Initialize module config.
                if (file_exists($configfile)) { // Load module config, if present.
                    include($configfile);
                }

                // Is the module configured to NOT include the module header? (eg. mod/label).
                @$hide = $modconfig->hideheader;
                if (!$hide) {
                    $pdfhtml .= "<!-- Start of module content -->\n<h3>$instancelogo $fullinstancename</h3>\n";
                }

                // Render the module.
                $requesturl = $CFG->wwwroot . "/local/compile/mod/" . $mod->modname . "/compile.php?id=" . $mod->id;
                $htmlpage = compile_get_mod_html($requesturl);

                if ($compilepdf) {
                    // Need to remove references to dynamically generated images, because they can break the external PDF engine.
                    $htmlpage = preg_replace('/<img.*?class="userpicture.*?>/', '', $htmlpage);
                    // Get rid of any references to 'Separate Groups'.
                    $htmlpage = preg_replace('/<div class="groupselector">.*?<\/form><\/div>/', '', $htmlpage);
                    $htmlpage = compile_update_image_source($htmlpage, $mod->modname, $mod->id, 'pluginfile.php');
                    $htmlpage = compile_update_image_source($htmlpage, $mod->modname, $mod->id, 'file.php');

                    $pdfhtml .= $htmlpage;
                } else {
                    $htmlpage = compile_remove_blacklisted_links($htmlpage, $compilepdf); // Remove blacklisted links completely.
                    $pdfhtml .= $htmlpage;
                }
            } else {
                // Have Moodle render the module's output as a complete HTML page, which we will
                // strip down to content afterward.
                if ($compilepdf) {
                    $pdfhtml .= "<!-- Start of module content -->\n<h3>$fullinstancename</h3>\n";
                } else {
                    $pdfhtml .= "<!-- Start of module content -->\n<h3>$instancelogo $fullinstancename</h3>\n";
                }

                // Render the module.
                $requesturl = $CFG->wwwroot . "/mod/" . $mod->modname . "/view.php?id=" . $mod->id;
                $htmlpage = compile_get_mod_html($requesturl);

                // Remove header, footer, and other uncompilable elements.
                $htmlpage = compile_cleanup_rendered($htmlpage, $compilepdf);

                // Convert utf-8 characters which may not display correctly to single-byte ISO-8859-1.
                $htmlpage = utf8_decode($htmlpage);

                if ($compilepdf) {
                    $htmlpage = compile_update_image_source($htmlpage, $mod->modname, $mod->id, 'pluginfile.php');
                    $htmlpage = compile_update_image_source($htmlpage, $mod->modname, $mod->id, 'file.php');

                    $pdfhtml .= $htmlpage;
                } else {
                    $pdfhtml .= $htmlpage;
                }
            }
            $pdfhtml .= "<!-- End of module content -->\n<HR>\n";
        }
    }
}

// Output a course header.
if ($compilepdf) {
    $pdfhtml .= get_string('pdffooter', 'local_compile');   // Also output PDF-only footer branding.
    $pdfhtml .= get_string('wrapper_end', 'local_compile');
    $pdfhtml .= get_string('pdfclose', 'local_compile');    // Also close PDF HTML.
} else {
    $pdfhtml .= get_string('wrapper_end', 'local_compile');
}
$debugpdf = false;

$timelimit = 60; // Default time limit will be adjusted up if estimate is for more than this.
set_time_limit($timelimit);

if (!$compilepdf) {
    // Set up page.
    $PAGE->set_url('/local/compile/compile_modules.php', array('id' => $course->id));
    $PAGE->set_pagelayout('print'); // No blocks wanted, simplified for print (was 'course').
    $PAGE->set_pagetype('course-view-' . $course->format);
    $PAGE->set_other_editing_capability('moodle/course:manageactivities');
    if ($resetuserallowedediting) {
        unset($PAGE->_user_allowed_editing);
    }
    $PAGE->set_title(get_string('course') . ': ' . $course->fullname);
    $PAGE->set_heading($course->fullname);

    // Output page header.
    print $OUTPUT->header();

    // Output page content.
    print $pdfhtml;

    // Output page footer.
    print $OUTPUT->footer();
} else {
    if (!$debugpdf) {
        compile_output_as_pdf($pdfhtml);
    } else {
        print $pdfhtml;
    }
}
