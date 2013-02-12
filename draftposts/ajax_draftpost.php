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

global $DB;

$sesskey = required_param('sesskey', PARAM_ALPHA);
$action = optional_param('action', false, PARAM_ALPHA);


// Make sure we have a valid session
if (!isset($sesskey) || !confirm_sesskey()) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit();
}

// Get the course object - stop access if course not found 
if (!$course = $DB->get_record('course', array('id'=>$SESSION->draftpost->courseid))) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit();
}

// Get the forum object - stop access if forum not found
if (!$forum = $DB->get_record("forum", array("id"=>$SESSION->draftpost->forumid))) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit();
}

// Get the course module object - stop access if forum not found
if (!$cm = get_coursemodule_from_instance("forum", $forum->id, $course->id)) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
    exit();
}

// Make sure user has permission to start a discussion 
// or reply to a post for this forum
$modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
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

// Make sure the user is logged in and has permission to work with course
require_login($course);

// Get ready to do the work
$draft = new stdClass();
$draft->discussionid = $SESSION->draftpost->discussionid;
$draft->forumid = $SESSION->draftpost->forumid;
$draft->groupid = $SESSION->draftpost->groupid;
$draft->parentid = $SESSION->draftpost->parentid;
$draft->userid = $USER->id;

// Build get SQL criteria
$sqlconditions = array(
    'discussionid' => ($draft->discussionid ? $draft->discussionid : 0),
    'forumid' => ($draft->forumid ? $draft->forumid : 0),
    'groupid' => ($draft->groupid ? $draft->groupid : 0),
    'parentid' => ($draft->parentid ? $draft->parentid : 0),
    'userid' => ($draft->userid ? $draft->userid : 0)
);

switch ($action) {
    case 'savedraft':
        $subject = optional_param('subject', null, PARAM_RAW);
        $message = optional_param('message', null, PARAM_RAW);

        $draft->subject = $subject;
        $draft->message = $message;
        $draft->lastupdated = time();
        
        $json_object = new stdClass();

        // If record already exists then update it otherwise insert a new record
        if ($draftid = $DB->get_field('rrudraft_forum_posts', 'id', $sqlconditions)) {
            $draft->id = $draftid;

            if ($DB->update_record('rrudraft_forum_posts', $draft)) {
               $json_object->result = get_string('savesuccess', 'local_draftposts');
            } else {
               $json_object->error = get_string('savefail', 'local_draftposts');
            }
        } else {
            if ($DB->insert_record('rrudraft_forum_posts', $draft)) {
                $json_object->result = get_string('savesuccess', 'local_draftposts');
            } else {
                $json_object->error = get_string('savefail', 'local_draftposts');
            }
        }
        echo json_encode($json_object);
        break;

    case 'getdraft':
        if ($draftrecord = $DB->get_record('rrudraft_forum_posts', $sqlconditions, 'subject, message')) {
            echo json_encode($draftrecord);
        } else {
            $json_object->error = get_string('getfail', 'local_draftposts');
            echo json_encode($json_object);
        }
        break;

    default:
        // unsupported action
        debugging(DEBUG_DEVELOPER, 'Unsupported action in draftpost_ajax.php');
        break;

}