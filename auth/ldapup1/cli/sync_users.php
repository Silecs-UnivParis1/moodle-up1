<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    auth
 * @subpackage ldapup1
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from official auth_ldap
 *
 * user sync script.
 *
 * This script is meant to be called from a cronjob to sync moodle with the LDAP
 * backend in those setups where the LDAP backend acts as 'master'.
 *
 * Sample cron entry:
 * # 5 minutes past 4am
 * 5 4 * * * $sudo -u www-data /usr/bin/php /var/www/moodle/auth/ldap/cli/sync_users.php
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions

// now get cli options
list($options, $unrecognized) = cli_get_params(array('help'=>false, 'noupdate'=>false, 'init'=>false, 'since'=>0, 'verb'=>1, 'output'=>'file'),
                                               array('h'=>'help', 'n'=>'noupdate', 'i'=>'init'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"Synchronize users from LDAP. Normally, to be executed by a cron job.

If you want to force initialization, it can be useful to empty tables
user and user_sync first with the following SQL command:
DELETE FROM user, user_sync  USING user INNER JOIN user_sync
    WHERE user_sync.ref_plugin = 'auth_ldapup1' AND user.id = user_sync.userid;

Options:
--verb=N              Verbosity (0 to 1), 1 by default
--since=(datetime)    Fetch only to users with modifyTimestamp >= given datetime. If not set, use last ldap sync.
                      datetime in LDAP native format, eg '20120930123456Z' = 2012, sept. 30, 12:34:56 UTC
-i, --init            Apply to all users available in LDAP (no modifyTimestamp filter)
--output=(foobar)     Where to write execution messages? (file|stdout|stderr). Default is 'file' (sync_users.log)
-h, --help            Print out this help
-n, --noupdate        Do only inserts, removes and revives (not by default).

 Notes:
   - If you have a large number of users, you may want to raise the memory limits by passing -d memory_limit=256M
   - For debugging & better logging, you are encouraged to use in the command line, as php options
     -d log_errors=1 -d error_reporting=E_ALL -d display_errors=0 -d html_errors=0

";

if ( ! empty($options['help']) ) {
    echo $help;
    return 0;
}



// Ensure errors are well explained
$CFG->debug = DEBUG_NORMAL;

if (!is_enabled_auth('ldapup1')) {
    error_log('[AUTH LDAP] '.get_string('pluginnotenabled', 'auth_ldapup1'));
    die;
}

$ldapauth = get_auth_plugin('ldapup1');

if ( $options['init'] ) {
    $since = FALSE;
} elseif (isset($options['since']) && $options['since']>0 ) {
    $since = $options['since'];
} else {
    $last = $ldapauth->get_last_sync();
    $since = $last['begin'];
}

$ldapauth->sync_users((! $options['noupdate']), $since, $options['output'], $options['verb']);


