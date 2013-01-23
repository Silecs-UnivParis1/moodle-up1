<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib_wizard.php');


echo "\n\n" . wizard_rofid_to_idnumber('UP1-C25379') ;
echo "\n\n" . wizard_rofid_to_idnumber('UP1-C25378') ;
echo "\n\n";