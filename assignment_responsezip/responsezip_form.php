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
 * Response Zip                
 *
 * Upload of assignment responses in a zip file                        
 *
 * @package    plug-in                                                    
 * @subpackage responsezip form
 * @copyright  2011 Steve Beaudry   , Royal Roads University                                      
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
 require_once("../../config.php");
 require_once("$CFG->libdir/formslib.php");
 
 /**
 * Response Zip Class                                         
 *
 * Response Zip defintion /form                               
 *
 * @copyright 2011 Steve Beaudry, Royal Roads University                                          
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later  
 */
 class responsezip_form extends moodleform {

  function definition() {
   global $CFG, $id, $a;
   
   $uploadform =& $this->_form;
   $uploadform->addElement('hidden', 'id', $id);
   $uploadform->addElement('hidden', 'a', $a);
   $uploadform->addElement('filepicker', 'response_zip', "Upload response files zip", null, array('accepted_types' => '*.zip'));
   $buttonarray=array();
   $buttonarray[] = &$uploadform->createElement('submit', 'submitbutton', 'Submit response files');
   $buttonarray[] = &$uploadform->createElement('cancel');
   $uploadform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
   $uploadform->closeHeaderBefore('buttonar');
   
  }
 }
?>
