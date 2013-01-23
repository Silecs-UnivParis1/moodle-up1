<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require('../roflib.php');

// echo typeDiplomeOrderedList();

// echo fmtPath(getCourseFirstPath($argv[1]), 'combined');
// echo "\n";

// rof_insert_paths_statistics(0);

$path = array('27', 'UP1-PROG37797', 'UP1-PROG25375');
//27-Mathématiques et Informatique / Licence Informatique (MIAGE classique) /  Semestre 5 L3
$catid = rofpath_to_category($path);
echo "$catid";
$categorie = $DB->get_record('course_categories', array('id' => $catid));
var_dump ($categorie);

// N'existe pas
$path = array('27', 'UP1-PROG17797', 'UP1-PROG25375');
//27-Mathématiques et Informatique / Licence Informatique (MIAGE classique) /  Semestre 5 L3
$catid = rofpath_to_category($path);

return 0;
