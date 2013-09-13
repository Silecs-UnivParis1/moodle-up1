<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__DIR__))).'/config.php'); // global moodle config file.
require(__DIR__ . '/../rofcourselib.php');



list($referencenb, $referencefirst) = rof_get_referenced_objects();
echo "compteur références : ROFobject => nb \n";
print_r ($referencenb);
echo "première référence : ROFobject => courseid \n";
print_r ($referencefirst);

$rofid = 'UP1-PROG28809';
echo "courses referencing $rofid : \n";
$courses = rof_object_is_referenced_by($rofid);
print_r($courses);

return 0;
