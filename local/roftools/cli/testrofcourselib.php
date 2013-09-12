<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__DIR__))).'/config.php'); // global moodle config file.
require(__DIR__ . '/../rofcourselib.php');



list($referencenb, $referencefirst) = rof_get_referenced_objects();
print_r ($referencenb);
print_r ($referencefirst);


return 0;
