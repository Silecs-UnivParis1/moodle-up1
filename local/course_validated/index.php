<?php
require_once('../../config.php');
require_once('libcoursevalidated.php');
require_once('locallib.php');

require_once($CFG->libdir.'/adminlib.php');
admin_externalpage_setup('coursestovalidate');

require_login();

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/course_validated/index.php');
$PAGE->set_title('Espaces de cours en attente d\'approbation');

echo $OUTPUT->header();

// si l'utilisateur a la capacité supervalidator
$table = get_table_course_to_validate(0, 2);
draft_display_table_course_to_validate($table);

// Ancien affichage
$ccc = array();
if ($ccc = get_courses_to_validate()) {
	foreach ($ccc as $cc) {
		print_course_tovalidate($cc);
    }
}

echo $OUTPUT->footer();
