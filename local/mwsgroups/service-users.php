<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once(__DIR__ . '/lib.php');

$token = required_param('token', PARAM_RAW);
$maxrows = optional_param('maxRows', 10, PARAM_INT);
$filterstudent = optional_param('filter_student', 'both', PARAM_ALPHA);
$callback = optional_param('callback', '', PARAM_ALPHANUMEXT); // if set, use jsonp instead of json

if (!$maxrows || $maxrows > MWS_SEARCH_MAXROWS) {
    $maxrows = MWS_SEARCH_MAXROWS;
}

$res = mws_search_users($token, $maxrows, $filterstudent, false);

if (empty($callback)) {
    header('Content-Type: application/json; charset="UTF-8"');
    echo json_encode($res);
} else {
    header('Content-Type: application/javascript; charset="UTF-8"');
    echo $callback . '(' . json_encode($res) . ');';
}

