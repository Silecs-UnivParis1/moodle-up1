<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once(__DIR__ . '/lib.php');

$node = optional_param('node', '/0', PARAM_RAW);

$res  = get_children($node);

header('Content-Type: application/json; charset="UTF-8"');
echo json_encode($res);
