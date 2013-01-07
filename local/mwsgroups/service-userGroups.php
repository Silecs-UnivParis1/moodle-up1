<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once('./lib.php');

$uid = required_param('uid', PARAM_RAW);
$callback = optional_param('callback', '', PARAM_ALPHANUMEXT); // if set, use jsonp instead of json

$PAGE->set_context(get_system_context());

$res = mws_userGroupsId_fast($uid);

if (empty($callback)) {
    header('Content-Type: application/json; charset="UTF-8"');
    echo json_encode($res);
} else {
    header('Content-Type: application/javascript; charset="UTF-8"');
    echo $callback . '(' . json_encode($res) . ');';
}
