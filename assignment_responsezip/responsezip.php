<?php
require_once('../../config.php');
require_once('responsezip_form.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
global $COURSE;

$id     = optional_param('id', 0, PARAM_INT);       // assignment id
$a      = optional_param('a', 0, PARAM_INT);        // Assignment ID

$returnurl = new moodle_url("submissions.php", array('id'=>$id,)); //not xhtml, just url.

$url = new moodle_url('/local/assignment_responsezip/responsezip.php');
if ($id) {
    if (! $cm = get_coursemodule_from_id('assignment', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $assignment = $DB->get_record("assignment", array("id"=>$cm->instance))) {
        print_error('invalidid', 'assignment');
    }

    if (! $course = $DB->get_record("course", array("id"=>$assignment->course))) {
        print_error('coursemisconf', 'assignment');
    }
    $url->param('id', $id);
} else {
    if (!$assignment = $DB->get_record("assignment", array("id"=>$a))) {
        print_error('invalidid', 'assignment');
    }
    if (! $course = $DB->get_record("course", array("id"=>$assignment->course))) {
        print_error('coursemisconf', 'assignment');
    }
    if (! $cm = get_coursemodule_from_instance("assignment", $assignment->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
    $url->param('a', $a);
}

$PAGE->set_url($url);
require_login($course, true, $cm);

require_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE, $cm->id));

$uploadform = new responsezip_form();
if ($uploadform->is_cancelled()) {
 // What to do if the button is cancelled
} else if ($fromform=$uploadform->get_data()) {
 //Process the form data so long as it's validated
 $uploadedfile = $uploadform->get_file_content('response_zip');

 require_once($CFG->dirroot.'/mod/assignment/lib.php');
 require_once($CFG->dirroot.'/mod/assignment/type/'.$assignment->assignmenttype.'/assignment.class.php');
 $assignmentclass = 'assignment_'.$assignment->assignmenttype;
 $assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);

 $zipfile = $uploadform->save_temp_file('response_zip');
 // Unzip uploaded file to the appropriate storage area
 require_once($CFG->dirroot.'/lib/filestorage/zip_packer.php');
 $ziplib = new zip_packer;
 $context = get_context_instance(CONTEXT_MODULE, $cm->id);
 print "Course ID: $id<br>\n";
 print "Context ID: $context->id<br>\n";
 $response_files = $ziplib->extract_to_storage($zipfile, $context->id, 'mod_assignment', 'response', 0, 'feedback');
 // Associate the response files with their appropriate submissions...
 $fs = get_file_storage();
 $users = get_enrolled_users($context);
 $groups = groups_get_all_groups($COURSE->id);
 foreach ($response_files as $file=>$value) {
  preg_match('/(.*?)_(.*)_\d*(\..*)/', $file, $matches);
  $username = $matches[1];
  $filename = $matches[2];
  $fileextension = $matches[3];
  $userID = '';
  $groupID = '';
  $submission = '';
  print "Processing :$filename$fileextension<br>\n";
  if (! $assignmentinstance->assignment->groupassignment) {
   print "This is not a group assignment<br>\n";
   // Determine the userID of the user for the document 
   foreach ($users as $user) {
    if ($username == $user->firstname . " " . $user->lastname) {
     $userID = $user->id;
     $submission = $assignmentinstance->get_submission($userID, true, true);
     print "$userID - $username - $submission->id\n<BR>";
     print_r($submission);
     print "<BR><HR>\n";
    }   
   }
  } else {
   print "This is a group assignment<br>\n";
   foreach ($groups as $group) {
    if ($group->name == $username) {
     $groupID = $group->id;
     $submission = $assignmentinstance->get_submission('0', false, false, $groupID, true);
     print "$userID - $groupID - $username - $submission->id\n<BR>";
     print_r($submission);
     print "<BR><HR>\n";
    } 
   }
   // If there is no userID set for the assignment yet, it must be a group assignment, so we'll find the group ID.
   if (!$submission) {
    print "This is an individually submitted group submission<BR>\n";
    foreach ($users as $user) {
     if ($username == $user->firstname . " " . $user->lastname) {
      $userID = $user->id;
      $submission = $assignmentinstance->get_submission($userID, true, true);
      print "$userID - $username - $submission->id\n<BR>";
      print_r($submission);
      print "<BR><HR>\n";
     }   
    }
   }
  }
  // So long as we have the original submission, setup the record to add the file as a feedback to the submission.
  if ($submission) {
   $orig_file = $fs->get_file($context->id, 'mod_assignment', 'response', '0', '/feedback/', $file);
   if (!$orig_file) { die("Didn't get the file! $file"); } else {
    $newfilerecord = array(
	'contextid' => $context->id,
	'component' => 'mod_assignment',
	'filearea' => 'response',
	'itemid' => $submission->id,
	'filepath' => '/',
	'filename' => 'feedback_' . $filename . $fileextension
    );

    // The next while loop simply takes care of incrementing the filename, in case of a collision.
    $file_inc = '';
    while ($fs->file_exists($context->id, 'mod_assignment', 'response', $submission->id, '/', $newfilerecord['filename'])) {
     $file_inc++;
     $newfilerecord['filename'] = 'feedback_' . $filename . '_' . $file_inc . $fileextension; 
    }

    // Create the record for the file as a feedback for the assignment response.  'feedback' is triggered based
    // on being in the 'response' filearea, and having an itemID matching the submission ID.
    $DB->set_field('assignment_submissions', 'timemarked', time(), array('userid'=>$submission->userid,'id'=>$submission->id,'assignment'=>$submission->assignment));
    $fs->create_file_from_storedfile($newfilerecord, $orig_file);
   }
  } else {
   //Produce some error here, because the submission for the user/group could not be found, so we can't relate the response file.
   print_error('Unknown response file in the zip.  Unable to associate it with a submission.');
  }
 }
 redirect($CFG->wwwroot . '/mod/assignment/submissions.php?id='.$id);
 die();
} else {
 // What to do on the first display of the form, or if it doesn't validate properly...
 print $OUTPUT->header();
 $uploadform->display();
 print $OUTPUT->footer();
}
?>

