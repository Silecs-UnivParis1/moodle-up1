<?php
/**
 * @package    local
 * @subpackage up1_notification
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once("../../mod/feedback/lib.php");
require_once('lib_notification.php');
require_once('notification_form.php');

$courseid = optional_param('courseid', false, PARAM_INT);
$id = required_param('id', PARAM_INT);

// faire pleins de vérif

if (! $cm = get_coursemodule_from_id('feedback', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}

if (! $feedback = $DB->get_record("feedback", array("id"=>$cm->instance))) {
    print_error('invalidcoursemodule');
}
//check whether the feedback is located and! started from the mainsite
if ($course->id == SITEID AND !$courseid) {
    $courseid = SITEID;
}

$current_tab =  'notification';

$url = new moodle_url('/local/up1_notification/notification.php', array('id'=>$id));
$PAGE->set_url($url);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);

$msgresult = '';
$mform = new local_up1_notification_notification_form();

$newformdata = array('id'=>$id);
$mform->set_data($newformdata);
$formdata = $mform->get_data();

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/mod/feedback/view.php?id='.$id);
}

$completedscount = feedback_get_completeds_group_count($feedback);
$incompletecount = feedback_count_incomplete_users($cm);

$completedscount = ($completedscount?$completedscount:0);
$incompletecount = ($incompletecount?$incompletecount:0);

$params = array('nbr_rep' => $completedscount, 'nbr_non_rep' => $incompletecount);
$params['nom_feedback'] = format_string($feedback->name);
$params['lien_feedback'] = $CFG->wwwroot . '/mod/feedback/view.php?id=' . $id;

if ($formdata) {
    // select msg
    $msg = get_notification_message($formdata, $params);
    //envoyer message
    if ($formdata->destinataire && $formdata->destinataire==1) {
        //répondant
        $responses = feedback_get_completeds_group($feedback);
        $msgresult = send_notification_complete_users($responses, $msg);
    } else {
        // non-répondant
        $idusers = feedback_get_incomplete_users($cm);
        $msgresult = send_notification_incomplete_users($idusers, $msg);
    }
}
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($feedback->name));
$PAGE->requires->css(new moodle_url('/local/up1_notification/notification.css'));
$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js_init_code(file_get_contents(__DIR__ . '/js/include-for-notification-form.js'));

echo $OUTPUT->header();

require('../../mod/feedback/tabs.php');

echo $OUTPUT->box_start('feedback_info');
echo '<span class="feedback_info">';
echo get_string('completed_feedbacks', 'feedback').': ';
echo '</span>';
echo '<span class="feedback_info_value">';
echo $completedscount;
echo '</span>';
echo $OUTPUT->box_end();

echo $OUTPUT->box_start('feedback_info');
echo '<span class="feedback_info">';
echo get_string('incompleted_feedbacks', 'local_up1_notification') . ': ';
echo '</span>';
echo '<span class="feedback_info_value">';
echo $incompletecount;
echo '</span>';
echo $OUTPUT->box_end();

echo $OUTPUT->heading(get_string('notification', 'local_up1_notification'));

if ($msgresult != '') {
    echo $OUTPUT->box_start('feedback_info');
    echo $msgresult;
    echo '<p><a href="' . $url . '">' . get_string('sendnewnotification', 'local_up1_notification')
        . '</a></p>';
    echo $OUTPUT->box_end();
} else {

    $mform->display();
}

echo $OUTPUT->footer();
?>
