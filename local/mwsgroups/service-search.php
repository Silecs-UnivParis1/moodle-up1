<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once('./lib.php');

$token = required_param('token', PARAM_ALPHANUMEXT);
$maxrows = optional_param('maxRows', 10, PARAM_ALPHANUMEXT);

$res = mws_search($token, $maxrows);

header('Content-Type: application/json; charset="UTF-8"');

echo json_encode($res);

