<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__DIR__))).'/config.php'); // global moodle config file.
require(__DIR__ . '/../locallib.php');

echo "create categories... \n";
createRofCategories(1);

echo "OK.\n";

