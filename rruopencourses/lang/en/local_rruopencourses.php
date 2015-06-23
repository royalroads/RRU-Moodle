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
 * local_rruopencourses.php, a collection of language strings (en, UTF-8)
 *
 * 2013-09-25
 * @package      plug-in
 * @subpackage   rruopencourses
 * @copyright    2013 Gerald Albion, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// Settings strings
$string['settingspagetitle'] = 'RRU Open Courses';
$string['opendatelabel'] = 'Open course date';
$string['opendatehelp']  = 'The number of days prior to course start date to make the course available to students<br/>(0 = course start date)';
$string['emailslabel']   = 'Email';
$string['emailshelp']    = 'Email address(es) of people who should receive notifications of course openings.<br/>Multiple addresses should be separated by \';\'';

// Notification email components - body
$string['emailbody']      = "<html>\r\n"
                          . "  <head>\r\n"
                          . "    <title>Courses opened to students today</title>\r\n"
                          . "    <style>\r\n"
                          . "      th, td    {font-family:Calibri,Segoe UI,Arial;}\r\n"
                          . "      th        {background:#CCC;color:#000;padding:2px 8px 2px 2px;}\r\n"
                          . "      td        {background:#FFF;color:#000;padding:2px 8px 2px 2px;}\r\n"
                          . "      table     {border-radius:6px;border:solid 1px #AAA;}\r\n"
                          . "    </style>\r\n"
                          . "  </head>\r\n"
                          . "  <body style='font-family:Calibri,Segoe UI,Myriad,Tahoma,Arial;'>\r\n"
                          . "    <div style='background-color:#CCF;'>\r\n"
                          . '      The following courses were opened to students today:' . "\r\n"
                          . "    </div>\r\n"
                          . '{$a->coursetable}' . "\r\n";

$string['emailfail']     = "    <p>\r\n"
                          . "      <div style='background-color:#FCC;'>\r\n"
                          . '        The following Moodle courses on {$a->servername} failed to open to students today:' . "\r\n"
                          . "      </div>\r\n"
                          . '{$a->coursetable}'. "\r\n"
                          . "    </p>";

$string['emailfoot']     = "  </body>\r\n"
                          . "</html>";

// Notification email components - grid of courses within notification body
$string['emailgridhead']  = '      <br/>'
                          . '      <table>' ."\r\n"
                          . '        <thead>' ."\r\n"
                          .	'           <th>ID Number</th>' ."\r\n"
                         .	'           <th>Course Name</th>' ."\r\n"
                         . '         </thead>' . "\r\n";

$string['emailgridrow'] = '        <tr>' ."\r\n"
                          . '          <td>' ."\r\n"
                          . '            <a href="{$a->url}">{$a->idnumber}</a>' . "\r\n"
                          . '          </td>' ."\r\n"
                          . '          <td>' ."\r\n"
                          . '            <a href="{$a->url}">{$a->fullname}</a>' . "\r\n"
                          . '          </td>' ."\r\n"
                          . '        </tr>' . "\r\n";

$string['emailgridfoot']= '      </table>' . "\r\n";

$string['nocourses']     = '    <p>Although courses were scheduled to open today, no courses were opened.</p>';

// Notification email components - subject
$string['emailsubj']     = '{$a->date} - Courses opened';


// Notification email components - mail headers
$string['emailheaders']  = 'MIME-Version: 1.0' . "\r\n"
                          . 'Content-type: text/html; charset=iso-8859-1' . "\r\n"
                          . 'From: {$a->fromemail}' . "\r\n"
                          . 'Reply-To: {$a->fromemail}' . "\r\n"
                          . 'X-Mailer: PHP/{$a->ver}';
$string['pluginname'] = 'RRU Open Courses';