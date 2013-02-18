<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once(__DIR__ . '/lib.php');

$node = optional_param('node', '/0', PARAM_RAW);
$callback = optional_param('callback', '', PARAM_ALPHANUMEXT); // if set, use jsonp instead of json

$res  = get_children($node);

if (empty($callback)) {
    header('Content-Type: application/json; charset="UTF-8"');
    echo json_encode($res);
} else {
    header('Content-Type: application/javascript; charset="UTF-8"');
    echo $callback . '(' . json_encode($res) . ');';
}
