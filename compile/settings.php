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
 * Settings of the module
 * Here, we extend the settings for the the Course Compile and Print plugin.
 * These settings control which module types are included in a 'Course Compile'.
 * We add a custom admin page, then we add a setting for each installed module type.  Additionally
 * we check to see if a custome 'compile.php' exists for the module type, and if so, we enable the
 * module type by default.
 *
 * 2011-06-03
 * @package      local_compile
 * @copyright    2014 Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $DB, $CFG;

if ($hassiteconfig) { // Needs this condition or there is error on login page.
    $settings = new admin_settingpage('local_compile', 'Compile and Print');
    $ADMIN->add('localplugins', $settings);
    // Select Modules.
    if ($allmods = $DB->get_records("modules")) {
        foreach ($allmods as $mod) {
            if ((file_exists("$CFG->dirroot/mod/$mod->name/lib.php")) && ($mod->visible)) {
                $settings->add(new admin_setting_configcheckbox(
                    "local_compile/".$mod->name,
                    "Compile ". get_string("modulenameplural", "$mod->name"),
                    "Include ". get_string("modulenameplural", "$mod->name"). " in Compile and Print",
                file_exists("$CFG->dirroot/local/compile/mod/$mod->name/compile.php")));
            }
        }
    }

    // Manage domain blacklist.
    $settings->add(new admin_setting_configtextarea(
            'local_compile/blacklistresources', // Setting name.
            get_string('excludedomainscaption', 'local_compile'), // Display name.
            get_string('excludedomainsverbose', 'local_compile'), // Description.
            '' // Fefault value.
                  ) );

}
