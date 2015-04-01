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
 * The log_listmodules event.
 *
 * @package    compile
 * @copyright  2014 Royal Roads University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace compile\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The log_listmodules event class.
 *
 * @property-read array $other {
 *      Event to log listing modules in Course Compile
 * }
 *
 * @since     Moodle 2.7.2+
 * @copyright 2014 Royal Roads University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class log_listmodules extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'course';
    }

    public static function get_name() {
        return get_string('eventlog_listmodules', 'compile');
    }

    public function get_description() {
        return "User ID {$this->userid} viewed the list of compilable modules in course id {$this->courseid}.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('local/compile/list_modules.php', array('id' => $this->courseid));
    }

    /**
     * Return legacy data for add_to_log().
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid,
                      'compile',
                      'List Modules',
                       $this->objectid,
                       $this->contextinstanceid);
    }
}