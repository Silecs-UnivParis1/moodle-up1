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
 *   - If you have a large number of users, you may want to raise the memory limits
 *     by passing -d memory_limit=256M
 *   - For debugging & better logging, you are encouraged to use in the command line:
 *     -d log_errors=1 -d error_reporting=E_ALL -d display_errors=0 -d html_errors=0
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->dirroot.'/local/cohortsyncup1/lib.php');


// now get cli options
list($options, $unrecognized) = cli_get_params(array('help'=>false, 'init'=>false, 'cleanall'=>false, 'allGroups'=>false,
                                               'since'=>false, 'verb'=>1, 'printlast'=>false),
                                               array('h'=>'help', 'i'=>'init'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"Synchronize cohorts from PAGS webservice. Normally, to be executed by a cron job.

Options:
--verb=N              Verbosity (0 to 3), 1 by default
--since=(timestamp)   Apply only to users synchronized since given timestamp. If not set, use last cohort sync.
--allGroups           Uses 'allGroups' webservice instead of the standard one (from users)
-i, --init            Apply to all users ever synchronized (like --since=0)
-h, --help            Print out this help
--cleanall            Empty cohort_members, then cohort
--printlast           Display last syncs (diagnostic)

If you want to force initialization, you should execute --cleanall first but it may be faster
to manually empty tables cohort and cohort_members with the following MySQL command:
DELETE FROM cohort, cohort_members  USING cohort INNER JOIN cohort_members
    WHERE cohort.component = 'local_cohortsyncup1' AND cohort.id = cohort_members.cohortid;

Example:
/usr/bin/php local/cohortsyncup1/cli/sync_cohorts.php --init --verb=2

";

if ( ! empty($options['help']) ) {
    echo $help;
    return 0;
}


// Ensure errors are well explained
$CFG->debug = DEBUG_NORMAL;

if ( $options['cleanall'] ) {
    cohorts_cleanall();
    return 0;
}

if ( $options['printlast'] ) {
    echo "last sync from users = \n";
    print_r(get_cohort_last_sync('sync'));
    echo "last sync AllGroups = \n";
    print_r(get_cohort_last_sync('syncAllGroups'));
    return 0;
}


if ( $options['init'] ) {
    $since = 0;
} elseif ( $options['since'] || $options['since'] === '0' ) {
    $since = $options['since'];
} else {
    if ($options['allGroups']) {
        $last = get_cohort_last_sync('syncAllGroups');
    } else {
        $last = get_cohort_last_sync('sync');
    }
    $since = $last['begin'];
}

if ($options['allGroups']) {
    sync_cohorts_all_groups($since, 0, $options['verb']);
} else {
    sync_cohorts_from_users($since, 0, $options['verb']);
}
