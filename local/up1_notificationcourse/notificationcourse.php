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

$id = required_param('id', PARAM_INT);
$moduletype = required_param('mod', PARAM_ALPHA);

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

require_login($course, true, $cm);

$url = new moodle_url('/local/up1_notificationcourse/notificationcourse.php', array('mod' => $moduletype, 'id'=>$id));
$PAGE->set_url($url);

$site = get_site();

$msgresult = '';
$infolog = array();
$infolog['courseid'] = $cm->course;
$infolog['cmid'] = $cm->id;
$infolog['cmurl'] = $url;
$infolog['userid'] = $USER->id;

$urlcourse = $CFG->wwwroot . '/course/view.php?id='.$course->id;
$urlactivite = $CFG->wwwroot . '/mod/' . $moduletype . '/view.php?id=' . $cm->id;

$coursepath = get_pathcategories_course($PAGE->categories, $course);

$mailsubject = get_email_subject($site->shortname, $course->shortname, format_string($cm->name));

$msgbodyinfo = array();
$msgbodyinfo['user'] = $USER->firstname . ' ' . $USER->lastname;
$msgbodyinfo['shortnamesite'] = $site->shortname;
$msgbodyinfo['nomactivite'] = format_string($cm->name);
$msgbodyinfo['urlactivite'] = $urlactivite;
$msgbodyinfo['urlcourse'] = $urlcourse;
$msgbodyinfo['shortnamecourse'] = $course->shortname;
$msgbodyinfo['fullnamecourse'] = $course->fullname;
$msgbodyinfo['coursepath'] = $coursepath;

$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($module->name));
$PAGE->requires->css(new moodle_url('/local/up1_notificationcourse/notificationcourse.css'));

$recipicents = '';
$students = array();

// le groupmode
$groupmode = groups_get_activity_groupmode($cm);

if ($groupmode == 0) {
    // pas de groupe, envoyé à tous les étudiants
    $students = get_users_from_course($course, 'student');
    $recipicents = get_label_destinataire(count($students), $cm->groupingid, $msgbodyinfo);
} elseif ($cm->groupingid != 0) {
    //envoyé au groupe
    $students = groups_get_grouping_members($cm->groupingid);
    $recipicents = get_label_destinataire(count($students), $cm->groupingid, $msgbodyinfo);
} else {
    $recipicents = get_string('norecipient', 'local_up1_notificationcourse');
}

$mform = new local_up1_notificationcourse_notificationcourse_form(null,
    array('urlactivite' => $urlactivite, 'coursepath' => $coursepath));

$newformdata = array('id'=>$id, 'mod' => $moduletype);
$mform->set_data($newformdata);
$formdata = $mform->get_data();


if ($mform->is_cancelled()) {
    redirect($urlcourse);
}

if ($formdata) {
    $msg = get_notificationcourse_message($mailsubject, $msgbodyinfo, $formdata->complement);
    if (count($students)) {
        $msgresult = send_notificationcourse($students, $msg, $infolog);
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('sendnotification', 'local_up1_notificationcourse'));

if ($msgresult != '') {
    echo $OUTPUT->box_start('info');
    echo $msgresult;
    echo html_Writer::tag('p', html_Writer::link($urlcourse, get_string('returncourse', 'local_up1_notificationcourse')));
    echo $OUTPUT->box_end();
} else {
    echo html_Writer::tag('p', $recipicents, array('class' => 'notificationlabel'));

    $senderlabel = html_Writer::tag('span', get_string('sender', 'local_up1_notificationcourse'), array('class' => 'notificationgras'));
    $sender = $site->shortname . ' &#60;'. $CFG->noreplyaddress . '&#62;';
    echo html_Writer::tag('p', $senderlabel . $sender, array('class' => 'notificationlabel'));

    echo html_Writer::tag('p', get_string('subject', 'local_up1_notificationcourse') . $mailsubject, array('class' => 'notificationlabel'));

    $msgbody = get_email_body($msgbodyinfo, 'html');
    echo html_Writer::tag('p', get_string('body', 'local_up1_notificationcourse'), array('class' => 'notificationlabel notificationgras'));
    echo html_Writer::tag('p', $msgbody, array('class' => 'notificationlabel'));
    echo html_Writer::tag('div', get_string('complement', 'local_up1_notificationcourse'), array('class' => 'notificationlabel'));

    $mform->display();
}

echo $OUTPUT->footer();
?>
