<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once('./lib.php');

$token = required_param('token', PARAM_ALPHANUMEXT);
$maxrows = optional_param('maxRows', 10, PARAM_INT);
$callback = optional_param('callback', '', PARAM_ALPHANUMEXT); // if set, use jsonp instead of json

$res = mwsteachers_search($token, $maxrows);
// echo "$token";
// die();

header('Content-Type: application/json; charset="UTF-8"');

if (empty($callback)) {
    echo json_encode($res);
} else {
    echo $callback . '(' . json_encode($res) . ');';
}

