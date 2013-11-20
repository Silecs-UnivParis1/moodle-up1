<?php
/**
 * @package    local
 * @subpackage up1_notificationcourse
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once('lib_notificationcourse.php');
require_once('notificationcourse_form.php');


//$id = optional_param('id', false, PARAM_INT);
$id = required_param('id', PARAM_INT);
$moduletype = required_param('mod', PARAM_ALPHA);

$infolog = array();


if (! $cm = get_coursemodule_from_id($moduletype, $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}

if (! $module = $DB->get_record($moduletype, array("id"=>$cm->instance))) {
    print_error('invalidcoursemodule');
}
//check whether the feedback is located and! started from the mainsite
if ($course->id == SITEID AND !$courseid) {
    $courseid = SITEID;
}

$infolog['courseid'] = $cm->course;
$infolog['cmid'] = $cm->id;

$url = new moodle_url('/local/up1_notificationcourse/notificationcourse.php', array('mod' => $moduletype, 'id'=>$id));

$PAGE->set_url($url);

$infolog['cmurl'] = $url;
$infolog['userid'] = $USER->id;

$context = context_module::instance($cm->id);
require_login($course, true, $cm);

$msgresult = '';
$mform = new local_up1_notificationcourse_notificationcourse_form();



$newformdata = array('id'=>$id, 'mod' => $moduletype);
$mform->set_data($newformdata);
$formdata = $mform->get_data();

$urlcourse = $CFG->wwwroot . '/course/view.php?id='.$course->id;

if ($mform->is_cancelled()) {
    redirect($urlcourse);
}

$params = array();
$params['type_activite'] = $cm->modname;
$params['nom_activite'] = format_string($cm->name);
$params['lien_activite'] =  $CFG->wwwroot . '/mod/' . $moduletype . '/view.php?id=' . $cm->id;

if ($formdata) {
    $msg = get_notificationcourse_message($formdata, $params);
    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    $students = get_users_from_course($course, 'student');
    if (count($students)) {
        $msgresult = send_notificationcourse($students, $msg, $infolog);
    }
}

$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($module->name));
$PAGE->requires->css(new moodle_url('/local/up1_notificationcourse/notificationcourse.css'));

echo $OUTPUT->header();

echo $OUTPUT->heading('Notification par messagerie interne');

if ($msgresult != '') {
    echo $OUTPUT->box_start('info');
    echo $msgresult;
    echo '<p><a href="' . $urlcourse . '">Retour au cours</a></p>';
    echo $OUTPUT->box_end();
} else {

    $mform->display();
}

echo $OUTPUT->footer();
?>
