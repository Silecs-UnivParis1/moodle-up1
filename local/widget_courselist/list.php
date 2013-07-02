<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/batch_lib.php');
require_once $CFG->dirroot . '/local/up1_courselist/courselist_tools.php';

global $OUTPUT, $PAGE;

$perpage = 10;
$search = new stdClass();

$format = optional_param('format', 'table', PARAM_ALPHA);
$search->search = optional_param('search', '', PARAM_RAW_TRIMMED);
$search->startdateafter = isoDateToTs(optional_param('startdateafter', '', PARAM_RAW_TRIMMED));
$search->startdatebefore = isoDateToTs(optional_param('startdatebefore', '', PARAM_RAW_TRIMMED));
$search->enrolled  = optional_param('enrolled', '', PARAM_TEXT); // has a teacher with such name
if (isset($_REQUEST['enrolledroles'])) {
    if (is_array($_REQUEST['enrolledroles'])) {
        $search->enrolledroles = optional_param_array('enrolledroles', array(), PARAM_INT);
    } else {
        $search->enrolledroles = explode(',', optional_param('enrolledroles', '', PARAM_SEQUENCE));
    }
} else if (isset($_REQUEST['fields'])) {
    $search->enrolledroles = array(3);
}

if ($format !== 'list') {
    $format = 'table';
}

if (!empty($_GET['custom'])) {
    foreach ($_GET['custom'] as $name => $value) {
        if (is_string($value)) {
            $fname = 'profile_field_' . strtolower($name);
            $search->$fname = trim($value);
        }
    }
}

$PAGE->set_context(context_system::instance());

$totalcount = 0;
$courses = null;
if ($search) {
    $search->visible = 1;
    $courses = get_courses_batch_search($search, "c.fullname ASC", 0, 9999, $totalcount);
}

if (empty($courses)) {
    if ($search) {
        echo "Aucun cours ne correspond aux critères.";
    }
} else {
    $courseformatter = new courselist_format($format);
    echo $courseformatter->get_header();
    foreach ($courses as $course) {
        echo $courseformatter->format_course($course, true) . "\n";
    }
    echo $courseformatter->get_footer() . "\n";
}

function isoDateToTs($date) {
    if (preg_match('/^(\d{4})-(\d\d)-(\d\d)$/', $date, $m)) {
        return make_timestamp($m[1], $m[2], $m[3]);
    }
    return false;
}