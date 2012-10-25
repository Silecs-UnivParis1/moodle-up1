<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once('./lib.php');

$token = required_param('token', PARAM_RAW);
$maxrows = optional_param('maxRows', 10, PARAM_INT);
$filterstudent = optional_param('filter_student', 'both', PARAM_ALPHANUMEXT);
$callback = optional_param('callback', '', PARAM_ALPHANUMEXT); // if set, use jsonp instead of json

$res = mws_search($token, $maxrows, $filterstudent);
// echo "$token";
// die();

header('Content-Type: application/json; charset="UTF-8"');

if (empty($callback)) {
    echo json_encode($res);
} else {
    echo $callback . '(' . json_encode($res) . ');';
}

