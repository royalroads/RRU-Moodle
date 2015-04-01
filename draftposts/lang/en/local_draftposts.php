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
 * local_draftposts.php, collection of language strings for Draft Posts Plug-in
 *
 *
 * 2011-11-21
 * @package      plug-in
 * @subpackage   RRU_DRAFTPOSTS
 * @copyright    2011 Andrew Zoltay, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'RRU Draft Posts';

// Admin Settings.
$string['saveinterval'] = 'Save interval';
$string['saveinterval_help'] = 'Enter the interval duration between draft saves(in seconds)';

// Event: log_forumdrafts_viewed.
$string['eventlog_forumdrafts_viewed'] = 'The user viewed the list of forum drafts';
// Event: log_draft_deleted.
$string['eventlog_draft_deleted'] = 'Delete forum draft';

// Drafts general usage.
$string['actions'] = 'Actions';
$string['confirmdelete'] = 'Are you sure you want to delete this draft?';
$string['confirmload'] = 'Do you want to load an unfinished draft?';
$string['date'] = 'Date';
$string['discussions'] = 'Discussions';
$string['draftdiscusstopic'] = 'Discussion topic';
$string['draftsheader'] = 'Drafts';
$string['draftslinktitle'] = 'View my drafts';
$string['draftslinktitle_help'] = "View my draft discussions and posts";
$string['deletefail'] = 'Failed to delete draft';
$string['deletehint'] = 'Delete draft';
$string['deletesuccess'] = 'Draft was deleted';
$string['denied'] = 'You do not have permission to delete this draft';
$string['getfail'] = 'Failed to retrieve draft';
$string['eventforumdraftsviewed'] = 'The user viewed the list of forum drafts';
$string['nodrafts'] = '(No discussion drafts have been saved)';
$string['replies'] = 'Replies';
$string['restore'] = 'Restore';
$string['restorehint'] = 'Restore draft';
$string['savedraft'] = 'Save draft';
$string['saveddraftstitle'] = "My drafts";
$string['savefail'] = 'Failed to save draft';
$string['savesuccess'] = 'Draft saved';
$string['thread'] = 'Thread';
