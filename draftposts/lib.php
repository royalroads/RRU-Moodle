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
 * lib.php, collection of functions specific to draft posts.
 *
 * This library file is primarily used to define hooks for the
 * forum module to call in order to support draft posts.
 *
 * 2011-11-23
 * @package      plug-in
 * @subpackage   RRU_DRAFTPOSTS
 * @copyright    2011 Andrew Zoltay, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Hook to add a button to support saving a draft post
 *
 * @author Andrew Zoltay
 * date    2011-11-23
 * @param @$postform moodleform object - passed by reference to add the button
 */
function dp_return_save_draft_button(&$postform) {

    // Add the save draft button.
    $postform->insertElementBefore($postform->createElement('button', 'savedraft',
                        get_string('savedraft', 'local_draftposts'),
                        'class="savedraftbtn", onclick="M.local_draftposts.save_draft();"'),
                        'submitbutton');

}

/**
 * Hook to add a button to support viewing draft posts
 * NOTE: makes assumption of being called from /mod/forum files
 *
 * @author Andrew Zoltay
 * date    2011-12-06
 * @global outputobject $OUTPUT - for nice formatting
 * @param int $forumid - unique id for forum that has draft posts
 * @param int $discussionid - unique id for discussion that has draft posts
 */
function dp_print_drafts_link($forumid, $discussionid = null) {
    global $OUTPUT;

    $querylink = 'f=' . $forumid;
    if ($discussionid) {
        $querylink = $querylink . '&d=' . $discussionid;
    }

    $title = get_string('draftslinktitle', 'local_draftposts');
    $titlehelp = get_string('draftslinktitle_help', 'local_draftposts');

    $link = '<a href="../../local/draftposts/draftposts.php?' . $querylink .
            '" title="' . $titlehelp . '" class="draftposts-link">' . $title . '</a>';

    echo $OUTPUT->box($link, 'generalbox draftposts-box', 'draftposts-boxlink');
}

/**
 * Print out draft posts for a forum/discussion for a user
 * NOTE: headers are printed based parentid order in $drafts
 * - did not follow Moodle 2 htmlwriter as it doesn't appear to support CSS classes
 *
 * @author Andrew Zoltay
 * date    2011-12-05
 * @param array of draft objects $drafts
 * @param int $discussionsource - used to determine source of drafts for returning to source
 */

function dp_print_draftposts($drafts, $discussionsource) {
    $printdiscussiontitle = true;
    $printreplytitle = true;

    $repliestitle = get_string('replies', 'local_draftposts');
    $discussionstitle = get_string('discussions', 'local_draftposts');
    $deletetext = 'Delete';
    $deletehint = get_string('deletehint', 'local_draftposts');
    $restoretext = get_string('restore', 'local_draftposts');
    $restorehint = get_string('restorehint', 'local_draftposts');

    // Id, subject, discussionid, parentid, forumid, lastupdated.
    foreach ($drafts as $draft) {
        // Determine if we're dealing with a new discussion, an existing discussion or a reply.
        if ($draft->parentid) {
            // Replies.
            if ($printreplytitle) {
                // Only do this once.
                $printreplytitle = false;
                // Close discussions table if it exists.
                if (!$printdiscussiontitle) {
                    echo '</tbody>';
                    echo '</table>';
                }
                echo '<h2 class="draftpost-table-title">' . $repliestitle . '</h2>';
                // Include 'thread' column for replies.
                dp_print_drafts_header('draftposts-table-reply', true);
            }
            // Added by CCH 14.05.14.
            if ($draft->postid > 0) {
                $restorehtml = '<a href="../../mod/forum/post.php?edit=' . $draft->postid . '&groupid=' . $draft->groupid . '&draftid=' . $draft->id .
                '" title="' . $restorehint . '">';
            } else {
                $restorehtml = '<a href="../../mod/forum/post.php?reply=' . $draft->parentid . '&groupid=' . $draft->groupid . '&draftid=' . $draft->id .
                '" title="' . $restorehint . '">';
            }
            $threadthtml = '<a href="../../mod/forum/discuss.php?d=' . $draft->discussionid . '">' . $draft->discussionname . '</a>';
        } else {
            // Discussions.
            if ($printdiscussiontitle) {
                // Only do this once.
                $printdiscussiontitle = false;
                echo '<h2 class="draftpost-table-title">'. $discussionstitle . '</h2>';
                dp_print_drafts_header('draftposts-table-discussion');
            }
            // Check if it's a new discussionn or an existing one.
            if ($draft->firstpost) {
                $restorehtml = '<a href="../../mod/forum/post.php?edit=' . $draft->firstpost . '&draftid=' . $draft->id;
                if ($draft->groupid != 0) {
                    $restorehtml = $restorehtml . '&groupid=' . $draft->groupid;
                }
                $restorehtml = $restorehtml . '" title="' . $restorehint . '">';
            } else {
                $restorehtml = '<a href="../../mod/forum/post.php?forum=' . $draft->forumid . '&groupid=' . $draft->groupid . '&draftid=' . $draft->id .
                        '" title="' . $restorehint . '">';
            }
        }

        // Need to know how we got to draftposts.php so we know what to display after we delete the draft.
        // Need to maintain context.
        if ($discussionsource) {
            $deletehtml = '<a href="draftposts.php?f=' . $draft->forumid . '&d=' . $discussionsource .
                    '&delete=' . $draft->id . '" title="' . $deletehint . '">'. $deletetext . '</a>';
        } else {
            $deletehtml = '<a href="draftposts.php?f=' . $draft->forumid .
                    '&delete=' . $draft->id . '" title="' . $deletehint . '">'. $deletetext . '</a>';
        }

        echo '<tr>';
        echo '<td class="cell draftposts-cell">' . $restorehtml . $draft->subject . '</a></td>';
        if ($draft->parentid) {
            // Display thread column for replies.
            echo '<td class = "cell draftposts-thread-cell">' . $threadthtml . '</td>';
        }
        echo '<td class ="cell draftposts-cell">' . userdate($draft->lastupdated) . '</td>';
        echo '<td class ="cell draftposts-cell-action">' . $restorehtml . $restoretext . '</a> | ' . $deletehtml . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

}

/**
 * Prints HTML table header for drafts
 *
 * @author Andrew Zoltay
 * date    2011-12-08
 * @param string $classname - CSS class name for table
 * @param boolean $isreplies - indicate whether or not to include threads column
 */
function dp_print_drafts_header($classname, $isreplies = false) {
    // Header.
    echo '<table cellspacing="0" class="' . $classname .'">';
    echo '<thead>';
    echo '<tr>';
    echo '<th class="header topic" scope="col">'.get_string('draftdiscusstopic', 'local_draftposts').'</th>';
    if ($isreplies) {
        echo '<th class="header date" scope="col">'.get_string('thread', 'local_draftposts').'</th>';
    }
    echo '<th class="header date" scope="col">'.get_string('date', 'local_draftposts').'</th>';
    echo '<th class="header actions" scope="col">'.get_string('actions', 'local_draftposts').'</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
}

/**
 * Delete a draft post
 * NOTE: checks to make sure user is same person who created the draft
 * Uses $draftid parameter if it is set otherwise it uses remaining parameters
 *
 * @author Andrew Zoltay
 * date    2011-12-19
 * @global dbobject $DB
 * @global userobject $USER
 * @param int $draftid
 * @param int $forumid
 * @param int $groupid
 * @param int $discussionid
 * @param int $parentid
 * @return int 1 = success, 0 = fail, -1 = user doesn't match the draft user
 */
function dp_delete_draft($draftid = null, $forumid = 0, $groupid = 0, $discussionid = 0, $parentid = 0) {
    global $DB, $USER;

    // Prep criteria for what to delete.
    if ($draftid) {
        $where = 'id = ? and userid = ?';
        $params = array($draftid, $USER->id);
    } else if ($parentid == 0) {
        // Delete discussion draft and any replies to it.
        $where = 'forumid = ? and discussionid = ? and groupid = ? and userid = ?';
        $params = array($forumid, $discussionid, $groupid, $USER->id);
    } else {
        // Delete reply draft.
        $where = 'forumid = ? and discussionid = ? and parentid = ? and groupid = ? and userid = ?';
        $params = array($forumid, $discussionid, $parentid, $groupid, $USER->id);
    }
    // AZFuture - need to handle delete reply and any of its replies
    // - may need to write a new function for 'clean-up' purposes
    // - may by outside the application as some drafts are left by users after course has finished.

    // Only the person who created the draft can see it.
    if ($USER->id != $DB->get_field_select('rrudraft_forum_posts', 'userid', $where, $params)) {
        return -1;
    }

    // Delete the draft and return result to caller.
    return $DB->delete_records_select('rrudraft_forum_posts', $where, $params);

}

/**
 * Delete any drafts (user independant) for a specific dicussion
 * so we don't leave orphan drafts around when someone deletes a disucssion thread
 *
 * @author Andrew Zoltay
 * date    2011-12-19
 * @global dbobject $DB
 * @global userobject $USER
 * @param type $forumid
 * @param type $groupid
 * @param type $discussionid
 * @return int 1 = success, 0 = fail, -1 = user doesn't match the draft user
 */
function dp_delete_thread_drafts($forumid = 0, $groupid = 0, $discussionid = 0) {
    global $DB, $USER;

    // Only the person who created the discussion should have the power to delete the drafts.
    $where = 'forum = ? and id = ? and groupid = ? and userid = ?';
    $params = array($forumid, $discussionid, $groupid, $USER->id);

    if ($USER->id != $DB->get_field_select('forum_discussions', 'userid', $where, $params)) {
        return -1;
    }

    // Delete the discussion draft and any replies to it regardless of which user created it.
    $where = 'forumid = ? and discussionid = ? and groupid = ?';
    $params = array($forumid, $discussionid, $groupid);

    return $DB->delete_records_select('rrudraft_forum_posts', $where, $params);
}