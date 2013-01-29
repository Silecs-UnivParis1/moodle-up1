<?php
require_once('../../config.php');
require_once('locallib.php');
require_once($CFG->libdir.'/adminlib.php');

/**
 * @todo delete/show/hide/validate : GET->POST
 * @todo manage localsupervalidator capability
 */

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
   redirect(new moodle_url('/local/course_validated/index.php'));
}

if (!empty($validate)) {
   validate_course($validate);
   redirect(new moodle_url('/local/course_validated/index.php'));
}

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/course_validated/index.php');
$PAGE->set_title("Espaces de cours en attente d'approbation");
$PAGE->set_heading("Espaces de cours en attente d'approbation");
$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();

if (has_capability('local/crswizard:supervalidator', $systemcontext)) {
    $table = get_table_course_to_validate(0, $systemcontext);
    echo "<p>Vous êtes super-approbateur.</p>";
    $courselist = get_id_courses_to_validate(0, 0);
} else {
    $table = get_table_course_to_validate($USER->id, $systemcontext);
    // echo "<p>Vous n'êtes pas super-approbateur.</p>";
    $courselist = get_id_courses_to_validate($USER->id, 0);
}
$cnt = ($courselist == '' ? 0 : count(explode(',', $courselist)));

if ( $cnt == 0 ) {
    if (has_capability('local/crswizard:supervalidator', $systemcontext)) {
        echo $OUTPUT->box("Aucune demande de création de cours n'est en attente d'approbation.");
    } else {
        echo $OUTPUT->box("Aucune demande de création de cours n'est en attente de votre approbation.");
    }
} else {
    echo "<p>Il y a <b>" . $cnt . "</b> espaces de cours en attente d'approbation.</p>\n";
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
