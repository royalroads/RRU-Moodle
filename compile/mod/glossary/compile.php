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
 * Compile glossary module
 *
 * @package    local_compile
 * @subpackage mod_glossary
 * @author     Carlos Chiarella
 * @copyright  2014 Royal Roads University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once('../../lib.php');
require_once($CFG->dirroot.'/mod/glossary/lib.php');

defined('MOODLE_INTERNAL') || die(); // Must load config.php before checking MOODLE_INTERNAL.
$id = optional_param('id', 0, PARAM_INT); // Course Module ID.

// Define a constant to force a standard glossary view.
define('GLOSSARYPRINTPIVOT', 1);

$fullsearch = 0;                                            // Full search (concept and definition) when searching?
$hook       = 'ALL';                                        // The term to look for based on mode. Use for display purposes.
$sortkey    = optional_param('sortkey', '', PARAM_ALPHA);   // Sorted view: CREATION | UPDATE | FIRSTNAME | LASTNAME...
$sortorder  = 'ASC';                                        // It defines the order of the sorting (ASC or DESC)
$offset     = optional_param('offset', 0, PARAM_INT);       // Entries to bypass (for paging purposes)
$page       = -1;                                           // Page to show (for paging purposes)
$show       = optional_param('show', '', PARAM_ALPHA);      // The concept/alias => mode=term hook=$show. Use for display purposes.
$mode       = 'letter';                                     // Term entry mode to search. Use for display purposes.


//  Validations checks. It validates that the course module id, course id and glossary record exist.
if ($id) {
    if (! $cm = get_coursemodule_from_id('glossary', $id)) {
        die(get_string('invalidcoursemodule', 'error'));
    }
    if (! $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST)) {
        die(print_error('coursemisconf'));
    }
    if (! $glossary = $DB->get_record('glossary', array('id' => $cm->instance), '*', MUST_EXIST)) {
        die(print_error('invalidcoursemodule'));
    }
} else {
    die("Invalid course module ID.");
}

$context = context_module::instance($cm->id);

$intro = compile_activity_intro($cm);

// Output module content: Intro (description).
print get_string('intro', 'local_compile', $intro);

// Prepare format_string/text options.
$fmtoptions = array(
    'context' => $context);

// Setting the defaut number of entries per page if not set to force all pages to be displayed.
if ( !$entriesbypage = $glossary->entbypage ) {
    $entriesbypage = $CFG->glossary_entbypage;
}

// Setting the value of the $offset to force all pages to be displayed.
if ($page != 0 && $offset == 0) {
    $offset = $page * $entriesbypage;
}

// Setting the value of $printpivot to a constant. This variable will be used later in the script /mod/glossary/sql.php.
$printpivot = GLOSSARYPRINTPIVOT;
// Setting the value of $tab to a constant. This variable will be used later in the script /mod/glossary/sql.php.
$tab = GLOSSARY_STANDARD_VIEW;

// Setting the display format of the glossary.
$displayformat = $glossary->displayformat;

// Printing the heading.
$strnoentries = get_string("noentries", "glossary");
$strwaitingapproval = get_string('waitingapproval', 'glossary');

// If we are in approval mode, prit special header.
$PAGE->set_context($context);

require($CFG->dirroot.'/mod/glossary/sql.php');

// Printing the entries.
$entriesshown = 0;
$currentpivot = '';

$onetimedisplay = 0;
if ($allentries) {

    foreach ($allentries as $entry) {

        // Setting the pivot for the current entry.
        $pivot = $entry->glossarypivot;
        $upperpivot = core_text::strtoupper($pivot);
        $pivottoshow = core_text::strtoupper(format_string($pivot, true, $fmtoptions));
        // Reduce pivot to 1cc if necessary.
        // The variable $fullpivot is set in mod/glossary/sql.php.
        if ( !$fullpivot ) {
            $upperpivot = core_text::substr($upperpivot, 0, 1);
            $pivottoshow = core_text::substr($pivottoshow, 0, 1);
        }

        // If there's a group break.
        if ( $currentpivot != $upperpivot ) {

            // Print the group break if apply.
            // The variable $printpivot might be changed in mod/glossary/sql.php.
            if ($printpivot) {
                $currentpivot = $upperpivot;
                if ($onetimedisplay == 0 ) {
                    $onetimedisplay = 1;
                } else {
                    echo '</table>';
                }
                echo '<div>';
                echo '<table cellspacing="0" class="glossarycategoryheader">';

                echo '<tr><th>';

                echo $pivottoshow;
                echo "</th></tr></table></div>\n";

                echo '<table class="glossaryentries">';

            }
        }

        // And finally print the entry.
        // Prepare extra data.
        $keyword = '';
        if ($aliases = $DB->get_records_menu("glossary_alias", array("entryid" => $entry->id), '', 'id, alias')) {
            $keyword = implode("\n", $aliases) . "\n";
        }

        echo '<tr><td>' . $entry->concept . '</td><td>' . $entry->definition . 'Keyword: ' .$keyword .'</td></tr>';
        $entriesshown++;
    }
}
if ($onetimedisplay == 1) {
    echo '</table>';
}
if ( !$entriesshown ) {
    echo $OUTPUT->box(get_string("noentries", "glossary"), "generalbox boxaligncenter boxwidthwide");
}

