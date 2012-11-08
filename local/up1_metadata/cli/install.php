<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require "../locallib.php";

echo "Création des catégories :\n";
insert_metadata_categories();

echo "\nCréation des champs :\n";
insert_metadata_fields();

// delete_metadata_fields();
