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
 * List of modules to compile
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
require_once('classes/event/log_listmodules.php');

defined('MOODLE_INTERNAL') || die(''); // Config.php must be loaded first.

//  Display the course home page.

$id          = optional_param('id', 0, PARAM_INT);
$name        = optional_param('name', '', PARAM_RAW);
$edit        = optional_param('edit', -1, PARAM_BOOL);
$hide        = optional_param('hide', 0, PARAM_INT);
$show        = optional_param('show', 0, PARAM_INT);
$idnumber    = optional_param('idnumber', '', PARAM_RAW);
$section     = optional_param('section', 0, PARAM_INT);
$move        = optional_param('move', 0, PARAM_INT);
$marker      = optional_param('marker', -1 , PARAM_INT);
$switchrole  = optional_param('switchrole', -1, PARAM_INT);

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

$PAGE->set_url('/local/compile/list_modules.php', array('id' => $course->id)); // Defined here to avoid notices on errors etc.

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
    // Reset course page state - this prevents some weird problems.
    $USER->activitycopy = false;
    $USER->activitycopycourse = null;
    unset($USER->activitycopyname);
    unset($SESSION->modform);
    $USER->editing = 0;
    $resetuserallowedediting = true;
}

// Logging event: User listed compilable modules.
$event = \compile\event\log_listmodules::create(array(
    'objectid' => $course->id,
    'context' => $context
));
$event->trigger();

$course->format = clean_param($course->format, PARAM_ALPHA);
if (!file_exists($CFG->dirroot.'/course/format/'.$course->format.'/format.php')) {
    $course->format = 'weeks';  // Default format is weeks.
}

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_other_editing_capability('moodle/course:manageactivities');

if ($resetuserallowedediting) {
    unset($PAGE->_user_allowed_editing);
}

// Don't allow editing to the page under any circumstances.
$USER->editing = 0;

if ($course->id == SITEID) {
    // This course is not a real course.
    redirect($CFG->wwwroot .'/');
}

$PAGE->set_title(get_string('course') . ': ' . $course->fullname);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

echo get_string('modselectheader', 'local_compile', $course->fullname);

// Start of Course Compile unique code.

$headertable  = '<form name="compileform" id="compileform" target="_blank" action="compiled_course.php" method="post">';
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

$modinfo      = get_fast_modinfo($course->id);
$sections     = $modinfo->get_section_info_all();
$mods         = $modinfo->get_cms();

if (!$sections) {
    error(get_string('coursehasnosections', 'local_compile'));
}

// User notification.
echo get_string('selectionnotice', 'local_compile');

// JavaScript required to toggle the modules in a section according to the section checkbox.
$coursemodulestable = "
<script type=\"text/javascript\">
/**
 * Toggle a whole section of course modules according to the section checkbox
 * @author Gerald Albion
 * date 2014-04-04
 * @param int sid Section ID of the modules to be toggled
 * @return void
 */

function togglesection(sid) {
  var sender   = document.getElementById(''+sid);
  var newstate = sender.checked;
  var inputs   = document.getElementsByTagName('input');
  for(var i=0;i<inputs.length;i++) {
    if (inputs[i].type=='checkbox') {
      if (inputs[i].disabled||inputs[i].readOnly) continue;
      if (inputs[i].getAttribute('class')=='section-'+sid) {
        inputs[i].checked=newstate;
      }
    }
  }
}

function setparent(sid,pid) {
  var sender = document.getElementById(''+sid);
  var parent = document.getElementById(''+pid);

  // If we have turned on a module, turn on the section too.
  if (sender.checked) {
    parent.checked = true;
  }

  // Have we have turned off all of the modules?
  var allunchecked = true;
  var inputs   = document.getElementsByTagName('input');
  for(var i=0;i<inputs.length;i++) {
    if (inputs[i].type=='checkbox') {
      if (inputs[i].disabled||inputs[i].readOnly) continue;
      if (inputs[i].getAttribute('class')=='section-'+pid) {
        if (inputs[i].checked) {
          allunchecked = false;
          break;
        }
      }
    }
  }

  // Yes, so turn off the section too.
  if (allunchecked) {
    parent.checked = false;
  }
}

</script>
";

// Add each section to the output HTML.
foreach ($sections as $section) {
    $row = print_course_section($course, $section, $mods);
    if ($row != "") {
        $sectionname         = get_section_name($course, $section);
        $coursemodulestable .= '<table class="compile-section">';
        $coursemodulestable .= '<tr><td colspan=3>';
        $coursemodulestable .= '<input type=checkbox checked id='
            . $section->id.' name=checkboxlist[] value="'
            . $section->id.'" onclick="togglesection('.$section->id.');"/>';
        $coursemodulestable .= '<a class="compile-section-caption" href="'
            . $CFG->wwwroot.'/course/view.php?id='
            . $course->id.'&section='
            . $section->section.'">'
            . $sectionname
            . '</a>';
        $coursemodulestable .= '</td></tr>';
        $coursemodulestable .= $row;
        $coursemodulestable .= ' </table>';
    }
}

echo '<input type="hidden" name="course" value="' . $course->id . '" />';
echo $coursemodulestable;
echo '</form>';
echo $OUTPUT->footer();
