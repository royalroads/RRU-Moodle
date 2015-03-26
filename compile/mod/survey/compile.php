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
 * Compile survey module
 *
 * @package    local_compile
 * @subpackage mod_survey
 * @author     Carlos Chiarella
 * @copyright  2014 Royal Roads University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../../config.php');
require_once('../../lib.php');
require_once($CFG->dirroot.'/mod/survey/lib.php');

defined('MOODLE_INTERNAL') || die(); // Must load config.php before checking MOODLE_INTERNAL.
$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
$highlightrequired = false;

// Validations checks. It validates that the course module id, course id and feedback record exist.
if ($id) {
    if (! $cm = get_coursemodule_from_id('survey', $id)) {
        die(get_string('invalidcoursemodule', 'error'));
    }
    if (! $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST)) {
        die(print_error('coursemisconf'));
    }
    if (! $survey = $DB->get_record('survey', array('id' => $cm->instance), '*', MUST_EXIST)) {
        die(print_error('invalidcoursemodule'));;
    }
    if (! $surveytype = $DB->get_record('survey', array('id' => $survey->template), '*', MUST_EXIST)) {
        die(get_string('invalidtmpid', 'survey'));
    }
} else {
    die("Invalid course module ID.");
}

// The objects $context and $PAGE are set because they are needed for $OUTPUT.
$context = context_module::instance($cm->id);
$PAGE->set_context($context);

$intro = compile_activity_intro($cm);

// Output module content: Intro (description).
print get_string('intro', 'local_compile', $intro);

print get_string('surveytype', 'survey') . ': ' . get_string($surveytype->name, 'survey');
//  Start the survey display.

echo '<div>'. get_string('allquestionrequireanswer', 'survey'). '</div>';

// Get all the major questions and their proper order.
if (! $questions = $DB->get_records_list("survey_questions", "id", explode(',', $survey->questions))) {
    print_error('cannotfindquestion', 'survey');
}
$questionorder = explode( ",", $survey->questions);

// Cycle through all the questions in order and print them.

global $qnum;  // TODO: ugly globals hack for survey_print_*().
$qnum = 0;
foreach ($questionorder as $key => $val) {
    $question = $questions["$val"];
    $question->id = $val;

    if ($question->type >= 0) {

        if ($question->text) {
            $question->text = get_string($question->text, "survey");
        }

        if ($question->shorttext) {
            $question->shorttext = get_string($question->shorttext, "survey");
        }

        if ($question->intro) {
            $question->intro = get_string($question->intro, "survey");
        }

        if ($question->options) {
            $question->options = get_string($question->options, "survey");
        }

        if ($question->multi) {
            compile_survey_print_multi($question);
        } else {
            compile_survey_print_single($question);
        }
    }
}

/**
 * Prints a table that contains the survey question.
 *
 * @author Carlos Chiarella
 * date 2014-11-128
 * @param object $question
 * @global object $DB Moodle database object
 * @global $qnum question number
 * @global $checklist to use for printing the survey
 * @global object $OUTPUT for printing
 */
function compile_survey_print_multi($question) {
    global $DB, $qnum, $OUTPUT; // TODO: this is sloppy globals abuse.

    $stripreferthat = get_string("ipreferthat", "survey");
    $strifoundthat = get_string("ifoundthat", "survey");
    $strresponses  = get_string('responses', 'survey');

    echo $OUTPUT->heading($question->text, 3);
    echo "<table class='surveytable'>";

    $options = explode( ",", $question->options);
    $numoptions = count($options);

    // COLLES Actual (which is having questions of type 1) and COLLES Preferred (type 2)
    // expect just one answer per question. COLLES Actual and Preferred (type 3) expects
    // two answers per question. ATTLS (having a single question of type 1) expects one
    // answer per question. CIQ is not using multiquestions (i.e. a question with subquestions).
    // Note that the type of subquestions does not really matter, it's the type of the
    // question itself that determines everything.
    $oneanswer = ($question->type == 1 || $question->type == 2) ? true : false;

    // COLLES Preferred (having questions of type 2) will use the radio elements with the name
    // like qP1, qP2 etc. COLLES Actual and ATTLS have radios like q1, q2 etc.

    echo "<tr><th>$strresponses</th>";
    echo "<th>". get_string('notyetanswered', 'survey'). "</th>";
    while (list ($key, $val) = each ($options)) {
        echo "<th>$val</th>\n";
    }
    echo "</tr>\n";

    echo "<tr><th colspan='7'>$question->intro</th></tr>\n";

    $subquestions = $DB->get_records_list("survey_questions", "id", explode(',', $question->multi));

    foreach ($subquestions as $q) {
        $qnum++;

        if ($q->text) {
            $q->text = get_string($q->text, "survey");
        }

        echo "<tr>";
        if ($oneanswer) {
            echo "<th>";
            echo "$qnum &nbsp; ";
            echo $q->text ."</th>\n";

            echo "<td><label></label></td>";
            for ($i = 1; $i <= $numoptions; $i++) {
                echo "<td><label></label></td>";
            }
        } else {
            echo "<th>";
            echo "$qnum";
            $qnum++;
            echo "<span>$stripreferthat</span> &nbsp; ";
            echo "<span>$q->text</span></th>\n";

            echo '<td><label></label></td>';

            for ($i = 1; $i <= $numoptions; $i++) {
                echo "<td><label></label></td>";
            }
            echo "</tr>";

            echo "<tr>";
            echo "<th>";
            echo "$qnum";
            echo "<span>$strifoundthat</span> &nbsp; ";
            echo "<span>$q->text</span></th>\n";

            echo "<td><label></label></td>";

            for ($i = 1; $i <= $numoptions; $i++) {
                echo "<td><label></label></td>";
            }

        }
        echo "</tr>\n";
    }
    echo "</table>";
}

/**
 * Prints a table that contains the survey question.
 *
 * @author Carlos Chiarella
 * date 2014-11-128
 * @param object $question
 * @global $qnum question number
 * @global object $OUTPUT for printing
 */
function compile_survey_print_single($question) {
    global $qnum, $OUTPUT;

    $qnum++;

    echo "<br />\n";
    echo "<table class='surveytable'>";
    echo "<tr>";
    echo "<th><label><b>$qnum</b> &nbsp;";
    echo "<span><b>$question->text</b></span></label></th>\n";
    echo "<td><span>";

    if ($question->type == 0) {           // Plain text field.
        echo $question->options;

    } else if ($question->type > 0) {     // Choose one of a number.
        $options = explode( ",", $question->options);
        foreach ($options as $key => $val) {
            $key++;
            echo $val . ' ';
        }
    } else if ($question->type < 0) {     // Choose several of a number.
        $options = explode( ",", $question->options);
        echo $OUTPUT->notification(get_string('surveyquestiontypeerror', 'local_compile'));
    }

    echo "</span></td></tr></table>";

}
