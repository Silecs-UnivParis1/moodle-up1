<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__FILE__))).'/config.php'); // global moodle config file.
require('./locallib.php');


fetchConstants();

echo setComponents();

echo fetchPrograms(2);

echo fetchCoursesByProgram('UP1-PROG29332', 2, 1);

echo fetchCourses(2);

setCourseParents(2, false);

// echo fmtPath(getCourseFirstPath("UP1-C20867"), 'combined');

echo "\n\n";
return 0;
