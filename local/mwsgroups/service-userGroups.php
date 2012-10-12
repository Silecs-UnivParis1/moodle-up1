<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once('./lib.php');

$uid = required_param('uid', PARAM_RAW);
$callback = optional_param('callback', '', PARAM_ALPHANUMEXT); // if set, use jsonp instead of json

$res = mws_userGroupsId_bis($uid);

header('Content-Type: application/json; charset="UTF-8"');

if (empty($callback)) {
    echo json_encode($res);
} else {
    echo $callback . '(' . json_encode($res) . ');';
}
