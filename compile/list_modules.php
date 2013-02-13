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
 * list_modules.php
 *
 * List of modules to compile
 *
 * 2011-06-03
 * @package      plug-in
 * @subpackage   RRU_Compile
 * @copyright    2011 Steve Beaudry, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//  Display the course home page.

require_once('../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once($CFG->libdir.'/completionlib.php');

$id          = optional_param('id', 0, PARAM_INT);
$name        = optional_param('name', '', PARAM_RAW);
$edit        = optional_param('edit', -1, PARAM_BOOL);
$hide        = optional_param('hide', 0, PARAM_INT);
$show        = optional_param('show', 0, PARAM_INT);
$idnumber    = optional_param('idnumber', '', PARAM_RAW);
$section     = optional_param('section', 0, PARAM_INT);
$move        = optional_param('move', 0, PARAM_INT);
$marker      = optional_param('marker',-1 , PARAM_INT);
$switchrole  = optional_param('switchrole',-1, PARAM_INT);

if (empty($id) && empty($name) && empty($idnumber)) {
    print_error('unspecifycourseid', 'error');
}

if (!empty($name)) {
    if (! ($course = $DB->get_record('course', array('shortname'=>$name)))) {
        print_error('invalidcoursenameshort', 'error');
    }
} else if (!empty($idnumber)) {
    if (! ($course = $DB->get_record('course', array('idnumber'=>$idnumber)))) {
        print_error('invalidcourseid', 'error');
    }
} else {
    if (! ($course = $DB->get_record('course', array('id'=>$id)))) {
        print_error('invalidcourseid', 'error');
    }
}

$PAGE->set_url('/local/compile/list_modules.php', array('id' => $course->id)); // Defined here to avoid notices on errors etc

preload_course_contexts($course->id);
if (!$context = get_context_instance(CONTEXT_COURSE, $course->id)) {
    print_error('nocontext');
}

// Remove any switched roles before checking login
if ($switchrole == 0 && confirm_sesskey()) {
    role_switch($switchrole, $context);
}

require_login($course);

// Switchrole - sanity check in cost-order...
$reset_user_allowed_editing = false;
if ($switchrole > 0 && confirm_sesskey() &&
    has_capability('moodle/role:switchroles', $context)) {
    // is this role assignable in this context?
    // inquiring minds want to know...
    $aroles = get_switchable_roles($context);
    if (is_array($aroles) && isset($aroles[$switchrole])) {
        role_switch($switchrole, $context);
        // Double check that this role is allowed here
        require_login($course->id);
    }
    // reset course page state - this prevents some weird problems ;-)
    $USER->activitycopy = false;
    $USER->activitycopycourse = NULL;
    unset($USER->activitycopyname);
    unset($SESSION->modform);
    $USER->editing = 0;
    $reset_user_allowed_editing = true;
}

//If course is hosted on an external server, redirect to corresponding
//url with appropriate authentication attached as parameter
if (file_exists($CFG->dirroot .'/course/externservercourse.php')) {
    include $CFG->dirroot .'/course/externservercourse.php';
    if (function_exists('extern_server_course')) {
        if ($extern_url = extern_server_course($course)) {
            redirect($extern_url);
        }
    }
}


require_once($CFG->dirroot.'/calendar/lib.php');    /// This is after login because it needs $USER

add_to_log($course->id, 'course', 'list_modules', "list_modules.php?id=$course->id", "$course->id");

$course->format = clean_param($course->format, PARAM_ALPHA);
if (!file_exists($CFG->dirroot.'/course/format/'.$course->format.'/format.php')) {
    $course->format = 'weeks';  // Default format is weeks
}

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_other_editing_capability('moodle/course:manageactivities');

if ($reset_user_allowed_editing) {
    // ugly hack
    unset($PAGE->_user_allowed_editing);
}

// We don't allow editing to the page under any circumstances
$USER->editing = 0;

$SESSION->fromdiscussion = $CFG->wwwroot .'/local/compile/list_modules.php?id='. $course->id;


if ($course->id == SITEID) {
    // This course is not a real course.
    redirect($CFG->wwwroot .'/');
}

$PAGE->set_title(get_string('course') . ': ' . $course->fullname);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

// Start of Course Compile unique code

$headertable = '<form name="compileform" id="compileform" target="_blank" action="Compiled_Course.php" method="post">';
$headertable .= '<input type="hidden" name="id" value="' . $course->id . '" />';
$headertable .= '<table class="toolbar"><tr><td>';
$headertable .= " Select: ";
$headertable .= '<a href="javascript:void(0)" onclick="checkall()" >All</a>, ';
$headertable .= '<a href="javascript:void(0)" onclick="checknone()" >None</a> ';
$headertable .= '</td></tr><tr><td>';
$headertable .= '<input type="submit" name="submit_type" value="Compile to screen" />';
$headertable .= '<input type="submit" name="submit_type" value="Compile to PDF" />';
$headertable .= '</td></tr></table>';
echo $headertable;

$coursemodulestable = '<table>';

$modinfo =& get_fast_modinfo($COURSE);
get_all_mods($course->id, $mods, $modnames, $modnamesplural, $modnamesused);

if (!$sections = get_all_sections($id)) {
    error("Course has no sections");
}

foreach ($sections as $section) {
    $row = print_course_section($course, $section, $mods, $modnamesused);
    if ($row != "") {
        $coursemodulestable .= '<tr><td>' . $row . '</td></tr>';
    }
}

$coursemodulestable .= '</table>';

echo '<input type="hidden" name="course" value="' . $course->id . '" />';
echo $coursemodulestable;
echo '</form>';
echo $OUTPUT->footer();

/**
 * Prints a section full of activity modules
 *
 * @author Steve Beaudry
 * date    2011-06-03
 * @param object course information
 * @param array section information
 * @param array mod modules of the course
 * @param array modnameused course module names used
 * @param boolean absolute
 * @param string width
 * @return string section table
 */
function print_course_section($course, $section, $mods, $modnamesused, $absolute = false, $width = "100%") {
    global $CFG, $USER;

    //$sectiontable = '<table>';
    $sectiontable = '<div>';
    $modinfo = unserialize($course->modinfo);

    if (!empty($section->sequence)) {
        $sectionmods = explode(",", $section->sequence);
        foreach ($sectionmods as $modnumber) {
            if (empty($mods[$modnumber])) {
                continue;
            }
            $mod = $mods[$modnumber];
                
            if ($mod->visible) {
                //$instancename = urldecode($modinfo[$modnumber]->name);

                list($content, $instancename) = get_print_section_cm_text($mod, $course);
         
                if (strlen($content) > 0) {
                   $instancename = strip_tags($content);
						    }  
           
                $fullinstancename = urldecode($instancename);
               
                if (!empty($CFG->filterall)) {
                    $instancename = filter_text($instancename, $course->id);
                }
                if (!empty($modinfo[$modnumber]->extra)) {
                    $extra = urldecode($modinfo[$modnumber]->extra);
                } else {
                    $extra = '';
                }

                // Normal activity
                $mod_compilable = get_config("course_compile",$mod->modname);
    						if (($mod_compilable) && ($mod->visible)) {
                    if (!strlen(trim($instancename))) {
                        $instancename = $mod->modfullname;
                    }
                    $fullinstancename = urldecode($instancename);
                    if ($mod->modname != 'resource') {
                    		$sectiontable .= '<tr>';
                        $sectiontable .= '<td>' . "<input type=checkbox checked id=$modnumber name=checkboxlist[] value=$modnumber />" . '</td>';
                        $sectiontable .= '<td>' . "<a href=$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id><img src=$CFG->wwwroot/theme/$CFG->theme/pix_plugins/mod/$mod->modname/icon.png> $fullinstancename</a>" . '</td>';
		                		$sectiontable .= '</tr>';
	    					    } else {
                    		require_once($CFG->dirroot.'/mod/resource/lib.php');
                        $info = resource_get_coursemodule_info($mod);
                        if ($info->icon) {
                            $sectiontable .= '<tr>';
                            $sectiontable .= '<td>' . "<input type=checkbox checked id=$modnumber name=checkboxlist[] value=$modnumber />" . '</td>';
                            $sectiontable .= '<td>' . "<a href=$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id><img src={$CFG->wwwroot}/pix/{$info->icon}.gif> $fullinstancename</a>" . '</td>';
                            $sectiontable .= '</tr>';
                        } else if (!$info->icon) {
                            $sectiontable .= '<tr>';
                            $sectiontable .= '<td>' . "<input type=checkboxi checked id=$modnumber name=checkboxlist[] value=$modnumber />" . '</td>';
                            $sectiontable .= '<td>' . "<a href=$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id><img src=$CFG->modpixpath/$mod->modname/icon.gif> $fullinstancename</a>" . '</td>';
                            $sectiontable .= '</tr>';
                        }
                    }
                }
            }
        }
    }
    $sectiontable .= '</div>';
    if ($sectiontable == "<div></div>") {
        $sectiontable = '';
    }
    return $sectiontable;
}
