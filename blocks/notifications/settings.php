<?php
/////////////////////////////////////////////////////
// GLOBAL SETTINGS
////////////////////////////////////////////////////
include_once realpath( dirname( __FILE__ ).DIRECTORY_SEPARATOR ).DIRECTORY_SEPARATOR."common.php";
include_once LIB_DIR."AbstractSMS.php";
if(file_exists(LIB_DIR."SMS.php")) {
	include_once LIB_DIR."SMS.php";
}

defined( 'MOODLE_INTERNAL' ) || die;
global $CFG;

if ( $ADMIN->fulltree ) {

	$settings->add( new admin_setting_heading('block_notifications_settings', '', get_string('global_configuration_comment', 'block_notifications')) );
	$settings->add( new admin_setting_configcheckbox('block_notifications_email_channel', get_string('email', 'block_notifications'), '', 1) );

	if( class_exists('SMS') ) {
		$settings->add( new admin_setting_configcheckbox('block_notifications_sms_channel', get_string('sms', 'block_notifications'), '', 1) );
	} else {
		$settings->add( new admin_setting_configcheckbox('block_notifications_sms_channel', get_string('sms', 'block_notifications'),  get_string('sms_class_not_implemented', 'block_notifications'), 0) );
	}

	$settings->add( new admin_setting_configcheckbox('block_notifications_rss_channel', get_string('rss', 'block_notifications'), '', 1) );
	
	$settings->add( new admin_setting_configcheckbox('block_notifications_rss_shortname_url_param', get_string('rss_by_shortname', 'block_notifications'), '', 0) );

	$options = array();
	for( $i=1; $i<25; ++$i ) {
		$options[$i] = $i;
	}

	$default = 12;
	if( isset($CFG->block_notifications_frequency) ) {
		$default = $CFG->block_notifications_frequency;
	}

    $settings->add( new admin_setting_configselect('block_notifications_frequency',
													get_string('notification_frequency', 'block_notifications'),
													get_string('notification_frequency_comment', 'block_notifications'), $default , $options) );

	$settings->add( new admin_setting_heading('block_notifications_presets', '', get_string('global_configuration_presets_comment', 'block_notifications')) );

	$settings->add( new admin_setting_configcheckbox('block_notifications_email_notification_preset', get_string('email_notification_preset', 'block_notifications'), get_string('email_notification_preset_explanation', 'block_notifications'), 1) );

	$settings->add( new admin_setting_configcheckbox('block_notifications_sms_notification_preset', get_string('sms_notification_preset', 'block_notifications'), get_string('sms_notification_preset_explanation', 'block_notifications'), 1) );

}

