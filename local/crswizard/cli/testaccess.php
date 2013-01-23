<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../libaccess.php');


var_dump(wizard_membersof_permitted_cohorts('exterminator'));

var_dump(wizard_membersof_permitted_cohorts('creator'));

// var_dump(wizard_membersof_permitted_cohorts('validator'));



echo "\n\n validator has_cohort_permission\n";
var_dump(wizard_has_cohort_permission('validator', 2681));
var_dump(wizard_has_cohort_permission('validator', 3));
var_dump(wizard_has_cohort_permission('validator', 4));
var_dump(wizard_has_cohort_permission('validator', 6));

echo "\n\n creator has_cohort_permission\n";
var_dump(wizard_has_cohort_permission('creator', 2681));
var_dump(wizard_has_cohort_permission('creator', 3));
var_dump(wizard_has_cohort_permission('creator', 1));
var_dump(wizard_has_cohort_permission('creator', 6));

echo "\n\n validator has_permission\n";
var_dump(wizard_has_permission('validator', 2681));
var_dump(wizard_has_permission('validator', 3));
var_dump(wizard_has_permission('validator', 4));
var_dump(wizard_has_permission('validator', 6));

var_dump(wizard_who_has_permission('validator'));