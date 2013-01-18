<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once(__DIR__ . '/lib.php');

$token = required_param('token', PARAM_RAW);
$maxrows = optional_param('maxRows', 10, PARAM_INT);
$filterstudent = optional_param('filter_student', 'both', PARAM_ALPHA);
$exclude = optional_param('exclude', '', PARAM_TAGLIST); // usernames to exclude, separated by ","
$callback = optional_param('callback', '', PARAM_ALPHANUMEXT); // if set, use jsonp instead of json

$search_u = new mws_search_users();
$search_u->maxrows = $maxrows;
$search_u->filterstudent = $filterstudent;
$search_u->supann = false;
$search_u->exclude = $exclude;
$res  = $search_u->search($token);

if (empty($callback)) {
    header('Content-Type: application/json; charset="UTF-8"');
    echo json_encode($res);
} else {
    header('Content-Type: application/javascript; charset="UTF-8"');
    echo $callback . '(' . json_encode($res) . ');';
}

