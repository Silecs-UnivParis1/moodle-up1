<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__DIR__))).'/config.php'); // global moodle config file.
require(__DIR__ . '/../locallib.php');

echo "create categories... \n";
create_rof_categories(1);

echo "OK.\n";

