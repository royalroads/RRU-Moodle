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
 * local_compile.php, collection of language strings
 *
 *
 * 2014-07-29
 * @package      local_compile
 * @subpackage   lang_en
 * @author       Andy Zoltay, Gerald Albion
 * @copyright    2014 Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/*
 * Course Compile: General
 */

// Title.
$string['plugin_title'] = 'Course Compile and Print';

// Event: log_listmodules.
$string['eventlog_listmodules'] = 'Course Compile: Modules listed';

// Event: log_compile.
$string['eventlog_compile'] = 'Course Compiled';

// Error: Image Missing.
$string['imagemissing'] = '(Missing: {$a})';

// Inline error: Image on blacklisted domain.
$string['imageblacklisted'] = '({$a} not included in compile.)';

// Inline error: Blacklisted link not included in Screen output.
$string['linkblacklisted'] = '({$a} not included in compile.)';

// Error Title: Cannot load PDF engine.
$string['cantloadpdfengine'] = 'Unable to load external PDF engine.';

// Error Details: PDF Engine missing.
$string['pdfenginemissing'] = 'The specified external PDF engine is not installed.';

// Error Details: PDF Engine bad permissions.
$string['pdfenginebadpermissions'] = 'Moodle does not have access to the external PDF engine.';

// Error Title: Cannot compile PDF.  The Engine loaded and ran but there was no output.
$string['cantcompilepdf'] = 'Unable to compile PDF'.

// Error Details: Cannot compile PDF.  The Engine loaded and ran but there was no output.
$string['cantcompilepdfunknown'] = 'The PDF engine was unable to produce a PDF file.';

// Error: Course has no sections.
$string['coursehasnosections'] = 'Course has no sections';

// Settings page.
$string['excludedomainscaption'] = 'Exclude Domains';
$string['excludedomainsverbose'] = 'Enter your list of domains to be excluded in compiled linked resources, separated by commas.';

// Info text to display at the top of the module selection page.
$string['selectionnotice'] = '
<div id="selectionnotice">
   Disclaimer: This course compile represents a real-time snapshot of course components, and may be different than previous or future course compilations. The following will not be included in the course compile:
   <ul>
     <li>Activities and resources that have restricted access (that are not accessible at the time of the compile)
     </li>
     <li>Files, e.g. Word and PDF documents and PowerPoints
     </li>
     <li>User generated data, e.g. discussion forum posts
     </li>
   </ul>
</div>';

// HTML for a section header within the compiled output.
$string['sectionheader'] = '
<div class="compile_section_head">
  <hr>
  <h2>{$a}</h2>
  <hr>
</div>
';

// HTML for a PDF course header.
$string['pdfheader'] = '
<div id="compile_course_head_pdf">
  <div id="logo">
    <img src="{$a->moodleroot}/theme/rru/pix/header-logo.png">
  </div>
  <div id="coursetitle">
    <h2>{$a->title}</h2>
  </div>
</div>
';

// HTML for a PDF course footer.
$year = date("Y");
$string['pdffooter'] = '
<div id="compile_course_foot_pdf">
  <div id="compile_foot_disclaimer">
    Copyright &copy; '.$year.' Royal Roads University.  All Rights Reserved.<br/>
    The University reserves the right to amend course outlines from time to time without notice.
  </div>
</div>
';

// HTML to close a PDF.
$string['pdfclose'] = "\n</body>\n</html>";

// HTML for the start of the course wrapper.
$string['wrapper_start'] = '<div class="compile_content"><!-- Course Wrapper -->';

// HTML for the end of the course wrapper.
$string['wrapper_end'] = '</div><!-- Course Wrapper -->';

// Link caption for the link in the Course Admin block.
$string['menucaption'] = 'Compile';

// This plug-in's name.
$string['pluginname'] = 'Course Compile and Print';

// HTML for the top of main content on the module selector page.
$string['modselectheader'] = '<h2>Compile course: {$a}</h2>';

// General date format.
$string['compile_dateformat'] = 'D, j M Y g:i A';

/*
 * For all module templates: common description/intro format
 */
$string['intro'] = '<div class="compile_intro">{$a}</div>';

/*
 * Compile Module Template: Quiz
 */

// Quiz Open date.
$string['quiz_open'] = '<p>Opens: <em>{$a}</em></p>';

// Quiz Close date.
$string['quiz_close'] = '<p>Closes: <em>{$a}</em></p>';

// Quiz - No open date.
$string['quiz_noopen'] = '<p>No opening date.</p>';

// Quiz - No close date.
$string['quiz_noclose'] = '<p>No closing date.</p>';

// Quiz - Open dates wrapper.
$string['quiz_openwrap'] = '<div id="compile_quiz_times">';

// Quiz - Close dates wrapper.
$string['quiz_closewrap'] = '</div>';

/*
 * Compile Module Template: Resource
 */

// Resource - File name.
$string['resource_filename'] = '<p class="compile_url">{$a}</p>';

// Survey.
$string['surveyquestiontypeerror'] = 'This question type not supported yet';
/*
 * Compile Module Template: Wiki
 */

// Wiki - Wiki Mode.
$string['wiki_mode'] = '<p>Wiki Mode: {$a}</p>';

// Wiki - First Page Title.
$string['wiki_firstpagetitle'] = '<p>First Page Title: {$a}</p>';

/*
 * Compile Module Template: External Tool (LTI)
 */

// External Tool - Launch URL.
$string['lti_url'] = '<p>Launch URL: {$a}</p>';

/*
 * Compile Module Template: Forum.
 */

// Disclaimer.
$string['forum_disclaimer'] = '<p class="forum-compile-disclaimer">Contents of the forum are not included in compile.
        To see the content, please visit the course.</p>';

/*
 * Compile Module Template: Assign.
 */
// Due date.
$string['assign_duedate'] = '<p>Due: {$a}</p>';

// No due date/.
$string['assign_noduedate'] = '<p>No due date specified.</p>';


