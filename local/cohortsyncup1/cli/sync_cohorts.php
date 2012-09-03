<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage cohortsyncup1
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 * Notes:
 *   - it is required to use the web server account when executing PHP CLI scripts
 *   - If you have a large number of users, you may want to raise the memory limits
 *     by passing -d momory_limit=256M
 *   - For debugging & better logging, you are encouraged to use in the command line:
 *     -d log_errors=1 -d error_reporting=E_ALL -d display_errors=0 -d html_errors=0
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require_once($CFG->dirroot.'/local/cohortsyncup1/lib.php');

// Ensure errors are well explained
$CFG->debug = DEBUG_NORMAL;

if ( isset($argv[1]) && $argv[1]==='init' ) {
    $since = 0;
} else {
    $since = time() - (24*60*60 + 10*60) ; // il y a 24 h + 10 min
}

sync_cohorts($since, 0);

