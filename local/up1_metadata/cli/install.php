<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require "../datalib.php";
require "../insertlib.php";


$metadata = up1_course_metadata();

echo "Création des catégories :\n";
insert_metadata_categories($metadata, 'course');

echo "\nCréation des champs :\n";
insert_metadata_fields($metadata, 'course');

// delete_metadata_fields();
