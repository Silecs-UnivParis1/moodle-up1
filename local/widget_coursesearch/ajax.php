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
    if (empty($data->sesskey)) {
        // circumvent CSRF protection (unwanted on a GET request)
        $data->sesskey = sesskey();
        $data->_qf__course_batch_search_form = 1;
    }
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
    print_navigation_bar($totalcount, $page, $perpage, $data);
}

$form->display();

/**
 * Print a list navigation bar
 * Display page numbers, and a link for displaying all entries
 * @param int $totalcount number of entry to display
 * @param int $page page number
 * @param int $perpage number of entry per page
 * @param string $search
 */
function print_navigation_bar($totalcount, $page, $perpage, $search) {
    global $OUTPUT;
    $encodedsearch = http_build_query((array) $search);
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, "?$encodedsearch&perpage=$perpage");
    return; // disable "show all"
    // display
    if ($perpage != 99999 && $totalcount > $perpage) {
        echo "<center><p>";
        echo "<a href=\"search.php?$encodedsearch&amp;perpage=99999\">".get_string("showall", "", $totalcount)."</a>";
        echo "</p></center>";
    } else if ($perpage === 99999) {
        $defaultperpage = 10;
        // If user has course:create or category:manage capability the show 30 records.
        $capabilities = array('moodle/course:create', 'moodle/category:manage');
        if (has_any_capability($capabilities, context_system::instance())) {
            $defaultperpage = 30;
        }
        echo "<center><p>";
        echo "<a href=\"search.php?$encodedsearch&amp;perpage=".$defaultperpage."\">".get_string("showperpage", "", $defaultperpage)."</a>";
        echo "</p></center>";
    }
}
