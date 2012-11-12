<?php

require_once(dirname(dirname(__FILE__)) . "/config.php");
require_once($CFG->dirroot . '/course/lib.php');
require_once(dirname(__FILE__) . '/batch_form.php');
require_once(dirname(__FILE__) . '/batch_lib.php');

global $DB, $PAGE;

$action = optional_param('action', '', PARAM_ALPHA);
$coursesid = optional_param_array('c', array(), PARAM_INT);  // which courses to act on
$page      = optional_param('page', 0, PARAM_INT);     // which page to show
$perpage   = optional_param('perpage', 100, PARAM_INT); // how many per page

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
?>
    <form id="movecourses" action="batch.php" method="post">
        <div class="generalbox boxaligncenter">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>" />
            <table border="0" cellspacing="2" cellpadding="4" class="course-selection">
                <tr>
                    <th><input type="checkbox" name="course-selectall" id="course-selectall" value="0" onclick="toggleCourseSelection()" /></th>
                    <th class="header" scope="col"><?php echo get_string('courses'); ?></th>
                </tr>
                <?php
                foreach ($courses as $course) {
                    echo '<tr>';
                    echo '<td align="center">';
                    echo '<input type="checkbox" name="c[]" value="' . $course->id . '" class="course-select" />';
                    echo '</td>';
                    $linkcss = $course->visible ? '' : ' class="dimmed" ';
                    $coursename = get_course_display_name_for_list($course);
                    echo '<td><a '.$linkcss.' href="view.php?id='.$course->id.'">'. format_string($coursename) .'</a></td>';
                    echo "</tr>";
                }
                ?>
            </table>
            <fieldset><legend><?php echo get_string('actions'); ?></legend>
                <ul>
                    <li>
                        <input type="text" name="batchprefix" />
                        <button name="action" value="prefix"><?php echo get_string('prefix', 'admin'); ?></button>
                    </li>
                    <li>
                        <input type="text" name="batchsuffix" />
                        <button name="action" value="suffix"><?php echo get_string('suffix', 'admin'); ?></button>
                    </li>
                    <li>
                        <button name="action" value="close"><?php echo get_string('close', 'admin'); ?></button>
                    </li>
                </ul>
            </fieldset>
        </div>
    </form>
    <script type="text/javascript">
//<![CDATA[
function toggleCourseSelection() {
    var current = document.getElementById('course-selectall');
    var checkboxes = document.getElementsByClassName('course-select');
    for (var i=0; i<checkboxes.length; i++) {
        if (checkboxes[i].type == 'checkbox') {
            checkboxes[i].checked = (current.value == '0');
        }
    }
    if (current.value == '0') {
        current.value = '1';
    } else {
        current.value = '0';
    }
}
//]]>
    </script>
<?php
}

$form->display();
echo $OUTPUT->footer();
