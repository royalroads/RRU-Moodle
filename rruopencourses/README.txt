/ This file is part of Moodle - http://moodle.org/
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
 * Readme file for RRU Open Courses plugin
 *
 * @package    local/rruopencourses
 * @copyright  2015 Gerald Albion, Royal roads University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

Description of the plugin:
Formerly, when a course was ready to be made available for students, a staff member had to manually make the course visible.  
The process of identifying which courses should be made visible and then opening the courses to students had been tedious and error-prone.  
This plug-in provides that functionality. Its operation is straightforward: Once a day, it checks the course table to determine which courses, 
if any, are scheduled to start that day.  It then makes those courses visible, and sends a report by email to one or more administrators.  
Any problems are reported to the site administrator by email.
This plugin uses the setting number of days in advance to open the course (roc_opencoursedate )and the start date field of the course table to query the database.
