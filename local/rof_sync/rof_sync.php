<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__FILE__))).'/config.php'); // global moodle config file.
require('./locallib.php');


// fetchConstants();

// echo fetchComponents();

echo fetchPrograms(2);

// echo fetchCoursesByProgram('UP1-PROG29332', 2, 1);

echo fetchCourses(2);

echo "\n\n";
return 0;
