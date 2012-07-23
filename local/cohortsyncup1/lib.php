<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage cohortsyncup1
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/cohort/lib.php');

function sync_cohorts($timelast=0, $limit=0)
{
    global $CFG, $DB;

    $wsgroups = 'http://ticetest.univ-paris1.fr/web-service-groups/userGroupsAndRoles';
    $wstimeout = 5;
    $ref_plugin = 'auth_ldapup1';
    $param = 'uid';
    // ex. parameter'?uid=e0g411g01n6'

    $sql = 'SELECT u.id, username FROM {user} u JOIN {user_sync} us ON (u.id = us.id) '
         . 'WHERE us.ref_plugin = ? AND us.timemodified > ?';
    $users = $DB->get_records_sql_menu($sql, array($ref_plugin, $timelast), 0, $limit);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $wstimeout);
    $cntUsers = array(); // users added in each cohort
    $cntCrcohorts = 0;
    $cntAddmembers = 0;

    $res = cohort_get_cohorts(1); //context global
    $allcohorts = $res['cohorts'];
    $idcohort = array();

    foreach ($allcohorts as $cohort) {
        $idcohort[$cohort->idnumber] = $cohort->id;
    }
//var_dump($idcohort); die();

    foreach ($users as $userid => $username) {
        $requrl = $wsgroups . '?uid=' . $username;
        curl_setopt($ch, CURLOPT_URL, $requrl);
        $data = json_decode(curl_exec($ch));
        echo ':'; // progress bar

        foreach ($data as $cohort) {
            $ckey = $cohort->key;
            echo '.'; // progress bar
            if ( isset($cntUsers[$ckey]) ) {
                $cntUsers[$ckey]++;
            } else {
                $cntUsers[$ckey] = 1;
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
    } // foreach ($users)
    curl_close($ch);

    echo "\n\nCohorts : " . count($cntUsers) . " encountered. $cntCrcohorts created.";
    echo "\nMembership: $cntAddmembers.\n\n";
    // print_r($cntUsers);
}


function define_cohort($wscohort) {
    $newcohort = array(
                        'contextid' => 1,
                        'name' => (property_exists($wscohort, 'name') ? $wscohort->name : $wscohort->key),
                        'idnumber' => $wscohort->key,
                        'description' => (property_exists($wscohort, 'description') ? $wscohort->description : ''),
                        'descriptionformat' => 0 //** @todo check
                    );
    return ((object) $newcohort);
}