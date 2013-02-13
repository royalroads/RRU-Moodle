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
 * @package      plug-in
 * @subpackage   RRU_Compile
 * @copyright    2011 Steve Beaudry, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$debugPDF = false;
if ($_POST['submit_type'] == 'Compile to PDF') {
    $compilePDF = true;
} else {
    $compilePDF = false;
}
require_once('../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once($CFG->libdir.'/completionlib.php');

if ($compilePDF) {
    require_once($CFG->dirroot.'/lib/tcpdf/tcpdf.php');
}

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
$checkboxlist = optional_param('checkboxlist', '', PARAM_RAW);

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

$PAGE->set_url('/local/compile/compile_modules.php', array('id' => $course->id)); // Defined here to avoid notices on errors etc

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

add_to_log($course->id, 'course', 'compile_module', "compile_modules.php?id=$course->id", "$course->id");

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

$SESSION->fromdiscussion = $CFG->wwwroot .'/local/compile/compile_module.php?id='. $course->id;


if ($course->id == SITEID) {
    // This course is not a real course.
    redirect($CFG->wwwroot .'/');
}

$PAGE->set_title(get_string('course') . ': ' . $course->fullname);
$PAGE->set_heading($course->fullname);
if ($compilePDF) {
    $pdf_html = print_pdf_header();
} else {
    echo $OUTPUT->header();
}
//$modinfo =& get_fast_modinfo($COURSE);
get_all_mods($course->id, $mods, $modnames, $modnamesplural, $modnamesused);
foreach ($checkboxlist as $index=>$selectedModID) {
    foreach ($mods as $mod) {
        if ($mod->id == $selectedModID) {
            $modinfo = unserialize($course->modinfo);
            // Get data about this course-module
            list($content, $instancename) = get_print_section_cm_text($mod, $course);
         
            if (strlen($content) > 0) {
        				$instancename = strip_tags($content);
						}  
            //$instancename = urldecode($modinfo[$mod->id]->name);
            $fullinstancename = urldecode($instancename);
         
            if ($mod->modname != 'resource') {
                //$instance_logo = "<img src=$CFG->wwwroot/mod/$mod->modname/pix/icon.gif>";
                $instance_logo = "<img src=$CFG->wwwroot/theme/$CFG->theme/pix_plugins/mod/$mod->modname/icon.png>";
            } else {
                require_once($CFG->dirroot.'/mod/resource/lib.php');
                $info = resource_get_coursemodule_info($mod);
                $instance_logo = "<img src={$CFG->wwwroot}/pix/{$info->icon}.gif>";
            }
            if ($compilePDF) {
                $pdf_html .= "<!-- Start of module content -->\n<h3>$fullinstancename</h3>\n";
            } else {
        				print "<!-- Start of module content -->\n<h3>$instance_logo $fullinstancename</h3>\n";
            }
            //First, we'll look for a custom compile.php page for the module.  If that doesn't exist, we'll fall back to the view.php page.
            if (file_exists($CFG->dirroot.'/local/compile/mod/' . $mod->modname . '/compile.php')) {
    						$requestURL = $CFG->wwwroot . "/local/compile/mod/" . $mod->modname . "/compile.php?id=" . $mod->id;
                $cookie="MoodleSession{$CFG->sessioncookie}=" . $_COOKIE["MoodleSession" . $CFG->sessioncookie];
                $ch=curl_init();
                curl_setopt($ch, CURLOPT_URL, $requestURL);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_COOKIE, $cookie);
                session_write_close();
                $htmlpage = curl_exec($ch);
                $returncode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($compilePDF) {
                    // Need to remove references to dynamically generated images, because they break TCPDF.
                    // Alternatively, we could add '&t=.jpg' to the end of the src URL, IF tcpdf > version 5 was being used.
                    // Another alternative would be to replace with a static icon.
                    $htmlpage = preg_replace('/<img.*?class="userpicture.*?>/','',$htmlpage);
                    // Get rid of any references to 'Separate Groups'
                    $htmlpage = preg_replace('/<div class="groupselector">.*?<\/form><\/div>/','',$htmlpage);
                    $pdf_html .= $htmlpage;
							  } else {
                    print $htmlpage;
                }
            } else {
                $requestURL = $CFG->wwwroot . "/mod/" . $mod->modname . "/view.php?id=" . $mod->id;
                $cookie="MoodleSession{$CFG->sessioncookie}=" . $_COOKIE["MoodleSession" . $CFG->sessioncookie];
                $ch=curl_init();
                curl_setopt($ch, CURLOPT_URL, $requestURL);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_COOKIE, $cookie);
								session_write_close();
                $htmlpage = curl_exec($ch);
                //We're only interesting in the content of the page, so we use a regular expression to select it 
                preg_match('/<!-- END OF HEADER -->.*<span id="maincontent">.*?<\/span>(.*)<\/div>.*?<\/div>.*?<div id="region-pre"/ims',$htmlpage, $content);
                //Should absolutely be able to do this in one regular expression, but it's escaping me today, and I'm too busy to screw with it any further.  using a second regex to get rid of the final </div> tag.  Yes, I'm embarrased.
                preg_match('/(.*)\t*<\/div>\t*/ims',$content[1],$cleaned);
                if ($compilePDF) {
                    $pdf_html .= $cleaned[1];
                } else {
                    print $cleaned[1];
                }
            }
            if ($compilePDF) {
                $pdf_html .= "<!-- End of module content -->\n<HR>\n";
    				} else {
                print "<!-- End of module content -->\n<HR>\n";
            }
        } 
    }
}

if (! $compilePDF) {
    echo $OUTPUT->footer();
} else {
    if (! $debugPDF) {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Course Compile');
        $pdf->SetSubject('Course Compile');
        $pdf->setPrintHeader(false);
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('helvetica', '', 10);

        $pdf->AddPage();
        $pdf->writeHTML($pdf_html, true, false, true, false, '');
        $pdf->Output('Compiled_Course.pdf');
    } else {
        echo $pdf_html;
    }
}
function print_pdf_header () {
    $pdf_header = "<HTML><BODY>\n";
    $pdf_header .= '<link rel="stylesheet" type="text/css" href="pdf.css" media="screen" />' . "\n";
    return $pdf_header;
} 
?>
