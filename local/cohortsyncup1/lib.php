<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage cohortsyncup1
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/cohort/lib.php');

function cohorts_cleanall() {
    global $DB;

    echo "Deleting cohort_members...\n";
    $select = "cohortid IN (SELECT id FROM {cohort} WHERE component='local_cohortsyncup1')";
    $DB->delete_records_select('cohort_members', $select);

    echo "Deleting cohorts...\n";
    $DB->delete_records('cohort', array('component' => 'local_cohortsyncup1'));
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
    $wsallgroups = 'http://ticetest.univ-paris1.fr/wsgroups/allGroups';
    $cnt = array('create' => 0, 'modify' => 0, 'pass' => 0, 'noop' => 0);
    $count = 0;
    $wstimeout = 5;

    date_default_timezone_set('UTC');
    $ldaptimelast = date("YmdHis", $timelast) . 'Z';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $wstimeout);
    curl_setopt($ch, CURLOPT_URL, $wsallgroups);
    $data = json_decode(curl_exec($ch));
    add_to_log(0, 'local_cohortsyncup1', 'syncAllGroups:begin', '', "From allGroups since $timelast");

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
                if ($verbose >= 3) echo '+'; // progress bar
            } else { // cohort exists ; must be modified
                list($update, $thiscohort) = update_cohort($cohort);
                if ($update) { // modified => to update
                    cohort_update_cohort($thiscohort);
                    $cnt['modify']++;
                    if ($verbose >= 3) echo 'M'; // progress bar
                } else { // nothing modified since last sync !
                    $cnt['noop']++;
                    if ($verbose >= 3) echo '='; // progress bar
                }
            }
        } // foreach($data)
        $logmsg = "\nAll cohorts : " . $cnt['pass']. " passed, " . $cnt['noop']. " noop, "
                . $cnt['modify']. " modified, " . $cnt['create']. " created.\n";
    } else {
        $logmsg = "\nUnable to fetch data from: " . $wsallgroups . "\n" ;
    }
    echo "\n\n$logmsg\n\n";
    add_to_log(0, 'local_cohortsyncup1', 'syncAllGroups:end', '', "From users. " . $logmsg);
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
    // $wsgroups = 'http://ticetest.univ-paris1.fr/web-service-groups/userGroupsAndRoles';
    $wsgroups = 'http://wsgroups.univ-paris1.fr/userGroupsAndRoles';
    $wstimeout = 5;
    $ref_plugin = 'auth_ldapup1';
    $param = 'uid';   // ex. parameter '?uid=e0g411g01n6'

    $sql = 'SELECT u.id, username FROM {user} u JOIN {user_sync} us ON (u.id = us.userid) '
         . 'WHERE us.ref_plugin = ? AND us.timemodified > ?';
    $users = $DB->get_records_sql_menu($sql, array($ref_plugin, $timelast), 0, $limit);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $wstimeout);
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
// var_dump($idcohort); die();

    $prevpercent = '';
    foreach ($users as $userid => $username) {
        $cntUsers++;
        $localusername = strstr($username, '@', true);
        $requrl = $wsgroups . '?uid=' . $localusername;
        $memberof = array(); //to compute memberships to be removed

        curl_setopt($ch, CURLOPT_URL, $requrl);
        $data = json_decode(curl_exec($ch));
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
    // print_r($cntCohortUsers);
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
                        'name' => (property_exists($wscohort, 'name') ? substr($wscohort->name, 0, 254) : $wscohort->key),
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
    $cohort->name = (property_exists($wscohort, 'name') ? substr($wscohort->name, 0, 254) : $wscohort->key);
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