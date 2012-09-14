<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__FILE__))).'/config.php'); // global moodle config file.
require('./locallib.php');

echo "create categories... \n";
createRofCategories(1);

echo "OK.\n";

