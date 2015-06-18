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
 *
 * This file controls the settings for the rruopencourses plugin.
 *
 * 2013-09-25
 * @package      plug-in
 * @subpackage   rruopencourses
 * @copyright    2013 Gerald albion, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $DB, $CFG;

if ($hassiteconfig) { // Needs this condition or there is error on login page.
    $settingspagetitle = get_string('settingspagetitle', 'local_rruopencourses');
    $settings = new admin_settingpage('local_rruopencourses', $settingspagetitle);
    $ADMIN->add('localplugins', $settings);

    // Setting: The number of days prior to course start date to make the course available to students
    // (0 = course start date)
    $settings->add(new admin_setting_configtext('local_rruopencourses/roc_opencoursedate', // setting name
        get_string('opendatelabel', 'local_rruopencourses'), // display name
        get_string('opendatehelp', 'local_rruopencourses'), // description
        0, // default value
        PARAM_INT)); // Value type.

    // Setting: Email address(es) of people who should receive notifications of course openings.
    // Multiple addresses should be separated by ';'
    $settings->add(new admin_setting_configtext('local_rruopencourses/roc_emails', // setting name
        get_string('emailslabel', 'local_rruopencourses'), // display name
        get_string('emailshelp', 'local_rruopencourses'), // description
        '')); // default value - empty.
}
