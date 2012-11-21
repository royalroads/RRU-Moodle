<?php

defined('MOODLE_INTERNAL') || die;

$settings->add(new admin_setting_configcheckbox('block_course_overview_visible' , 
		get_string('visible','block_course_overview'),
		get_string('visible_desc', 'block_course_overview'),
		1
	   ));

$settings->add(new admin_setting_configcheckbox('block_course_overview_start_date' ,
		get_string('start_date','block_course_overview'),
		get_string('start_date_desc','block_course_overview'),
		0
   	));
