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
 * Compile book module
 *
 * @package    local_compile
 * @subpackage mod_book
 * @copyright  2014 Royal Roads University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../../../config.php");
require_once('../../lib.php');
require_once("../../../../mod/book/locallib.php");
require_once($CFG->libdir.'/completionlib.php');

defined('MOODLE_INTERNAL') || die(); // Must load config.php first.

// Get and qualify Course Module ID.
$modname = 'book'; // Set to the name of the module.
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


$cm     = get_coursemodule_from_id('book', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$book   = $DB->get_record('book', array('id' => $cm->instance), '*', MUST_EXIST);

$context = context_module::instance($cm->id);
$PAGE->set_context($context);

$viewhidden = has_capability('mod/book:viewhiddenchapters', $context);

// Read chapters.
$chapters = book_preload_chapters($book);

// Empty book?
if (count($chapters) == 0) {
    $errormsg = get_string('nocontent', 'mod_book');
    die($errormsg);
}

// Security checks END.

// Prepare chapter navigation icons.
$previd = null;
$nextid = null;
$last   = null;

// Is the user a student?
$userroles = get_user_roles(context_course::instance($course->id), $USER->id);
$isstudent = false; // Initialize.
if (!empty($userroles)) { // No roles, not a student.
    foreach($userroles as $userrole) {
        if ($userrole->shortname == 'student') {
            $isstudent = true;
            break; // Found 'student' among user's roles in this course, break out.
        }
    }
}

foreach ($chapters as $ch) {
    // If student, don't display hidden chapter.
    if ($isstudent) {
        if ($ch->hidden == 1) {
            continue;
        }
    }

    $chapter = $DB->get_record('book_chapters', array('id' => $ch->id, 'bookid' => $book->id));
    $sec = '';
    if ($section = $DB->get_record('course_sections', array('id' => $cm->section))) {
        $sec = $section->section;
    }

    // We are cheating a bit here, viewing the last page means user has viewed the whole book.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

    // The chapter itself.
    echo $OUTPUT->box_start('generalbox book_content');
    if (!$book->customtitles) {
        $hidden = $ch->hidden ? ' dimmed_text' : '';
        if (!$ch->subchapter) {
            $currtitle = book_get_chapter_title($ch->id, $chapters, $book, $context);
            echo '<h3 class="book_chapter_title'.$hidden.'">'.$currtitle.'</h3>';
        } else {
            $currtitle = book_get_chapter_title($chapters[$ch->id]->parent, $chapters, $book, $context);
            $currsubtitle = book_get_chapter_title($ch->id, $chapters, $book, $context);
            echo '<h3 class="book_chapter_title'.$hidden.'">'.$currtitle.'<br />'.$currsubtitle.'</h3>';
        }
    }

    $chaptertext = file_rewrite_pluginfile_urls($chapter->content, 'pluginfile.php', $context->id, 'mod_book', 'chapter', $ch->id);
    echo format_text($chaptertext, FORMAT_HTML);
    echo $OUTPUT->box_end();
}
