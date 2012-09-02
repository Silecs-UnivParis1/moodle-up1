<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__FILE__))).'/config.php'); // global moodle config file.
require('./locallib.php');

//echo serializedToSql("A,B,C,KK,LL");

echo "create categories... \n";
createRofCategories(3);

echo "OK.\n";

