<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once(__DIR__ . '/lib.php');

$token = required_param('token', PARAM_RAW);
$maxrows = optional_param('maxRows', 0, PARAM_INT);
$maxrowsfor = array(
    'users' => optional_param('userMaxRows', 0, PARAM_INT),
    'groups' => optional_param('groupMaxRows', 0, PARAM_INT),
);
$filterstudent = optional_param('filter_student', 'both', PARAM_ALPHA);
$filtergroupcat = optional_param('filter_group_category', '', PARAM_ALPHANUMEXT);
$callback = optional_param('callback', '', PARAM_ALPHANUMEXT); // if set, use jsonp instead of json

if (!$maxrows || $maxrows > MWS_SEARCH_MAXROWS) {
    $maxrows = MWS_SEARCH_MAXROWS;
}
foreach ($maxrowsfor as $type => $max) {
    if (!$max || $max > MWS_SEARCH_MAXROWS) {
        $maxrowsfor[$type] = $maxrows;
    }
}

$PAGE->set_context(get_system_context());

$res = mws_search($token, $maxrowsfor['users'], $maxrowsfor['groups'], $filterstudent, $filtergroupcat);

if (empty($callback)) {
    header('Content-Type: application/json; charset="UTF-8"');
    echo json_encode($res);
} else {
    header('Content-Type: application/javascript; charset="UTF-8"');
    echo $callback . '(' . json_encode($res) . ');';
}

