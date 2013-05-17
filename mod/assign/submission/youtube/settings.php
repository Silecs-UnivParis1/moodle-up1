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
 * This file defines the admin settings for this plugin
 * Development funded by: Global Awakening (@link http://www.globalawakening.com)
 *
 * @package   assignsubmission_youtube
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// The youtube submission is on by default
$settings->add(new admin_setting_configcheckbox('assignsubmission_youtube/default',
                   new lang_string('default', 'assignsubmission_youtube'),
                   new lang_string('default_help', 'assignsubmission_youtube'), 0));

// Allow uploads
$settings->add(new admin_setting_configcheckbox('assignsubmission_youtube/allow_uploads',
                   new lang_string('allowuploads', 'assignsubmission_youtube'),
                   new lang_string('allowuploadsdetails', 'assignsubmission_youtube'), 1));
                   
// Allow webcam
$settings->add(new admin_setting_configcheckbox('assignsubmission_youtube/allow_webcam',
                   new lang_string('allowwebcam', 'assignsubmission_youtube'),
                   new lang_string('allowwebcamdetails', 'assignsubmission_youtube'), 1));

// Allow manual
$settings->add(new admin_setting_configcheckbox('assignsubmission_youtube/allow_manual',
                   new lang_string('allowmanual', 'assignsubmission_youtube'),
                   new lang_string('allowmanualdetails', 'assignsubmission_youtube'), 1));

// Developers Key			   
$settings->add(new admin_setting_configtext('assignsubmission_youtube/devkey',
                        new lang_string('youtubedevkey', 'assignsubmission_youtube'),
                        new lang_string('youtubedevkeydetails', 'assignsubmission_youtube'), '')); 
 
//The authentication type, master user or student by student
$options = array('byuser' => new lang_string('byuser', 'assignsubmission_youtube'),
			'bymaster' => new lang_string('bymaster', 'assignsubmission_youtube'));
$settings->add(new admin_setting_configselect('assignsubmission_youtube/authtype', 
				new lang_string('authtype', 'assignsubmission_youtube'),  
				new lang_string('authtypedetails', 'assignsubmission_youtube'), 'bymaster', $options));
				
// Section for authenticating by ClientLogin/Master.
$settings->add(new admin_setting_heading('youtubemasterheading', '', get_string('youtubemasterheading', 'assignsubmission_youtube')));

$settings->add(new admin_setting_configtext('assignsubmission_youtube/youtube_masteruser',
                        new lang_string('youtubemasteruser', 'assignsubmission_youtube'),
                        new lang_string('youtubemasteruserdetails', 'assignsubmission_youtube'), ''));
						
$settings->add(new admin_setting_configpasswordunmask('assignsubmission_youtube/youtube_masterpass',
                        new lang_string('youtubemasterpass', 'assignsubmission_youtube'),
                        new lang_string('youtubemasterpassdetails', 'assignsubmission_youtube'), ''));

// Section for authenticating by OAUTH2/Student
$settings->add(new admin_setting_heading('youtubestudentheading', '', get_string('youtubestudentheading', 'assignsubmission_youtube')));

$settings->add(new admin_setting_configtext('assignsubmission_youtube/youtube_clientid',
                        new lang_string('youtubeclientid', 'assignsubmission_youtube'),
                        new lang_string('youtubeclientiddetails', 'assignsubmission_youtube'), ''));
						
$settings->add(new admin_setting_configtext('assignsubmission_youtube/youtube_secret',
                        new lang_string('youtubesecret', 'assignsubmission_youtube'),
                        new lang_string('youtubesecretdetails', 'assignsubmission_youtube'), ''));
//$settings->disabledIf('assignsubmission_youtube/youtube_clientid', 'assignsubmission_youtube/authtype', 'eq', 'byuser');

// Section for Player Sizes
$settings->add(new admin_setting_heading('youtubeplayersizeheading', '', get_string('youtubeplayersizeheading', 'assignsubmission_youtube')));

//The size of the youtube player on the various screens		
$options = array('0' => new lang_string('linkonly', 'assignsubmission_youtube'),
				'160' => '160x120', '320' => '320x240','480' => '480x360',
				'640' => '640x480','800'=>'800x600','1024'=>'1024x768');
				
$settings->add(new admin_setting_configselect('assignsubmission_youtube/displaysize_single', 
					new lang_string('displaysizesingle', 'assignsubmission_youtube'), 
					new lang_string('displaysizesingledetails', 'assignsubmission_youtube'), '320', $options));

$settings->add(new admin_setting_configselect('assignsubmission_youtube/displaysize_list', 
					new lang_string('displaysizelist', 'assignsubmission_youtube'), 
					new lang_string('displaysizelistdetails', 'assignsubmission_youtube'), '480', $options));
					
				
					


