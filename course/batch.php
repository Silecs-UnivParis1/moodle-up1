<?php

require_once(dirname(dirname(__FILE__)) . "/config.php");
require_once($CFG->dirroot . '/course/lib.php');
require_once(dirname(__FILE__) . '/batch_form.php');
require_once(dirname(__FILE__) . '/batch_lib.php');

$action = optional_param('action', '', PARAM_ALPHA);
$page      = optional_param('page', 0, PARAM_INT);     // which page to show
$perpage   = optional_param('perpage', 10, PARAM_INT); // how many per page

require_login(get_site());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/course/batch.php');
$PAGE->set_title(get_string("coursebatchactions", 'admin'));
$PAGE->set_heading(get_string("coursebatchactions", 'admin'));

$form = new course_batch_search_form();
$data = $form->get_data();
$totalcount = 0;
$courses = null;
if ($data) {
    $courses = get_courses_batch_search($data, "c.fullname ASC", $page, $perpage, $totalcount);
}

require_once($CFG->libdir . '/adminlib.php');
admin_externalpage_setup('coursebatchactions', '', array(), $CFG->wwwroot . '/course/batch.php');

$settingsnode = $PAGE->settingsnav->find_active_node();
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("coursebatchactions", 'admin'));

if (empty($courses)) {
    if (is_array($courses)) {
        echo $OUTPUT->heading(get_string("nocoursesyet"));
    }
} else {
    echo '<form id="movecourses" action="category.php" method="post"><div>';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<table border="0" cellspacing="2" cellpadding="4" class="generalbox boxaligncenter"><tr>';
    echo '<th class="header" scope="col">'.get_string('courses').'</th>';
    foreach ($courses as $course) {
        $linkcss = $course->visible ? '' : ' class="dimmed" ';
        $coursename = get_course_display_name_for_list($course);
        echo '<tr>';
        echo '<td><a '.$linkcss.' href="view.php?id='.$course->id.'">'. format_string($coursename) .'</a></td>';
        echo '<td align="center">';
        echo '<input type="checkbox" name="c'.$course->id.'" />';
        echo '</td>';
    }
    echo "</tr>"
        . "</table></div></form>";
}

$form->display();
echo $OUTPUT->footer();
