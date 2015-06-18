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
 * This library does the actual work of opening the courses whose start date is today.
 *
 * 2014-04-28
 * @package      plug-in
 * @subpackage   rruopencourses
 * @copyright    2014 Gerald Albion, Royal Roads University
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Produce an HTML table with two columns: course ID number and course full name.
 *
 * @author Gerald Albion
 * date    2013-09-25
 * @global object $CFG Moodle Configuration Object
 * @param array $courses an array that is the output of a $DB->get_records_sql() call
 * @return string an HTML table with the courses formatted
 */
function coursetable($courses) {
    global $CFG;

    // No courses?  Can't make a table.  Return an informative message instead.
    if (0 == count($courses)) {
        return get_string('nocourses', 'local_rruopencourses');
    }

    // There is at least one course; build a table.
    $head = get_string('emailgridhead', 'local_rruopencourses');
    $rows = ''; // Init.
    foreach ($courses as $course) {
        $rowdata           = new stdClass ();
        $rowdata->id       = $course->id;
        $rowdata->idnumber = $course->idnumber;
        $rowdata->fullname = $course->fullname;
        $rowdata->url      = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
        $rows             .= get_string('emailgridrow', 'local_rruopencourses', $rowdata);
    }
    $foot = get_string('emailgridfoot', 'local_rruopencourses');
    return $head . $rows . $foot;
}

/**
 * Send an email with error reporting
 *
 * @author Gerald Albion
 * date    2013-09-25
 * @param string $email        the recipient email address
 * @param string $emailsubject the email subject
 * @param string $emailbody    the text of the email
 * @param string $headers      additional email headers
 * @return string information about the error, or an empty string if no error
 */
function local_rrusendmailto($email, $emailsubject, $emailbody, $headers) {
    $mailsuccess = false;
    $error       = '';

    // Try to send the mail.
    $mailsuccess = mail($email, $emailsubject, $emailbody, $headers);

    // Mail error?
    if (!$mailsuccess) {
        $error     = "\n(Error) Failed to send email to ". $email;
        $lasterror = "\n" . error_get_last();
        if ('' == $lasterror) {
            $lasterror = "\n(No error message specified by PHP)";
        }
        $error  .= $lasterror;
        print   $error;
    }
    return $error;
}

/**
 * Open the courses that are due to open today.
 *
 * Typically invoked by the server's cron.  This function:
 * - Queries the course table for courses that are not open but start today
 * - Opens those courses
 * - Emails notifications to a configured list of recipients
 * - Emails a notification to an admin address if there are any errors.
 *
 * @author Gerald Albion
 * date    2013-09-25
 * @global object $DB  Moodle Database object
 * @global object $CFG Moodle configuration object
 *
 */
function rruopencourses_run() {

    global $DB, $CFG;
    define('ROC_SEC_PER_DAY', 86400);

    // Security: Is this being called by the Moodle cron?
    // If not, $_SERVER['REMOTE_ADDR'] will be set and we should reject the request.
    if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
        die('Sorry, this function is not available from your location.');
    }

    // Setup: Clear errors.
    $errors = '';

    // Setup: Get the number of days in advance to open the course.
    $advance = get_config('local_rruopencourses', 'roc_opencoursedate') * ROC_SEC_PER_DAY;

    // Setup: query to select courses that are due to start but have not been opened yet.
    $sqlselect =
        "SELECT id, idnumber, fullname
         FROM {course}
         WHERE visible = 0
         AND LEFT(from_unixtime(CAST(startdate AS SIGNED) - " . $advance. "), 10) = curdate()
         ORDER BY fullname";

    // Setup: query to open courses that are due to start but have not been opened yet.
    $sqlopen =
        "UPDATE {course}
         SET visible = 1
         WHERE visible = 0
         AND LEFT(from_unixtime(CAST(startdate AS SIGNED) - " . $advance. "), 10) = curdate()";

    // Build a list of courses that need to be opened now.
    try {
        $coursestoopen = $DB->get_records_sql($sqlselect);
    } catch (Exception $e) {
        $error = "Error retrieving list of courses to open: " . $e->getMessage () . "<br>";
        $errors .= $error;
        print $error;
    }

    // Were there any courses to open?
    if (0 == count($coursestoopen)) {
        print "No courses to open today.\n";
        return;
    }

    // Open the courses.
    try {
        $DB->execute($sqlopen);
    } catch (Exception $e) {
        $error = "\nError opening courses: " . $e->getMessage () . "\n<br>" . $sqlopen . "\n<br>";
        $errors .= $error;
        print $error;
    }

    // Identify the courses that failed to open.
    // The same query that built our list of courses will return the failed openings now that we have attempted to open them all.
    try {
        $coursesthatfailed = $DB->get_records_sql($sqlselect);
    } catch (Exception $e) {
        $error   = "Error retrieving list of courses that failed to open: " . $e->getMessage () . "<br>";
        $errors .= $error;
        print $error;
    }

    // Now that we have opened the courses, change the list of courses "to be opened" into
    // a list of courses "that actually opened".  This list plus the list of failed
    // openings equals the original list of courses to change.  This is so we can report
    // only the courses that DID open in the email before reporting the ones that failed.
    foreach ($coursesthatfailed as $coursefailed) {
        $id = $coursefailed->id;
        if (isset($coursestoopen [$id])) {
            unset($coursestoopen [$id]);
        }
    }

    // Setup field strings.
    $todaysdate = date('M j, Y');

    // The site's full name is available in Course ID 1 (Thanks, Andy!)
    $servernamequery = "SELECT fullname FROM {course} WHERE id=1";
    try {
        $firstcourse = $DB->get_record_sql($servernamequery);
    } catch (Exception $e) {
        $error = "Error trying to get server name: " . $e->getMessage () . "<br>";
        $errors .= $error;
        print $error;
    }

    $servername = $firstcourse->fullname;

    // What if we couldn't get the site's full name from the course table?
    if ('' == $servername) {
        $servername = php_uname('n');
        if (false === $servername) {
            $servername = "localhost";
        }
    }

    // Setup email notification(s).
    $subjfields                = new stdClass();
    $subjfields->date          = $todaysdate;
    $subjfields->servername    = $servername;
    $emailsubject              = get_string ('emailsubj', 'local_rruopencourses', $subjfields);
    $headerfields              = new stdClass();
    $headerfields->fromemail   = 'Course Notification <' . $CFG->noreplyaddress . '>';
    $headerfields->ver         = phpversion();
    $headers                   = get_string('emailheaders', 'local_rruopencourses', $headerfields);

    // Build a notification email body. This will be sent to each email address in the settings.
    $bodyfields                = new stdClass();
    $bodyfields->servername    = $servername;
    $bodyfields->coursetable   = coursetable ($coursestoopen);
    $emailbody                 = get_string ('emailbody', 'local_rruopencourses', $bodyfields);

    // Were there failed openings?  If so, add the fail section to the email body.
    if (0 != count($coursesthatfailed)) {
        // Build the optional fail section of the email body.
        $failfields              = new stdClass();
        $failfields->servername  = $servername;
        $failfields->coursetable = coursetable($coursesthatfailed);
        $errortable              = get_string('emailfail', 'local_rruopencourses', $failfields);
        $emailbody              .= $errortable;
        $errors                 .= "<br/>Some courses could not be opened:<br/>" . $errortable . "<br/>";
    }

    // Add the email footer, which appears after the fail section (if any).
    $emailbody .= get_string('emailfoot', 'local_rruopencourses');

    // Send the notifications.
    $emaillist =  get_config('local_rruopencourses', 'roc_emails');
    $emails = explode(';', $emaillist);
    foreach ($emails as $email) {
        print "\n\nSending mail to $email\n";
        $errors .= local_rrusendmailto($email, $emailsubject, $emailbody, $headers);
    }

    // Were there errors?  If so, attempt to send one email containing all of them.
    if ('' != $errors) {
        $errors = "<html><head></head><body><p>There were errors opening courses:</p>\n<p>" . $errors . "</p></body></html>";
        $headers  = get_string('emailheaders', 'local_rruopencourses', phpversion());
        $warningerror = local_rrusendmailto($CFG->noreplyaddress, 'Error(s) opening courses', $errors, $headers);
        if ('' != $warningerror) {
            $error = "\n\n*** NOTICE ***\n\n"
                   . "There were error(s) and the module was unable to send an email notification of those errors.\n\n"
                   . "The error sending email was: \n" . $warningerror . "\n\n"
                   . "The original error(s) being reported were:\n" . $errors . "\n\n";
            file_put_contents('php://stderr', $error); // Output all the errors to stderr.
        }
    }
}