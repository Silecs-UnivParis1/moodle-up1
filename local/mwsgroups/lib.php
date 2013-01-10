<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage mwsgroups
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * emulates wsgroups "search" action from Moodle data
 * @global type $DB
 * @param string $token to search in user and cohort tables
 * @param int $usermaxrows
 * @param int $groupmaxrows
 * @param string $filterstudent = 'no' | 'only' | 'both'
 * @return array('users' => $users, 'groups' => $groups)
 */
function mws_search($token, $usermaxrows, $groupmaxrows, $filterstudent='both') {

    $users  = mws_search_users($token, $usermaxrows, $filterstudent);
    $groups = mws_search_groups($token, $groupmaxrows);
    return array('users' => $users, 'groups' => $groups);
}


/**
 * search users according to filters
 * @global type $DB
 * @param string $token to search in user table
 * @param int $maxrows (default 10)
 * @param string $filterstudent = 'no' | 'only' | 'both'
 * @return array
 */
function mws_search_users($token, $maxrows, $filterstudent) {
    global $DB;
    $ptoken = $token . '%';

    $sql = "SELECT id, username, firstname, lastname FROM {user} WHERE "
        . "( username = ? OR firstname LIKE ? OR lastname LIKE ? ) " ;
    if ($filterstudent == 'no') {
        $sql .= " AND idnumber = '' ";
    }
    if ($filterstudent == 'only') {
        $sql .= " AND idnumber != '' ";
    }
    $sql .= "ORDER BY lastname ASC, firstname ASC";
    $records = $DB->get_records_sql($sql, array($token, $ptoken, $ptoken), 0, $maxrows);
    $users = array();
    foreach ($records as $record) {
        $sql = "SELECT c.idnumber, c.name FROM {cohort} c JOIN {cohort_members} cm ON (c.id = cm.cohortid) "
             . "WHERE c.idnumber LIKE 'structures-%' AND cm.userid = ? ";
        $res = $DB->get_records_sql_menu($sql, array($record->id));
        $users[] = array(
            'uid' => $record->username,
            'displayName' => $record->firstname .' '. $record->lastname,
            'supannEntiteAffectation' => array_unique(array_map('groupNameToShortname', array_values($res))),
        );
    }
    return $users;
}

/**
 * search groups according to filters
 * @global type $DB
 * @param string $token to search in cohort table
 * @param int $maxrows
 * @return array
 */
function mws_search_groups($token, $maxrows) {
    global $DB;
    $ptoken = $token . '%';

    $sql = "SELECT id, name, idnumber, description, descriptionformat FROM {cohort} WHERE "
        . "name LIKE ? OR idnumber LIKE ?" ;
    $records = $DB->get_records_sql($sql, array($ptoken, $ptoken), 0, $maxrows);
    $groups = array();
    foreach ($records as $record) {
        $size = $DB->count_records('cohort_members', array('cohortid' => $record->id));
        $groups[] = array(
            'key' => $record->idnumber,
            'name' => $record->name,
            'description' => format_text($record->description, $record->descriptionformat),
            'category' => groupKeyToCategory($record->idnumber),
            'size' => $size
        );
    }
    return $groups;
}


/**
 * emulates wsgroups "userGroupsId" action from Moodle data
 * @global type $DB
 * @param string $uid (sens ldap) Moodle username
 * @return $groups as wsgroups structure
 */
function mws_userGroupsId($uid) {
    global $DB;

    $user = $DB->get_record('user', array('username' => $uid), 'id', MUST_EXIST);
    // on évite une 2e jointure dans la requête suivante, qui ralentit considérablement
    $groups = array();
    $sql = "SELECT c.id, c.name, c.idnumber, c.description, c.descriptionformat FROM {cohort} c "
        . "JOIN {cohort_members} cm ON (cm.cohortid = c.id) "
        . "WHERE userid=?";

    $records = $DB->get_records_sql($sql, array($user->id));
    foreach ($records as $record) {
        $size = $DB->count_records('cohort_members', array('cohortid' => $record->id));
        $groups[] = array(
            'key' => $record->idnumber,
            'name' => $record->name,
            'description' => format_text($record->description, $record->descriptionformat),
            'category' => groupKeyToCategory($record->idnumber),
            'size' => $size
        );
     }
    return $groups;
}



/**
 * function provided by Pascal Rigaux, cf http://tickets.silecs.info/mantis/view.php?id=1642 (5082)
 * @param string $name group/cohort name for a "structures-.*" group/cohort
 * @return string short name, ex. 'UFR 05'
 */
function groupNameToShortname($name) {
    if (preg_match('/(.*?)\s*:/', $name, $matches))
      return $matches[1];
    else
      return $name;
}
/**
 * function provided by Pascal Rigaux, cf http://tickets.silecs.info/mantis/view.php?id=1642 (5089)
 * @param string $key group key == cohort idnumber
 * @return string category, among (structures, affiliation, diploma, elp, gpelp, gpetp)
 */
function groupKeyToCategory($key) {
    if ( preg_match('/^(structures|affiliation|diploma)-/', $key, $matches) ||
         preg_match('/^groups-(gpelp|gpetp)\./', $key, $matches))
    return $matches[1];
    else if (startsWith($key, 'groups-mati'))
        return 'elp';
    else if (startsWith($key, 'groups-'))
        return 'local';
    else
        return null;
}

function startsWith($haystack, $needle)
{
    return strncmp($haystack, $needle, strlen($needle)) === 0;
}
