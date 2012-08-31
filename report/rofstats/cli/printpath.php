<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require('../lib.php');

// echo typeDiplomeOrderedList();

echo fmtPath(getCourseFirstPath($argv[1]), 'combined');
echo "\n";
return 0;
