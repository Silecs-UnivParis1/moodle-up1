<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/batch_lib.php');

$perpage = 20;
$search = new stdClass();

$page = optional_param('page', 0, PARAM_INTEGER);
$search->search = optional_param('search', '', PARAM_RAW_TRIMMED);
$search->startdateafter = isoDateToTs(optional_param('startdateafter', '', PARAM_RAW_TRIMMED));
$search->startdatebefore = isoDateToTs(optional_param('startdatebefore', '', PARAM_RAW_TRIMMED));

if (isset($_GET['custom'])) {
    foreach ($_GET['custom'] as $name => $value) {
        if (is_string($value)) {
            $search->{'profile_field_' . $name} = trim($value);
        }
    }
}

$totalcount = 0;
$courses = null;
if ($search) {
    $search->visible = 1;
    $courses = get_courses_batch_search($search, "c.fullname ASC", $page, $perpage, $totalcount);
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
        $url = new moodle_url('/course/view.php?id=' . $course->id);
        echo '<td><a href="' . $url . '">'. clean_text($coursename) .'</a></td>';
        echo "</tr>";
    }
    echo '</table>';
}

function isoDateToTs($date) {
    if (preg_match('/^(\d{4})-(\d\d)-(\d\d)$/', $date, $m)) {
        return make_timestamp($m[1], $m[2], $m[3]);
    }
    return false;
}