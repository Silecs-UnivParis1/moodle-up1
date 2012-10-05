<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once('./lib.php');

$uid = required_param('uid', PARAM_ALPHANUMEXT);

$res = mws_userGroupsId($uid);

header('Content-Type: application/json; charset="UTF-8"');

echo json_encode($res);

