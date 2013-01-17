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

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/course_validated/index.php');
$PAGE->set_title("Espaces de cours en attente d'approbation");

echo $OUTPUT->header();

// si l'utilisateur a la capacité supervalidator
$table = get_table_course_to_validate(0, 2);

if (empty($table->data)) {
    echo $OUTPUT->box("Aucune demande de création de cours n'est en attente de validation.");
} else {
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
