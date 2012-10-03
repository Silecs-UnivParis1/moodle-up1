<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require('../roflib.php');

// echo typeDiplomeOrderedList();

// echo fmtPath(getCourseFirstPath($argv[1]), 'combined');
// echo "\n";

rof_insert_paths_statistics(0);

return 0;
