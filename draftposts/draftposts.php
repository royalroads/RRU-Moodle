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
 * draftposts.php, displays a list of draft posts for the user to view/action
 *
 *
 * 2011-12-05
 * @package      plug-in
 * @subpackage   RRU_DRAFTPOSTS
 * @copyright    2011 Andrew Zoltay, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');
require_once('classes/event/log_forumdrafts_viewed.php');
require_once('classes/event/log_draft_deleted.php');

defined('MOODLE_INTERNAL') || die();

define('NEW_DISCUSSION_DRAFTS', 0);

$forumid = optional_param('f', 0, PARAM_INT);             // Forum ID.
$discussionid = optional_param('d', 0, PARAM_INT);        // Discussion ID.
$draftid = optional_param('dr', null, PARAM_INT);         // Draft ID.
$delete = optional_param('delete', 0, PARAM_INT);         // What ID to delete.
$confirm = optional_param('confirm', 0, PARAM_INT);       // Confirm ID to delete.


$params = array();
if ($draftid) {
    $params['dr'] = $draftid;
}
if ($forumid) {
    $params['f'] = $forumid;
}
if ($discussionid) {
    $params['d'] = $discussionid;
}
if ($delete) {
    $params['delete'] = $delete;
}
if ($confirm) {
    $params['confirm'] = $confirm;
}

$PAGE->set_url('/local/draftposts/draftposts.php', $params);

$discusssource = null;

// Get supporting objects.
if ($forumid) {
    if (! $forum = $DB->get_record("forum", array("id" => $forumid))) {
        print_error('invalidforumid', 'forum');
    }
    if (! $course = $DB->get_record("course", array("id" => $forum->course))) {
        print_error('coursemisconf');
    }
    if (!$cm = get_coursemodule_from_instance("forum", $forum->id, $course->id)) {
        print_error('missingparameter');
    }
} else {
    print_error('missingparameter');
}

// Confirm login.
require_course_login($course, true, $cm);

// Page needs context.
$context1 = context_module::instance($cm->id);
$PAGE->set_context($context1);

// Get strings needed for draft posts page.
$strtitle = get_string('saveddraftstitle', 'local_draftposts');

// Print header.
$pagetitle = $strtitle . ' - ' . $forum->name;

$PAGE->set_title(format_string($pagetitle));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->box(format_string($strtitle), 'generalbox', 'intro');

// Handle deleting draft post.
if ($confirm) {
    // We've got get confirmation from the user before we delete the draft.
    $result = dp_delete_draft($confirm);

    if ($result === true) {
        echo $OUTPUT->notification(get_string('deletesuccess', 'local_draftposts'), 'notifysuccess');
        $event = \local_draftposts\event\log_draft_deleted::create(array(
            'objectid' => $forumid,
            'context' => context_module::instance($cm->id)
        ));
        $event->trigger();
    } else {
        if ($result == -1) {
            $notice = get_string('denied', 'local_draftposts');
        } else {
            $notice = get_string('deletefail', 'local_draftposts');
        }
        echo $OUTPUT->notification($notice);
    }
} else if ($delete) {
    // Confirm with user before we delete draft.
    $draftpostsurl = 'draftposts.php?f=' . $forumid;
    if ($discussionid) {
        $draftpostsurl = $draftpostsurl . '&d=' . $discussionid;
    }
    echo $OUTPUT->confirm(get_string('confirmdelete', 'local_draftposts'),
                 $draftpostsurl . '&confirm=' . $delete,
                 $draftpostsurl);
} else {
    $event = \local_draftposts\event\log_forumdrafts_viewed::create(array(
        'objectid' => $forumid,
        'context' => context_module::instance($cm->id)
        ));
    $event->trigger();
}

// Print out the discussion and replies table(s).
$sqlwhere = "WHERE d.userid = $USER->id
                AND d.forumid = $forum->id ";
if ($discussionid) {
    $sqlwhere = $sqlwhere . "AND d.discussionid = $discussionid ";
    // Need this so we know what to display when a draft is deleted.
    $discusssource = $discussionid;
}

// Get any drafts.
$sql = 'SELECT d.id, d.subject, d.discussionid, d.parentid, d.forumid, d.groupid, fd.name AS discussionname, fd.firstpost,
        d.lastupdated, d.postid
        FROM {rrudraft_forum_posts} d
        LEFT OUTER JOIN {forum_discussions} fd ON (d.discussionid = fd.id)' .
        $sqlwhere .
        ' ORDER BY d.forumid, d.discussionid, d.parentid';
$newdiscussiondrafts = $DB->get_records_sql($sql);

if (!$newdiscussiondrafts) {
    echo get_string('nodrafts', 'local_draftposts');
} else {
    dp_print_draftposts($newdiscussiondrafts, $discusssource);
}
echo $OUTPUT->footer($course);
