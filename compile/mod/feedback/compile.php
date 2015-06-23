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
 * Compile feedback module
 *
 * @package    local_compile
 * @subpackage mod_feedback
 * @author     Carlos Chiarella
 * @copyright  2014 Royal Roads University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once('../../lib.php');
require_once($CFG->dirroot.'/mod/feedback/lib.php');

defined('MOODLE_INTERNAL') || die(); // Must load config.php before checking MOODLE_INTERNAL.
$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
$highlightrequired = false;

// Define a constant for additional check for multiple-submit (prevent browsers back-button).
define('STARTPOSITION', 0);
// Define a constant to indicate that the $feedbackitem record has a value.
define('FEEDBACKITEMHASVALUE', 1);
// Validations checks. It validates that the course module id, course id and feedback record exist.
if ($id) {
    if (! $cm = get_coursemodule_from_id('feedback', $id)) {
        die(get_string('invalidcoursemodule', 'error'));
    }
    if (! $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST)) {
        die(print_error('coursemisconf'));
    }
    if (! $feedback = $DB->get_record('feedback', array('id' => $cm->instance), '*', MUST_EXIST)) {
        die(print_error('invalidcoursemodule'));
    }
} else {
    die("Invalid course module ID.");
}

// The objects $context and $PAGE are set because they are needed for $OUTPUT.
$context = context_module::instance($cm->id);
$PAGE->set_context($context);

// Validation checks end.

$intro = compile_activity_intro($cm);

// Output module content: Intro (description).
print get_string('intro', 'local_compile', $intro);

// Get the feedbackitems after the last shown pagebreak.
$select = 'feedback = ? AND position > ?';
$params = array($feedback->id, STARTPOSITION);
$feedbackitems = $DB->get_records_select('feedback_item', $select, $params, 'position');

// Print the items.
if (is_array($feedbackitems)) {
    // Check, if there exists required-elements.
    $params = array('feedback' => $feedback->id, 'required' => 1);
    $countreq = $DB->count_records('feedback_item', $params);
    if ($countreq > 0) {
        echo '<span class="feedback_required_mark">';
        echo get_string('somefieldsrequired', 'form', '<img alt="'.get_string('requiredelement', 'form').
                '" src="'.$OUTPUT->pix_url('req') .'" class="req" />');
        echo '</span>';
    }
    echo $OUTPUT->box_start('feedback_items');

    $select = 'feedback = ? AND hasvalue = 1 AND position < ?';
    $params = array($feedback->id, STARTPOSITION);
    $itemnr = $DB->count_records_select('feedback_item', $select, $params);
    $lastbreakposition = 0;
    $align = right_to_left() ? 'right' : 'left';

    foreach ($feedbackitems as $feedbackitem) {

        if ($feedbackitem->dependitem > 0) {
            // Check if the conditions are ok.
            $fbcomparevalue = feedback_compare_item_value($feedbackcompletedtmp->id,
                                                            $feedbackitem->dependitem,
                                                            $feedbackitem->dependvalue,
                                                            true);
            if (!isset($feedbackcompletedtmp->id) OR !$fbcomparevalue) {
                $lastitem = $feedbackitem;
                $lastbreakposition = $feedbackitem->position;
                continue;
            }
        }

        echo $OUTPUT->box_start('feedback_item_box_'.$align);
        $value = '';

        if ($feedbackitem->hasvalue == FEEDBACKITEMHASVALUE AND $feedback->autonumbering) {
            $itemnr++;
            echo $OUTPUT->box_start('feedback_item_number_'.$align);
            echo $itemnr;
            echo $OUTPUT->box_end();
        }
        if ($feedbackitem->typ != 'pagebreak') {
            echo $OUTPUT->box_start('box generalbox boxalign_'.$align);
            feedback_print_item_complete($feedbackitem, $value, $highlightrequired);
            echo $OUTPUT->box_end();
        }

        echo $OUTPUT->box_end();

        $lastbreakposition = $feedbackitem->position; // Last item-pos (item or pagebreak).

    }
    echo $OUTPUT->box_end();

}

