<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage cohortsyncup1
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/cohort/lib.php');

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
    //$timestart = microtime(true);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $wstimeout);
    curl_setopt($ch, CURLOPT_URL, $webservice);
    //$timeend = microtime(true);
    $data = json_decode(curl_exec($ch));
    $curlinfo = curl_getinfo($ch);
    return array($curlinfo, $data);
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
    global $CFG, $DB;
    // $ws_allGroups = 'http://ticetest.univ-paris1.fr/wsgroups/allGroups';
    $cnt = array('create' => 0, 'modify' => 0, 'pass' => 0, 'noop' => 0);
    $count = 0;
    date_default_timezone_set('UTC');
    $ldaptimelast = date("YmdHis", $timelast) . 'Z';
    add_to_log(0, 'local_cohortsyncup1', 'syncAllGroups:begin', '', "From allGroups since $timelast");

    list($curlinfo, $data) = get_ws_data(get_config('local_cohortsyncup1', 'ws_allGroups'));
    // $data = array( stdClass( $key => '...', $name => '...', $modifyTimestamp => 'ldapTime' $description => '...'

    if ($data) {
        if ($verbose >= 2) echo "Parsing " . count($data) . " cohorts ; since $ldaptimelast. \n";

        foreach ($data as $cohort) {
            $count++;
            if ($limit > 0 && $count > $limit) break;
            if ($verbose >= 2) echo '.'; // progress bar
            if (property_exists($cohort,'modifyTimestamp') && $cohort->modifyTimestamp < $ldaptimelast) {
                if ($verbose >= 3) echo 'P'; // progress bar ; passed due to modifyTimestamp
                $cnt['pass']++;
                continue;
            }

            if (! $DB->record_exists('cohort', array('idnumber' => $cohort->key))) { // cohort doesn't exist yet
                $newcohort = define_cohort($cohort);
                $newid = cohort_add_cohort($newcohort);
                if ( $newid > 0 ) {
                    $cnt['create']++;
                }
                if ($verbose >= 3) echo '+'; // cohort created -> progress bar
            } else { // cohort exists ; must be modified
                list($update, $thiscohort) = update_cohort($cohort);
                if ($update) { // modified => to update
                    cohort_update_cohort($thiscohort);
                    $cnt['modify']++;
                    if ($verbose >= 3) echo 'M'; // cohort modified -> progress bar
                } else { // nothing modified since last sync !
                    $cnt['noop']++;
                    if ($verbose >= 3) echo '='; // cohort not modified (noop) -> progress bar
                }
            }
        } // foreach($data)
        $logmsg = "\nAll cohorts : " . $cnt['pass']. " passed, " . $cnt['noop']. " noop, "
                . $cnt['modify']. " modified, " . $cnt['create']. " created.\n";
    } else {
        $logmsg = "\nUnable to fetch data from: " . $ws_allGroups . "\n" ;
    }
    echo "\n\n$logmsg\n\n";
    add_to_log(0, 'local_cohortsyncup1', 'syncAllGroups:end', '', "From users. " . $logmsg);
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
    // $ws_allGroups = 'http://ticetest.univ-paris1.fr/wsgroups/allGroups';
    $count = 0;
    list($curlinfo, $data) = get_ws_data(get_config('local_cohortsyncup1', 'ws_allGroups'));
    // $data = array( stdClass( $key => '...', $name => '...', $modifyTimestamp => 'ldapTime' $description => '...'

    if ($data) {
        if ($verbose >= 1) {
            echo "\nParsing " . count($data) . " cohorts. \n";            
        }        
        foreach ($data as $cohort) {
            $count++;
            if ($verbose >= 2) echo '.'; // progress bar
            if ($verbose >= 3) echo $cohort->key . "\n";
        } // foreach($data)
        echo "\nAll cohorts parsed.\n";
    } else {
        echo "\nUnable to fetch data from: " . $ws_allGroups . "\n" ;
    }
    if ($verbose >= 2) {
        echo "\n\nCurl diagnostic:\n";
        print_r($curlinfo);
    }
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

    add_to_log(0, 'local_cohortsyncup1', 'sync:begin', '', "From users since $timelast");
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

    $res = cohort_get_cohorts(1, 0, 100000); //1 = context global, page, perpage
    $allcohorts = $res['cohorts'];
    $idcohort = array();

    foreach ($allcohorts as $cohort) {
        $idcohort[$cohort->idnumber] = $cohort->id;
    }

    $prevpercent = '';
    foreach ($users as $userid => $username) {
        $cntUsers++;
        $localusername = strstr($username, '@', true);
        $requrl = $ws_userGroupsAndRoles . '?uid=' . $localusername;
        $memberof = array(); //to compute memberships to be removed

        list($curlinfo, $data) = get_ws_data($requrl);
        if ($verbose >= 2) echo ':'; // progress bar user
        $percent = sprintf("%3.0f", ($cntUsers / $totalUsers * 100)) ;
        if ( $verbose>=1 && $percent != $prevpercent ) {
            echo "\n $percent % ";
            $prevpercent = $percent;
        }
        if ( empty($data) ) continue;

        foreach ($data as $cohort) {
            $ckey = $cohort->key;
            $memberof[] = $ckey;
            if ($verbose >= 3) echo '.'; // progress bar membership
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

        if ($timelast > 0) {
            $cntRemovemembers += remove_memberships($userid, $memberof);
        }
    } // foreach ($users)
    curl_close($ch);

    $logmsg = "$totalUsers parsed users.  "
        . "Cohorts : " . count($cntCohortUsers) . " encountered. $cntCrcohorts created.  "
        . "Membership: +$cntAddmembers  -$cntRemovemembers.";
    echo "\n\n$logmsg\n\n";
    add_to_log(0, 'local_cohortsyncup1', 'sync:end', '', "From users. " . $logmsg);
}


/**
 * compute memberships to be removed from database, and then actually do removing
 * @param type $userid
 * @param type $memberof
 */
function remove_memberships($userid, $memberof) {
    global $DB;
    $cnt = 0;

    $sql = "SELECT cm.cohortid, c.idnumber FROM {cohort_members} cm "
        . "INNER JOIN {cohort} c ON (c.id = cm.cohortid) WHERE (cm.userid=? AND c.component='local_cohortsyncup1')";
    $res = $DB->get_records_sql_menu($sql, array($userid));
    foreach ($res as $cohortid => $idnumber) {
        if ( ! in_array($idnumber, $memberof) ) {
            cohort_remove_member($cohortid, $userid);
            $cnt++;
        }
    }
    return $cnt;
}

/**
 * returns a "newcohort" object from the json-formatted webservice cohort
 * @param type $wscohort
 * @return (object) $newcohort
 */
function define_cohort($wscohort) {
    $newcohort = array(
                        'contextid' => 1,
                        'name' => (property_exists($wscohort, 'name') ? truncate_str($wscohort->name, 250) : $wscohort->key),
                        'idnumber' => $wscohort->key,
                        'description' => (property_exists($wscohort, 'description') ? $wscohort->description : $wscohort->key),
                        'descriptionformat' => 0, //** @todo check
                        'component' => 'local_cohortsyncup1'
                    );
    return ((object) $newcohort);
}

/**
 * returns an "updated cohort" object from the json-formatted webservice cohort
 * @param type $wscohort
 * @return (object) $cohort
 */
function update_cohort($wscohort) {
    global $DB;
    $cohort = $DB->get_record('cohort', array('idnumber' => $wscohort->key));

    $oldcohort = clone $cohort;
    $cohort->name = (property_exists($wscohort, 'name') ? truncate_str($wscohort->name, 250) : $wscohort->key);
    $cohort->description = (property_exists($wscohort, 'description') ? $wscohort->description : $wscohort->key);
    $toUpdate = ! (bool) ($cohort->name == $oldcohort->name && $cohort->description == $oldcohort->description);

    return (array($toUpdate, (object) $cohort));
}


/**
 * returns the last sync from the logs
 * @param $synctype = 'sync'|'syncAllGroups'
 * @return array('begin' => integer, 'end' => integer) as moodle timestamps
 */
function get_cohort_last_sync($synctype = 'sync') {
    global $DB;

    $sql = "SELECT MAX(time) FROM {log} WHERE module=? AND action=?";
    $begin = $DB->get_field_sql($sql, array('local_cohortsyncup1', $synctype.':begin'));
    if ($begin === null) $begin=0;
    $end = $DB->get_field_sql($sql, array('local_cohortsyncup1', $synctype.':end'));
    if ($end === null) $end=0;
        $res = array(
            'begin' => $begin,
            'end' => $end,
        );
        return $res;
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

