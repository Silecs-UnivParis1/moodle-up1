<?php
require_once('../../config.php');
require_once('libcoursevalidated.php');

require_once($CFG->libdir.'/adminlib.php');
admin_externalpage_setup('coursestovalidate');

// limite cette page au compte admin
require_login();

$hide = optional_param('hide', 0, PARAM_INT);
$show = optional_param('show', 0, PARAM_INT);
$validate = optional_param('validate', 0, PARAM_INT);

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);
has_capability('enrol/cohort:unenrol', $systemcontext);

// Hide or show a course
if (!empty($hide) or !empty($show)) {
	show_or_hide($show, $hide);
}

if (!empty($validate)) {
	validate_course($validate);
}

$PAGE->set_url('/local/course_validated/index.php');
$PAGE->set_title('Validation des cours');

echo $OUTPUT->header();
$ccc = array();


if ($ccc = get_courses_to_validate()) {
	foreach ($ccc as $cc) {
		print_course_tovalidate($cc);
    }
}

echo $OUTPUT->footer();
