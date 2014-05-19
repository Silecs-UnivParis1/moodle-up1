<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__DIR__))).'/config.php'); // global moodle config file.
require(dirname(__DIR__) . '/updatelib.php');

echo "updating course categories... \n";

update_categories_idnumber();

update_rof_settings();

echo "OK.\n";
