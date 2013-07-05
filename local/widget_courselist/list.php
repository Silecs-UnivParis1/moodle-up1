<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once __DIR__ . '/locallib.php';

global $OUTPUT, $PAGE;

$perpage = 10;
$search = new stdClass();

$format = optional_param('format', 'table', PARAM_ALPHA);
$search->search          = optional_param('search', '', PARAM_RAW_TRIMMED);
$search->startdateafter  = isoDateToTs(optional_param('startdateafter', '', PARAM_RAW_TRIMMED));
$search->startdatebefore = isoDateToTs(optional_param('startdatebefore', '', PARAM_RAW_TRIMMED));
$search->createdafter    = isoDateToTs(optional_param('createdafter', '', PARAM_RAW_TRIMMED));
$search->createdbefore   = isoDateToTs(optional_param('createdbefore', '', PARAM_RAW_TRIMMED));
$search->category        = optional_param('category', 0, PARAM_INT); // category where to search for courses
$search->topcategory     = optional_param('topcategory', 0, PARAM_INT); // category where to search for courses (recursively)
$search->node            = optional_param('node', 0, PARAM_INT);  // virtual table node where to search for courses (recursively)
$search->enrolled        = optional_param('enrolled', '', PARAM_TEXT); // has a teacher with such name
if (isset($_REQUEST['enrolledroles'])) {
    if (is_array($_REQUEST['enrolledroles'])) {
        $search->enrolledroles = optional_param_array('enrolledroles', array(), PARAM_INT);
    } else {
        $search->enrolledroles = explode(',', optional_param('enrolledroles', '', PARAM_SEQUENCE));
    }
} else if (isset($_REQUEST['enrolled'])) {
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

echo widget_courselist_query($format, $search);


function isoDateToTs($date) {
    if (preg_match('/^(\d{4})-(\d\d)-(\d\d)$/', $date, $m)) {
        return make_timestamp($m[1], $m[2], $m[3]);
    }
    return false;
}