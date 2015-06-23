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
/**
 * setting.php, settings for Default Courses
 *
 * 2012-06-20
 * @package      plug-in
 * @subpackage   Default_Courses
 * @copyright    2012 Andrew Zoltay, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $DB;

if ($hassiteconfig) { // Needs this condition or there is error on login page.
    $settings = new admin_settingpage('defaultcourses', get_string('pluginname', 'local_defaultcourses'));
    $ADMIN->add('localplugins', $settings);

    // Add a setting to define which courses are default courses.
    $multiselectitems = $DB->get_records_select_menu('course', 'id <> 1', null, 'fullname ASC', 'id, fullname');
    if ($multiselectitems) {
        $settings->add(new admin_setting_configmultiselect('local_defaultcourses/'.'courseids',
                                           get_string('definecourseids', 'local_defaultcourses'),
                                           get_string('definecourseids_help', 'local_defaultcourses'),
                                           array(),
                                           $multiselectitems));
    }

}
