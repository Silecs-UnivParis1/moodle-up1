<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__DIR__))).'/config.php'); // global moodle config file.
require(__DIR__ . '/../locallib.php');

echo "Creating list pages for ROF course categories... \n";
listpages_create();

echo "OK.\n";
