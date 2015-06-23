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
 * draft.php, Works with draft.js to handle AJAX db operations
 * for draft posts. Currently gets, updates and inserts.
 *
 *
 * 2011-11-23
 * @package      plug-in
 * @subpackage   RRU_DRAFTPOSTS
 * @copyright    2011 Andrew Zoltay, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

defined('MOODLE_INTERNAL') || die();

global $DB;

$sesskey = required_param('sesskey', PARAM_ALPHA);
$action = optional_param('action', false, PARAM_ALPHA);


// Make sure we have a valid session.
if (!isset($sesskey) || !confirm_sesskey()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit();
}

// Get the course object - stop access if course not found.
if (!$course = $DB->get_record('course', array('id' => $SESSION->draftpost->courseid))) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit();
}

// Get the forum object - stop access if forum not found.
if (!$forum = $DB->get_record("forum", array("id" => $SESSION->draftpost->forumid))) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit();
}

// Get the course module object - stop access if forum not found.
if (!$cm = get_coursemodule_from_instance("forum", $forum->id, $course->id)) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit();
}

// Make sure user has permission to start a discussion
// or reply to a post for this forum.
$modcontext = context_module::instance($cm->id);
if ($SESSION->draftpost->discussionid && $SESSION->draftpost->parentid) {
    if (!has_capability('mod/forum:replypost', $modcontext)) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
        exit();
    }
} else {
    if (!has_capability('mod/forum:startdiscussion', $modcontext)) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
        exit();
    }
}

// Make sure the user is logged in and has permission to work with course.
require_login($course);

// Get ready to do the work.
$draft = new stdClass();
$draft->discussionid = $SESSION->draftpost->discussionid;
$draft->forumid = $SESSION->draftpost->forumid;
$draft->groupid = $SESSION->draftpost->groupid;
$draft->parentid = $SESSION->draftpost->parentid;
$draft->userid = $USER->id;
$draft->id = $SESSION->draftpost->id;
$draft->postid = $SESSION->draftpost->postid;

// Build get SQL criteria.
$sqlconditions = array(
    'discussionid' => ($draft->discussionid ? $draft->discussionid : 0),
    'forumid' => ($draft->forumid ? $draft->forumid : 0),
    'groupid' => ($draft->groupid ? $draft->groupid : 0),
    'parentid' => ($draft->parentid ? $draft->parentid : 0),
    'userid' => ($draft->userid ? $draft->userid : 0),
    'id'     => ($draft->id ? $draft->id : 0)
);

switch ($action) {
    case 'savedraft':
        $subject = optional_param('subject', null, PARAM_RAW);
        $message = optional_param('message', null, PARAM_RAW);

        $draft->subject = $subject;
        $draft->message = $message;
        $draft->lastupdated = time();

        $jsonobject = new stdClass();

        // If $send (created in module.js) is differnt from "New topic" and draft->id > 0 update the record
        // else if only draft->id > 0 is true update the record otherwise insert a new record in the rrudraft_forum_posts table.
        if ($draft->id > 0) {

            if ($DB->update_record('rrudraft_forum_posts', $draft)) {
                $jsonobject->result = get_string('savesuccess', 'local_draftposts');
            } else {
                $jsonobject->error = get_string('savefail', 'local_draftposts');
            }
        } else {
            $draftid = $DB->insert_record('rrudraft_forum_posts', $draft);
            if ($draftid) {
                $SESSION->draftpost->id       = $draftid;
                $jsonobject->result = get_string('savesuccess', 'local_draftposts');
            } else {
                $jsonobject->error = get_string('savefail', 'local_draftposts');
            }
        }
        echo json_encode($jsonobject);
        break;

    case 'getdraft':
        if ($draftrecord = $DB->get_record('rrudraft_forum_posts', $sqlconditions, 'subject, message')) {
            echo json_encode($draftrecord);
        } else {
            $jsonobject->error = get_string('getfail', 'local_draftposts');
            echo json_encode($jsonobject);
        }
        break;

    default:
        // Unsupported action.
        debugging(DEBUG_DEVELOPER, 'Unsupported action in draftpost_ajax.php');
        break;

}