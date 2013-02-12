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
 * settings.php, adds user updateable admin settings for Draft Posts Plug-in
 *
 *
 * 2011-11-21
 * @package      plug-in
 * @subpackage   RRU_DRAFTPOSTS
 * @copyright    2011 Andrew Zoltay, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $DB, $CFG;
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs this condition or there is error on login page
    $settings = new admin_settingpage('draftposts', get_string('pluginname', 'local_draftposts'));
    $ADMIN->add('localplugins', $settings);

    // Add a setting to handle the duration before a draft is saved
    $settings->add(new admin_setting_configtext('local_draftposts/'.'saveinterval', get_string('saveinterval', 'local_draftposts'),
                                            get_string('saveinterval_help', 'local_draftposts'), 180));
    
}
