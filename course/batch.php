<?php

require_once(dirname(dirname(__FILE__)) . "/config.php");
require_once($CFG->dirroot . '/course/lib.php');
require_once(dirname(__FILE__) . '/batch_form.php');
require_once(dirname(__FILE__) . '/batch_lib.php');

global $DB, $PAGE;

$action = optional_param('action', '', PARAM_ALPHA);
$coursesid = optional_param_array('c', array(), PARAM_INT);  // which courses to act on
$page      = optional_param('page', 0, PARAM_INT);     // which page to show
$perpage   = optional_param('perpage', 10, PARAM_INT); // how many per page

require_login(get_site());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/course/batch.php');
$PAGE->set_title(get_string("coursebatchactions", 'admin'));
$PAGE->set_heading(get_string("coursebatchactions", 'admin'));

if ($action) {
    $courses = $DB->get_records_list('course', 'id', $coursesid);
    switch ($action) {
        case 'prefix':
            $prefix = optional_param('batchprefix', '', PARAM_RAW);
            if ($prefix) {
                foreach ($courses as $course) {
                    $course->fullname = $prefix . $course->fullname;
                    // $course->shortname = $prefix . $course->shortname;
                    $DB->update_record('course', $course);
                }
                redirect($CFG->wwwroot . '/course/batch.php');
                exit();
            }
            break;
        case 'suffix':
            $suffix = optional_param('batchsuffix', '', PARAM_RAW);
            if ($suffix) {
                foreach ($courses as $course) {
                    $course->fullname = $course->fullname . $suffix;
                    // $course->shortname = $course->shortname . $suffix;
                    $DB->update_record('course', $course);
                }
                redirect($CFG->wwwroot . '/course/batch.php');
                exit();
            }
            break;
        case 'close':
            foreach ($courses as $course) {
                $course->visible = 0;
                $DB->update_record('course', $course);
            }
            redirect($CFG->wwwroot . '/course/batch.php');
            exit();
    }
}

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
    echo '<form id="movecourses" action="batch.php" method="post"><div class="generalbox boxaligncenter">';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<table border="0" cellspacing="2" cellpadding="4"><tr>';
    echo '<th class="header" scope="col">'.get_string('courses').'</th>';
    echo '</tr>';
    foreach ($courses as $course) {
        echo '<tr>';
        echo '<td align="center">';
        echo '<input type="checkbox" name="c[]" value="' . $course->id . '" />';
        echo '</td>';
        $linkcss = $course->visible ? '' : ' class="dimmed" ';
        $coursename = get_course_display_name_for_list($course);
        echo '<td><a '.$linkcss.' href="view.php?id='.$course->id.'">'. format_string($coursename) .'</a></td>';
        echo "</tr>";
    }
    echo '</table>';

    echo '<div><input type="text" name="batchprefix" /> '
        , '<button name="action" value="prefix">' , get_string('prefix', 'admin') , '</button></div>';
    echo '<div><input type="text" name="batchsuffix" /> '
        , '<button name="action" value="suffix">' , get_string('suffix', 'admin') , '</button></div>';
    echo '<div><button name="action" value="close">' , get_string('close', 'admin') , '</button></div>';
    echo '</div></form>';
}

$form->display();
echo $OUTPUT->footer();
