<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage cohortsyncup1
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/local/cohortsyncup1/lib.php');

/**
 * deletes all cohorts members, then all cohorts.
 * @global type $DB
 * @param bool $force execution even if it breaks enrolments
 */
function cohorts_cleanall($force) {
    global $DB;

    $sql="SELECT COUNT(courseid) FROM {enrol} e LEFT JOIN {cohort} c ON
        (c.id = e.customint1) WHERE e.enrol='cohort' AND c.component='local_cohortsyncup1'";
    $n = $DB->count_records_sql($sql);
    if ($n > 0 && ! $force) {
        echo "Sorry, there are $n cohort enrolments. Can't do that.\n\n";
        return false;
    }

    echo "Deleting cohort_members...\n";
    $select = "cohortid IN (SELECT id FROM {cohort} WHERE component='local_cohortsyncup1')";
    $DB->delete_records_select('cohort_members', $select);

    echo "Deleting cohorts...\n";
    $DB->delete_records('cohort', array('component' => 'local_cohortsyncup1'));
    return true;
}


/**
 * check if the given cohort is enrolled
 * @global type $DB
 * @param integer $cohortid
 * @return boolean true is cohort is enrolled
 */
function cohort_is_enrolled($cohortid) {
    global $DB;
    return $DB->record_exists('enrol', array('enrol' => 'cohort', 'status' => 0, 'customint1' => $cohortid));
}

/**
 * delete a cohort only if it is not enrolled
 * @global type $DB
 * @param integer $cohortid
 * @return boolean true = deleted ; false = not deleted
 */
function safe_delete_cohort($cohortid) {
    global $DB;
    if (cohort_is_enrolled($cohortid)) {
        return false;
    } else {
        $cohort = $DB->get_record('cohort', array('id' => $cohortid), '*', MUST_EXIST);
        cohort_delete_cohort($cohort);
        return true;
    }
}


/**
 * Get data from webservice - a wrapper around curl_exec
 * @param string $webservice URL of the webservice
 * @return array($curlinfo, $data)
 */
function get_ws_data($webservice) {
    $wstimeout = 5;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $wstimeout);
    curl_setopt($ch, CURLOPT_URL, $webservice);
    $data = json_decode(curl_exec($ch));
    // $data = array( stdClass( $key => '...', $name => '...', $modifyTimestamp => 'ldapTime', $description => '...')

    $curlinfo = curl_getinfo($ch);
    if ($data === null) {
        $dump = var_export($curlinfo, true);
        throw new coding_exception("webservice does NOT work", $dump);
    }
    curl_close($ch);
    return array($curlinfo, $data);
}

/**
 * wrapper around delete_lacking_cohorts() to execute it by CLI one-shot
 * @return boolean
 */
function cli_delete_missing_cohorts($verbose) {
    echo "Connect webservice... ";
    add_to_log(0, 'local_cohortsyncup1', 'delMissingCohorts:begin', '', "");
    list($curlinfo, $wsdata) = get_ws_data(get_config('local_cohortsyncup1', 'ws_allGroups'));
    echo "OK. \n";

    $cnt = delete_missing_cohorts($wsdata, $verbose);
    $logmsg = "parsed cohorts : " . $cnt['delete']. " deleted, " . $cnt['keep']. " kept.";
    echo "\n =>  $logmsg\n\n";
    add_to_log(0, 'local_cohortsyncup1', 'delMissingCohorts:end', '', $logmsg);
    return true;
}

/**
 * delete base cohorts which are not present in the webservice results anymore
 * @param array $wsdata = array( stdClass( $key => '...', $name => '...', $modifyTimestamp => 'ldapTime', $description => '...')
 * @return array (int $deleted, int $kept) : # of deleted and kept cohorts
 */
function delete_missing_cohorts($wsdata, $verbose) {
    global $DB;
    $action = array (true => 'delete', false => 'keep');
    $cnt = array ('delete' => 0, 'keep' => 0);
    $progressbar = array ('delete' => 'D', 'keep' => 'K');

    $dbcohorts = $DB->get_fieldset_select('cohort', 'up1key', "component='local_cohortsyncup1' AND up1key != ''");
    $cohortsid = $DB->get_records_menu('cohort', null, null, 'up1key, id');
    $wscohorts = array_map('project_key', $wsdata);
    $todelete = array_diff($dbcohorts, $wscohorts);
    echo "\nThere are " . count($todelete) . " missing cohorts.\n";

    foreach ($todelete as $up1key) {
        $cohortid = $cohortsid[$up1key];
        $res = safe_delete_cohort($cohortid);

        $act = $action[$res];
        $cnt[$act]++;
        progressBar($verbose, 1, $progressbar[$act]);
        progressBar($verbose, 3, '=' . $up1key . ' ');     
    }
    return $cnt;
}

function project_key($datum) {
    return $datum->key;
}

/**
 * auxiliary function, based on WS  allGroups
 * useful to get empty groups and name/description changes in cohorts
 * @global type $CFG
 * @global type $DB
 * @param type $timelast since when the sync must be executed
 * @param type $limit
 * @param type $verbose
 */
function sync_cohorts_all_groups($timelast=0, $limit=0, $verbose=0)
{
    update_period($verbose);
    // $ws_allGroups = 'http://ticetest.univ-paris1.fr/wsgroups/allGroups';
    $cnt = array('create' => 0, 'modify' => 0, 'pass' => 0, 'noop' => 0);
    $progressbar = array('create' => '+', 'modify' => 'M', 'pass' => 'P', 'noop' => '=');
    $count = 0;
    date_default_timezone_set('UTC');
    $ldaptimelast = date("YmdHis", $timelast) . 'Z';
    add_to_log(0, 'local_cohortsyncup1', 'syncAllGroups:begin', '', "From allGroups since $timelast");

    list($curlinfo, $wsdata) = get_ws_data(get_config('local_cohortsyncup1', 'ws_allGroups'));
    // $data = array( stdClass( $key => '...', $name => '...', $modifyTimestamp => 'ldapTime' $description => '...'
    if ($wsdata) {
        progressBar($verbose, 1, "Parsing " . count($wsdata) . " cohorts ; since $ldaptimelast. \n");

        foreach ($wsdata as $cohort) {
            $count++;
            if ($limit > 0 && $count > $limit) break;

            $action = process_cohort($cohort, $verbose, $ldaptimelast); // real processing here
            $cnt[$action]++;
            progressBar($verbose, 2, $progressbar[$action]);
        } // foreach($data)

        $logmsg = "\nAll cohorts: " . $cnt['pass']. " passed, " . $cnt['noop']. " noop, "
                . $cnt['modify']. " modified, " . $cnt['create']. " created.";

        $cnt = delete_missing_cohorts($wsdata, $verbose);
        $logmsg .= "   MISSING: " . $cnt['delete']. " deleted, " . $cnt['keep']. " kept.";
    } else {
        $logmsg = "\nUnable to fetch data from: " . $ws_allGroups . "\n" ;
    }
    echo "\n\n$logmsg\n\n";
    add_to_log(0, 'local_cohortsyncup1', 'syncAllGroups:end', '', $logmsg);
}


/**
 * process individual cohort during sync
 * @param object $cohort
 * @param integer $verbose
 * @param string $ldaptimelast
 * @return action among ('create', 'modify', 'pass', 'noop')
 */
function process_cohort($cohort, $verbose, $ldaptimelast) {
    global $DB;

    progressBar($verbose, 2, '.');

    if (property_exists($cohort,'modifyTimestamp') && $cohort->modifyTimestamp < $ldaptimelast) {
        return 'pass'; // passed due to modifyTimestamp
    }
    if (! $DB->record_exists('cohort', array('up1key' => $cohort->key))) { // cohort doesn't exist yet
        $newcohort = define_cohort($cohort);
        $newid = cohort_add_cohort($newcohort);
        if ( $newid > 0 ) {
            return 'create';
            }
    } else { // cohort exists ; must be modified
        list($update, $thiscohort) = update_cohort($cohort);
        if ($update) { // modified => to update
            cohort_update_cohort($thiscohort);
            return 'modify';
        } else { // nothing modified since last sync !
            return 'noop';
        }
    }
}


/**
 * Debug / display results of webservice
 * @global type $CFG
 * @global type $DB
 * @param integer $verbose
 */
function display_cohorts_all_groups($verbose=2)
{
    global $CFG, $DB;
    $ws_allGroups = get_config('local_cohortsyncup1', 'ws_allGroups');
    // $ws_allGroups = 'http://ticetest.univ-paris1.fr/wsgroups/allGroups';
    $count = 0;
    list($curlinfo, $data) = get_ws_data($ws_allGroups, 'ws_allGroups');

    if ($data) {
        progressBar($verbose, 1, "\nParsing " . count($data) . " cohorts. \n");
        foreach ($data as $cohort) {
            $count++;
            progressBar($verbose, 2, '.');
            progressBar($verbose, 3, "$count." . $cohort->key . "\n");
        } // foreach($data)
        echo "\nAll cohorts parsed.\n";
    } else {
        echo "\nUnable to fetch data from: " . $ws_allGroups . "\n" ;
    }
    progressBar($verbose, 2, "\n\nCurl diagnostic:\n" . print_r($curlinfo, true));
}


/**
 * original function, linking users to cohorts, based on modified users and ws userGroupsAndRoles
 * @global type $CFG
 * @global type $DB
 * @param type $timelast since when the sync must be executed
 * @param type $limit
 * @param type $verbose
 */
function sync_cohorts_from_users($timelast=0, $limit=0, $verbose=0)
{
    global $CFG, $DB;

    add_to_log(0, 'local_cohortsyncup1', 'syncFromUsers:begin', '', "since $timelast");
    $ws_userGroupsAndRoles = get_config('local_cohortsyncup1', 'ws_userGroupsAndRoles');
    // $ws_userGroupsAndRoles = 'http://ticetest.univ-paris1.fr/web-service-groups/userGroupsAndRoles';
    $ref_plugin = 'auth_ldapup1';
    $param = 'uid';   // ex. parameter '?uid=e0g411g01n6'

    $sql = 'SELECT u.id, username FROM {user} u JOIN {user_sync} us ON (u.id = us.userid) '
         . 'WHERE us.ref_plugin = ? AND us.timemodified > ?';
    $users = $DB->get_records_sql_menu($sql, array($ref_plugin, $timelast), 0, $limit);

    $cntCohortUsers = array(); // users added in each cohort
    $cntCrcohorts = 0;
    $cntAddmembers = 0;
    $cntRemovemembers = 0;
    $cntUsers = 0;
    $totalUsers = count($users);
    $idcohort = $DB->get_records_select_menu('cohort', "up1key != ''", null, '', 'up1key, id');

    $prevpercent = '';
    foreach ($users as $userid => $username) {
        $cntUsers++;
        $localusername = strstr($username, '@', true);
        $requrl = $ws_userGroupsAndRoles . '?uid=' . $localusername;
        $memberof = array(); //to compute memberships to be removed

        list($curlinfo, $wsdata) = get_ws_data($requrl);
        progressBar($verbose, 2, ':'); // user
        $percent = sprintf("%3.0f", ($cntUsers / $totalUsers * 100)) ;
        if ( $verbose>=1 && $percent != $prevpercent ) {
            echo "\n $percent % ";
            $prevpercent = $percent;
        }
        if ( is_null($wsdata) ) continue;

        foreach ($wsdata as $cohort) {
            $ckey = $cohort->key;
            $memberof[] = $ckey;
            progressBar($verbose, 3, '.'); //membership
            if ( isset($cntCohortUsers[$ckey]) ) {
                $cntCohortUsers[$ckey]++;
            } else {
                $cntCohortUsers[$ckey] = 1;
                if (! isset($idcohort[$ckey])) { // cohort doesn't exist yet
                    $newcohort = define_cohort($cohort);
                    $newid = cohort_add_cohort($newcohort);
                    if ( $newid > 0 ) {
                        $cntCrcohorts++;
                        $idcohort[$ckey] = $newid;
                    }
                }
            }
            if ( ! $DB->record_exists('cohort_members',
                    array('cohortid' => $idcohort[$ckey], 'userid' => $userid) )) {
                cohort_add_member($idcohort[$ckey], $userid);
                $cntAddmembers++;
            }
        } // foreach($data)

        $cntRemovemembers += remove_memberships($userid, $memberof);
    } // foreach ($users)

    $logmsg = "$totalUsers parsed users.  "
        . "Cohorts : " . count($cntCohortUsers) . " encountered. $cntCrcohorts created.  "
        . "Membership: +$cntAddmembers  -$cntRemovemembers.";
    echo "\n\n$logmsg\n\n";
    add_to_log(0, 'local_cohortsyncup1', 'syncFromUsers:end', '', $logmsg);
}


/**
 * compute memberships to be removed from database, and then actually do removing
 * @param integer $userid
 * @param array $memberof array(int $cohort->up1key ... )
 */
function remove_memberships($userid, $memberof) {
    global $DB;
    $cnt = 0;

    $sql = "SELECT cm.cohortid, c.up1key FROM {cohort_members} cm "
        . "INNER JOIN {cohort} c ON (c.id = cm.cohortid) WHERE (cm.userid=? AND c.component='local_cohortsyncup1')";
    $res = $DB->get_records_sql_menu($sql, array($userid));
    foreach ($res as $cohortid => $up1key) {
        if ( ! in_array($up1key, $memberof) ) {
            cohort_remove_member($cohortid, $userid);
            $cnt++;
        }
    }
    return $cnt;
}

/**
 * returns a "newcohort" object from the json-formatted webservice cohort
 * reads the cohort_period parameter and uses it to set idnumber, period, name
 * @param type $wscohort
 * @return (object) $newcohort
 */
function define_cohort($wscohort) {
    $groupYearly = groupYearlyPredicate();
    $curyear = get_config('local_cohortsyncup1', 'cohort_period');
    $groupcategory = groupKeyToCategory($wscohort->key);

    $newcohort = array(
        'contextid' => 1,
        'name' => (property_exists($wscohort, 'name') ? truncate_str($wscohort->name, 250) : $wscohort->key),
        'idnumber' => $wscohort->key,
        'description' => (property_exists($wscohort, 'description') ? $wscohort->description : $wscohort->key),
        'descriptionformat' => 0, //** @todo check
        'component' => 'local_cohortsyncup1',
        'up1category' => $groupcategory,
        'up1key' => $wscohort->key,
    );

    if ( $groupYearly[$groupcategory] ) {
            $newcohort['up1period'] = $curyear;
            $newcohort['idnumber'] = $newcohort['idnumber'] . '-' . $curyear;
            $newcohort['name'] = '['. $curyear . '] ' . $newcohort['name'];
        } else {
        }

    return ((object) $newcohort);
}

/**
 * returns an "updated cohort" object from the json-formatted webservice cohort
 * only 'name' and 'description' fields can be updated
 * @param type $wscohort
 * @return (object) $cohort
 */
function update_cohort($wscohort) {
    global $DB;
    $cohort = $DB->get_record('cohort', array('up1key' => $wscohort->key));
    $groupYearly = groupYearlyPredicate();
    $curyear = get_config('local_cohortsyncup1', 'cohort_period');
    $groupcategory = groupKeyToCategory($wscohort->key);

    $oldcohort = clone $cohort;
    $prename = (property_exists($wscohort, 'name') ? truncate_str($wscohort->name, 250) : $wscohort->key);
    $cohort->name = ( $groupYearly[$groupcategory] ? '['. $curyear . '] ' : '') . $prename;
    $cohort->description = (property_exists($wscohort, 'description') ? $wscohort->description : $wscohort->key);
    $toUpdate = ! (bool) ($cohort->name == $oldcohort->name && $cohort->description == $oldcohort->description);

    return (array($toUpdate, (object) $cohort));
}


/**
 * Cleanly truncates a string on a word boundary, if possible
 * @param string $str string to truncate
 * @param int $bytes number of bytes to keep (warning: bytes, not chars)
 * @param bool $end : true = keep the end ; false = keep the beginning
 * @return type
 */
function truncate_str($str, $bytes=254, $end=true, $complete='…') {
    if (strlen($str) <= $bytes) {
        return $str;
    }
    if ($end) {
        $strend = substr($str, -$bytes);
        $pos = strpos($strend, " ");
        $new = substr($strend, $pos);
    } else {
        $pos = strrpos(substr($str, 0, $bytes), " ");
        if ( ! $pos ) { // no space found => hard truncate
            $new = substr($str, 0, $bytes);
        } else { // clean truncate
            $new = substr($str, 0, $pos);
        }
    }
    return $new . $complete;
}

function fix_user_sync($dryrun=false) {
    global $DB;
    $sql = "SELECT u.id, u.auth, u.username, u.timemodified "
         . "FROM {user} u LEFT JOIN {user_sync} us ON (u.id=us.userid) "
         . "WHERE us.userid IS NULL AND u.auth='shibboleth'";
    $missingusers = $DB->get_records_sql($sql);
    echo count($missingusers) . " missing users (exisiting in table user but not in table user_sync).\n";
    //print_r($missingusers);
    if ($dryrun) {
        return true;
    }

    $diag = true;
    foreach ($missingusers as $missing) {
        $syncuser = new stdClass();
        $syncuser->ref_plugin = 'auth_ldapup1';
        $syncuser->ref_param = '';
        $syncuser->timemodified = time();
        $syncuser->userid = $missing->id;
        
        $id = $DB->insert_record('user_sync', $syncuser, true, false);
        if ($id) {
            echo "    " . $id . " " . $missing->username . "\n";
        }
        else {
            echo "ERR " . $missing->username . "not inserted.\n";
            $diag = false;
        }
    }
    return $diag;
}


function update_period($verb=1) {
    global $DB;

    $dbyear = $DB->get_field_sql(" SELECT MAX(up1period) FROM cohort", null, MUST_EXIST);
    $curyear = get_config('local_cohortsyncup1', 'cohort_period');
    if ($dbyear == $curyear) {
        progressBar($verb, 2, "Period = $dbyear ; does not change.\n\n");
        return 0;
    } else {
        progressBar($verb, 2, "/!\ Period change, from $dbyear to $curyear.\n\n");
        $sql = "SELECT COUNT(id) FROM cohort WHERE up1period=? AND up1key != '' ";
        $cnterased = $DB->get_field_sql($sql, array($dbyear), MUST_EXIST);
        $DB->execute("UPDATE {cohort} SET up1key='' WHERE up1period=? AND up1key != ''", array($dbyear));
        return $cnterased;
    }
}

/**
 * performs various checkings on the cohorts consistency and display results (intended for CLI)
 */
function check_database() {
    $unique_key = check_up1key_unicity();
    if ($unique_key === true) {
        echo "Key cohort.up1key unicity OK.\n\n";
    } else {
        echo "/!\ Error: key cohort.up1key not unique for following cohorts: (up1key => count)\n";
        print_r($unique_key);
        echo "\n";
    }
}

/**
 * check if the field up1key is well unique (or empty)
 * @return mixed : true if unique, or array of up1keys in error with their count
 */
function check_up1key_unicity() {
    global $DB;
    $sql = "SELECT up1key, COUNT(id) AS cnt FROM {cohort} WHERE up1key != '' GROUP BY up1key HAVING cnt > 1";
    $res = $DB->get_records_sql_menu($sql);
    if (count($res) == 0) {
        return true;
    } else {
        return $res;
    }
}

/**
 * show various statistics on cohort records
 */
function cohort_statistics() {
    $fields = array('up1period', 'up1category');
    echo "\nCurrent year = " . get_config('local_cohortsyncup1', 'cohort_period') . "\n\n";
    foreach ($fields as $field) {
        echo "Statistics for $field : \n";
        print_r(cohort_statistics_by($field));
    }
}

/**
 * performs mysql statistics based on GROUP BY ($field)
 * @param string $field a column from table cohort
 * @return array (field-value => count)
 */
function cohort_statistics_by($field) {
    global $DB;
    $sql = "SELECT " . $field .", COUNT(id) AS cnt FROM {cohort} GROUP BY " . $field;
    $res = $DB->get_records_sql_menu($sql);
    return $res;
}

/**
 * progress bar display
 * @param int $verb verbosity
 * @param int $verbmin minimal verbosity
 * @param string $text to display
 */
function progressBar($verb, $verbmin, $text) {
    if ($verb >= $verbmin) {
        echo $text;
    }
}
