<?php
require_once('../../config.php');
require_once('libcoursevalidated.php');
require_once('locallib.php');

require_once($CFG->libdir.'/adminlib.php');
admin_externalpage_setup('coursestovalidate');

/* @var $OUTPUT core_renderer */
/* @var $PAGE page_base */

$page = optional_param('page', 0, PARAM_INT);

require_login();
// action parameters (value = course id)
$hide = optional_param('hide', 0, PARAM_INT);
$show = optional_param('show', 0, PARAM_INT);
$validate = optional_param('validate', 0, PARAM_INT);

// Hide or show a course
if (!empty($hide) or !empty($show)) {
   show_or_hide($show, $hide);
}

if (!empty($validate)) {
   validate_course($validate);
}

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/course_validated/index.php');
$PAGE->set_title("Espaces de cours en attente d'approbation");
$PAGE->set_heading("Espaces de cours en attente d'approbation");

echo $OUTPUT->header();

// si l'utilisateur a la capacité supervalidator
$table = get_table_course_to_validate(0, 2);
// sinon
// $table = get_table_course_to_validate($USER->id, 2);

if (empty($table->data)) {
    echo $OUTPUT->box("Aucune demande de création de cours n'est en attente de validation.");
} else {
    $n = count(get_id_courses_to_validate(0, 0));
    echo "<p>Il y a <b>" . $n . "</b> espaces de cours en attente d'approbation.</p>\n";
    $perpage = 20;
    $baseurl = new moodle_url('/local/course_validated/index.php');;
    $pagingbar = new paging_bar(count($table->data), $page, $perpage, $baseurl);

    if (count($table->data) > $perpage) {
        $table->data = array_slice($table->data, $page*$perpage, $perpage);
    }
    $table->data = array_merge(get_table_course_header(), $table->data);

    echo html_writer::table($table);
    echo $OUTPUT->render($pagingbar);
}

echo $OUTPUT->footer();
