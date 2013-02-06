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
$perpage = optional_param('perpage', 20, PARAM_INT); // nb of rows per page

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
    echo "<p>Vous êtes super-approbateur global.</p>";
    $courselist = get_id_courses_to_validate(0, 0);
} elseif ($svcats = wizard_supervalidator_which_categories($USER->id)) {
    if (count($svcats) == 1) {
        echo "<p>Vous êtes super-approbateur pour la catégorie : ";
    } else {
        echo "<p>Vous êtes super-approbateur pour les " . count($svcats) . " catégories : ";
    }
    echo '<b>' . join('</b>, <b>', array_values($svcats)) . "</b>.<p>";
    $courselist = get_id_courses_to_validate(0, 0, true);
    $table = get_table_course_to_validate(0, $systemcontext, true);
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
}

if (count($table->data) > 0) {
    $baseurl = new moodle_url('/local/course_validated/index.php');;
    $pagingbar = new paging_bar(count($table->data), $page, $perpage, $baseurl);

    if (count($table->data) > $perpage) {
        $table->data = array_slice($table->data, $page*$perpage, $perpage);
    }
    $table->data = array_merge(get_table_course_header(), $table->data);

    echo html_writer::table($table);
    echo $OUTPUT->render($pagingbar);
    $ddperpage = array( 20, 50, 100, 1000);
    echo '<form method="GET">';
    echo html_writer::select(array_combine($ddperpage, $ddperpage), 'perpage', '20', false);
    echo "&nbsp résultats par page &nbsp";
    echo '<input type="submit" value="ok">';
    echo '</form>';
}

echo $OUTPUT->footer();
