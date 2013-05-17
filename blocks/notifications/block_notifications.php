<?php
include_once realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR."common.php";
include_once LIB_DIR."User.php";
include_once LIB_DIR."Course.php";
include_once LIB_DIR."eMail.php";
if(file_exists(LIB_DIR."SMS.php")) {
	include_once LIB_DIR."SMS.php";
}

class block_notifications extends block_base {

//***************************************************
// Init
//***************************************************
	function init() {
		$this->title = get_string('pluginname', 'block_notifications');
	}

	function has_config() { return true; }

	function after_install() {
		global $CFG;
		// initialize the global configuration
		$global_config = array(
			"block_notifications_email_channel" => 1,
			"block_notifications_sms_channel" => 1,
			"block_notifications_rss_channel" => 1,
			"block_notifications_rss_shortname_url_param" => 0,
			"block_notifications_frequency" => 12,
			"block_notifications_email_notification_preset" => 1,
			"block_notifications_sms_notification_preset" => 1
		);
		return parent::config_save($global_config);
	}

	function before_delete() {
		global $CFG;
		unset($CFG->block_notifications_email_channel);
		unset($CFG->block_notifications_sms_channel);
		unset($CFG->block_notifications_rss_channel);
		unset($CFG->block_notifications_rss_shortname_url_param);
		unset($CFG->block_notifications_frequency);
		unset($CFG->block_notifications_email_notification_preset);
		unset($CFG->block_notifications_sms_notification_preset);
		return true;
	}

	function applicable_formats() {
		return array('course-view' => true);
	}


//***************************************************
// Configurations
//***************************************************
	function specialization() {
		global $COURSE;
		$Course = new Course();
		// if the course has not been registered so far
		// then register the course and set the starting time
		// for notifications
		if( !$Course->is_registered($COURSE->id) ) {
			$Course->register($COURSE->id, time());
		}
		// intialize logs; perform this operation just once
		if( !$Course->log_exists($COURSE->id) ) {
			$Course->initialize_log($COURSE);
		}
	}

	function instance_allow_config() {
		return true;
	}

	function instance_config_save($data, $nolongerused = false) {
		global $COURSE;
		$Course = new Course();
		$Course->update_course_notification_settings($COURSE->id, $data);
  		return true;
	}

	function personal_settings($course_registration){
		global $CFG;
		global $COURSE;
		global $USER;

		// if admin user or both sms and email notifications
		// are disabled in the course then do not display user preferences
		if(
			($CFG->block_notifications_email_channel != 1 and $CFG->block_notifications_sms_channel != 1) or
			($course_registration->notify_by_email == 0 and $course_registration->notify_by_sms == 0 )
		) {
			return '';
		} else {
			$User = new User();
			$user_preferences = $User->get_preferences($USER->id, $COURSE->id);

			// intialize preferences if preferences if necessary
			if(is_null($user_preferences)) {
				$user_preferences = new Object();
				$user_preferences->user_id = $USER->id;
				$user_preferences->course_id = $COURSE->id;
				$user_preferences->notify_by_email = $course_registration->email_notification_preset;
				$user_preferences->notify_by_sms = $course_registration->sms_notification_preset;
				$User->initialize_preferences(	$user_preferences->user_id,
												$user_preferences->course_id,
												$user_preferences->notify_by_email,
												$user_preferences->notify_by_sms );
			}

			// prepare mail notification status
			$mail_notification_status = '';
			if( isset($user_preferences->notify_by_email) and $user_preferences->notify_by_email == 1) { $mail_notification_status = 'checked="checked"'; }

			$sms_notification_status = '';
			if( isset($user_preferences->notify_by_sms) and $user_preferences->notify_by_sms == 1) { $sms_notification_status = 'checked="checked"'; }

			//user preferences interface
			$up_interface ="<script src='$CFG->wwwroot/blocks/notifications/js/jquery-1.4.3.js' type='text/javascript'></script>";
			$up_interface.="<script src='$CFG->wwwroot/blocks/notifications/js/user_preferences_interface.php' type='text/javascript'></script>";
			$up_interface.='<div id="notifications_config_preferences">'; // main div
			$up_interface.='<a id="notifications_user_preferences_trigger" href="#" onclick="show_user_preferences_panel()">';
			$up_interface.= get_string('user_preference_settings', 'block_notifications');
			$up_interface.= '</a>';
			$up_interface.='<div id="notifications_user_preferences" style="display:none">';// div a
			$up_interface.='<div>'; // div b
			$up_interface.= get_string('user_preference_header', 'block_notifications');
			$up_interface.='</div>'; // div b end
			$up_interface.='<form id="user_preferences" action="">';
			$up_interface.='<input type="hidden" name="user_id" value="'.$USER->id.'" />';
			$up_interface.='<input type="hidden" name="course_id" value="'.$COURSE->id.'" />';
			if ( $CFG->block_notifications_email_channel == 1 and $course_registration->notify_by_email == 1 ) {
				$up_interface.='<div>'; // div c
				$up_interface.="<input type='checkbox' name='notify_by_email' value='1' $mail_notification_status />";
				$up_interface.= get_string('notify_by_email', 'block_notifications');
				$up_interface.='</div>'; // div c end
			}
			if ( class_exists('SMS') and $CFG->block_notifications_sms_channel == 1 and $course_registration->notify_by_sms == 1 ) {
				$up_interface.='<div>'; // div d end
				$up_interface.="<input type='checkbox' name='notify_by_sms' value='1' $sms_notification_status />";
				$up_interface.= get_string('notify_by_sms', 'block_notifications');
				$up_interface.='</div>'; // div d end
			}
			$up_interface.='</form>';
			$up_interface.='<input type="button" name="save_user_preferences" value="'.get_string('savechanges').'" onclick="save_user_preferences()" />';
			$up_interface.='<input type="button" name="cancel" value="'.get_string('cancel').'" onclick="hide_user_preferences_panel()" />';
			$up_interface.='</div>'; // div a end
			$up_interface.='</div>'; // main div end
			return $up_interface;
		}
		/*
		*/
	}

//***************************************************
// Block content
//***************************************************
	function get_content() {
		if ($this->content !== NULL) {
			return $this->content;
		}

		global $COURSE;
		global $USER;
		global $CFG;

		$this->content   = new stdClass;
		$Course = new Course();
		$course_registration = $Course->get_registration($COURSE->id);
		
		if (
			( $CFG->block_notifications_email_channel != 1 and $CFG->block_notifications_sms_channel != 1 and $CFG->block_notifications_rss_channel != 1) or
			( $course_registration->notify_by_email == 0 and $course_registration->notify_by_sms == 0 and $course_registration->notify_by_rss == 0 )
		){

			$this->content->text =  get_string('configuration_comment', 'block_notifications');

		} else {
			// last notification info
			$this->content->text = "<span style='font-size: 12px'>";
			$this->content->text.= get_string('last_notification', 'block_notifications');
			$this->content->text.= ": ".date("j M Y G:i:s",$course_registration->last_notification_time);
			$this->content->text.= "</span><br />";

			if ( $CFG->block_notifications_email_channel == 1 and $course_registration->notify_by_email == 1 ) {
				$this->content->text.= "<img src='$CFG->wwwroot/blocks/notifications/images/Mail-icon.png' ";
				$this->content->text.= "alt='e-mail icon' ";
				$this->content->text.= "title='".get_string('email_icon_tooltip', 'block_notifications')." ";
				$this->content->text.= $course_registration->notification_frequency / 3600 . " ".get_string('end_of_tooltip', 'block_notifications')."' />";
				//$this->content->text.= '<br />';
			}

			if ( $CFG->block_notifications_sms_channel == 1 and $course_registration->notify_by_sms == 1 and class_exists('SMS') ) {
				if( empty($USER->phone2) ) {
					//$this->content->text.= "<a target='_blank' href='$CFG->wwwroot/help.php?module=plugin&file=../blocks/notifications/lang/en_utf8/help/prova.html'>";
					$this->content->text.= "<a target='_blank' href='$CFG->wwwroot/blocks/notifications/help.php'>";
					$this->content->text.= "<img src='$CFG->wwwroot/blocks/notifications/images/SMS-icon_warning.png' ";
					$this->content->text.= "alt='sms warning icon' ";
					$this->content->text.= "title='".get_string('sms_icon_phone_number_missing_tooltip', 'block_notifications')."' />";
					$this->content->text.= "</a>";
				} else {
					$this->content->text.= "<img src='$CFG->wwwroot/blocks/notifications/images/SMS-icon.png' ";
					$this->content->text.= "alt='sms icon' ";
					$this->content->text.= "title='".get_string('sms_icon_tooltip', 'block_notifications')." ";
					$this->content->text.= $course_registration->notification_frequency / 3600 . " ".get_string('end_of_tooltip', 'block_notifications')."' />";
				}
				//$this->content->text.= '<br />';
			}
			if ( $CFG->block_notifications_rss_channel == 1 and $course_registration->notify_by_rss == 1 ) {
				if ( isset($course_registration->rss_shortname_url_param) and $course_registration->rss_shortname_url_param == 1 ) {
					$this->content->text.= "<a target='_blank' href='$CFG->wwwroot/blocks/notifications/lib/RSS.php?shortname=$COURSE->shortname'>";
				} else {
					$this->content->text.= "<a target='_blank' href='$CFG->wwwroot/blocks/notifications/lib/RSS.php?id=$COURSE->id'>";
				}
				$this->content->text.= "<img src='$CFG->wwwroot/blocks/notifications/images/RSS-icon.png' ";
				$this->content->text.= "alt='rss icon' ";
				$this->content->text.= "title='".get_string('rss_icon_tooltip', 'block_notifications')."' />";
				$this->content->text.= "</a>";
			}

		}

		$this->content->text.= $this->personal_settings($course_registration);
		$this->content->footer = '';
		return $this->content;
	}

//***************************************************
// Cron
//***************************************************

function cron() {
		global $CFG;
		echo "\n\n****** notifications :: begin ******";
		$User = new User();
		// clean deleted users data
		$User->collect_garbage();

		$Course = new Course();
		// clean deleted courses data
		$Course->collect_garbage();

		// get the list of courses that are using this block
		$courses = $Course->get_all_courses_using_notifications_block();
		
		// if no courses are using this block exit
		if( !is_array($courses) or count($courses) < 1 ) {
			echo "\n--> None course is using notifications plugin.";
			echo "\n****** notifications :: end ******\n\n";
			return;
		}

		foreach($courses as $course) {
			// if course is not visible then skip
			if ( $course->visible == 0 ) { continue; }

			// if the course has not been registered so far then register
			echo "\n--> Processing course: $course->fullname";
			if( !$Course->is_registered($course->id) ) {
				$Course->register($course->id, time());
			}

			// check notification frequency for this course
			$course_registration = $Course->get_registration($course->id);
			// initialize user preferences and check for new enrolled users in this course
			$enrolled_users = $User->get_all_users_enrolled_in_the_course($course->id);

			foreach($enrolled_users as $user) {
				// check if the user has preferences
				$user_preferences = $User->get_preferences($user->id, $course->id);
				// if the user has not preferences than set the default
				if(is_null($user_preferences)) {
					$user_preferences = new Object();
					$user_preferences->user_id = $user->id;
					$user_preferences->course_id = $course->id;
					$user_preferences->notify_by_email = $course_registration->email_notification_preset;
					$user_preferences->notify_by_sms = $course_registration->sms_notification_preset;
					$User->initialize_preferences(	$user_preferences->user_id,
													$user_preferences->course_id,
													$user_preferences->notify_by_email,
													$user_preferences->notify_by_sms );
				}
			}

			// if course log entry does not exist
			// or the last notification time is older than two days
			// then reinitialize course log
			if( !$Course->log_exists($course->id) or $course_registration->last_notification_time + 48*3600 < time() )
				$Course->initialize_log($course);

			// check notification frequency for the course and skip to next cron cycle if neccessary
			if( $course_registration->last_notification_time + $course_registration->notification_frequency > time() ){
				echo " - Skipping to next cron cycle.";
				continue;
			}

			$Course->update_log($course);

			// check if the course has something new or not
			$changelist = $Course->get_recent_activities($course->id);
			// update the last notification time
			$Course->update_last_notification_time($course->id, time());
			if( empty($changelist) ) { continue; } // check the next course. No new items in this one.


			foreach($enrolled_users as $user) {
				// get user preferences
				$user_preferences = $User->get_preferences($user->id, $course->id);
				// if the email notification is enabled in the course
				// and if the user has set the emailing notification in preferences
				// then send a notification by email
				if( $CFG->block_notifications_email_channel == 1 and $course_registration->notify_by_email == 1 and $user_preferences->notify_by_email == 1 ) {
					$eMail = new eMail();
					$eMail->notify($changelist, $user, $course);
				}
				// if the sms notification is enabled in the course
				// and if the user has set the sms notification in preferences
				// and if the user has set the mobile phone number
				// then send a notification by sms
				if(
					class_exists('SMS') and
					$CFG->block_notifications_sms_channel == 1 and
					$course_registration->notify_by_sms == 1 and
					$user_preferences->notify_by_sms == 1 and
					!empty($user->phone2)
				) {
					$sms = new SMS();
					$sms->notify($changelist, $user, $course);
				}
			}
		}
		echo "\n****** notifications :: end ******\n\n";
		return;
	}

}
?>
