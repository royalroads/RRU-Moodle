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
 * The log_forumdrafts_viewed event.
 *
 * @package    draftposts
 * @copyright  2014 Royal Roads University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_draftposts\event;

defined('MOODLE_INTERNAL') || die();
/**
 * The log_forumdrafts_viewed event class.
 *
 * @property-read array $other {
 *      Event to log viewing a list of forum drafts for a  specific forum
 * }
 *
 * @since     Moodle 2.7.2+
 * @copyright 2014 Royal Roads University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class log_forumdrafts_viewed extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'c'; // Possible values: c(reate), r(ead), u(pdate), d(elete).
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'rrudraft_forum_posts';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventlog_forumdrafts_viewed', 'local_draftposts');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' viewed the list of draft posts for this forum " .
        "id '$this->contextinstanceid'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('local/draftposts/draftposts.php', array('id' => $this->contextinstanceid));
    }

    /**
     * Return legacy data for add_to_log().
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid,
        'draftposts',
        'View forum draft posts',
        $this->objectid,
        $this->contextinstanceid);
    }
}