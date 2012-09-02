<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/batch_form.php');
require_once($CFG->dirroot . '/course/batch_lib.php');

$page      = optional_param('page', 0, PARAM_INT);     // which page to show
$perpage   = optional_param('perpage', 10, PARAM_INT); // how many per page

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));

$form = new course_batch_search_form(null, null, 'get');
$data = $form->get_data();
$totalcount = 0;
$courses = null;
if ($data) {
    $data->visible = 1;
    $courses = get_courses_batch_search($data, "c.fullname ASC", $page, $perpage, $totalcount);
}

if (empty($courses)) {
    if (is_array($courses)) {
        echo "Aucun cours ne correspond aux critères.";
    }
} else {
    echo '<table border="0" cellspacing="2" cellpadding="4"><tr>';
    echo '<th class="header" scope="col">Cours correspondant : ' . $totalcount . '</th>';
    echo '</tr>';
    foreach ($courses as $course) {
        echo '<tr>';
        $coursename = get_course_display_name_for_list($course);
        echo '<td><a href="view.php?id='.$course->id.'">'. format_string($coursename) .'</a></td>';
        echo "</tr>";
    }
    echo '</table>';
}

$form->display();
