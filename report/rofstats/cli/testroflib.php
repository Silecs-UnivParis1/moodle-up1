<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require('../roflib.php');

// echo typeDiplomeOrderedList();

// echo fmtPath(getCourseFirstPath($argv[1]), 'combined');
// echo "\n";

// rof_insert_paths_statistics(0);
$path = array('27', 'UP1-PROG37797', 'UP1-PROG25375');
$cpath = array(
'27' => '27-Mathématiques et Informatique',
'UP1-PROG37797' => 'Licence Informatique (MIAGE classique)',
'UP1-PROG25375' => 'Semestre 5 L3'
);

/*
$catid = rof_rofpath_to_category($path);
echo "$catid";
$categorie = $DB->get_record('course_categories', array('id' => $catid));
var_dump ($categorie);
*/


// N'existe pas
/*
$path = array('27', 'UP1-PROG17797', 'UP1-PROG25375');
//27-Mathématiques et Informatique / Licence Informatique (MIAGE classique) /  Semestre 5 L3
$catid = rofpath_to_category($path);
*/

echo "\n\n" . rof_get_code_or_rofid('UP1-C25379'); // should return MA32I310
echo "\n\n" . rof_get_code_or_rofid('UP1-C99999');
echo "\n\n" . rof_get_code_or_rofid('UP1-PROG37797');
echo "\n\n" ;

echo "rof_get_metadata (3)" ;
var_dump(rof_get_metadata('UP1-PROG25375'));
//var_dump(rof_get_metadata($path));
//var_dump(rof_get_metadata($cpath));

var_dump(rof_get_metadata('UP1-C25379'));

return 0;
