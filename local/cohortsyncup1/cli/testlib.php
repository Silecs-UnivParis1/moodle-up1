<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage cohortsyncup1
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->dirroot.'/local/cohortsyncup1/locallib.php');


echo "\nchecking cohorts \n";
var_dump(cohort_is_enrolled(44861));
var_dump(cohort_is_enrolled(44862));

echo "\ndeleting cohorts \n";
echo "44861 : ";
var_dump(safe_delete_cohort(44861));

echo "\n44862 :";
var_dump(safe_delete_cohort(44862));
