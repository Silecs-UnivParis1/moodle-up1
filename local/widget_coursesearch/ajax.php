<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/batch_form.php');
require_once($CFG->dirroot . '/course/batch_lib.php');
require_once $CFG->dirroot . '/local/up1_courselist/courselist_tools.php';

global $OUTPUT, $PAGE;

$page      = optional_param('page', 0, PARAM_INT);     // which page to show
$perpage   = optional_param('perpage', 10, PARAM_INT); // how many per page
$topcategory = optional_param('topcategory', 0, PARAM_INT); // category where to search for courses
$topnode   = optional_param('topnode', 0, PARAM_INT);  // virtual table node where to search for courses
$enrolled  = optional_param('enrolled', '', PARAM_TEXT); // has a teacher with such name

$PAGE->set_context(context_system::instance());

$searchconfig = array(
    'fields' => array(
        'Identification' => array(
            'up1nomnorme',
            'up1code',
            'up1rofid',
        ),
        'Indexation' => array(
            'up1composante',
            'up1semestre',
            'up1niveaulmda',
        ),
        'Diplome' => array(
            'up1diplome',
            'up1domaine',
        ),
        'Cycle de vie' => array(
        )
    )
);
if (isset($_REQUEST['fieldsjson'])) {
    $searchconfig['fields'] = json_decode($_REQUEST['fieldsjson'], true);
} else if (isset($_REQUEST['fields'])) {
    $searchconfig['fields'] = $_REQUEST['fields'];
}

$form = new course_batch_search_form(null, $searchconfig, 'get');
$form->set_data(
        (object) array(
            'topcategory' => $topcategory,
            'topnode' => $topnode,
            'enrolled' => $enrolled,
        )
);
$data = $form->get_data();
$totalcount = 0;
$courses = null;
if ($data) {
    $data->visible = 1;
    if (empty($data->sesskey)) {
        // circumvent CSRF protection (unwanted on a GET request)
        $data->sesskey = sesskey();
        $data->_qf__course_batch_search_form = 1;
    }
    $courses = get_courses_batch_search($data, "c.fullname ASC", 0, 9999, $totalcount);
}

if (empty($courses)) {
    if (is_array($courses)) {
        echo "Aucun cours ne correspond aux critères.";
    }
} else {
    $courseformatter = new courselist_format('table');
    echo $courseformatter->get_header();
    foreach ($courses as $course) {
        echo $courseformatter->format_course($course, true) . "\n";
    }
    echo $courseformatter->get_footer() . "\n";
}

$form->display();
