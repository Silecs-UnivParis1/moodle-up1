<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage mwsgroups
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* @var $DB moodle_database */

define('MWS_SEARCH_MAXROWS', 100);

require __DIR__ . '/lib_users.php';

/**
 * emulates wsgroups "search" action from Moodle data
 * @param string $token to search in user and cohort tables
 * @param int $usermaxrows
 * @param int $groupmaxrows
 * @param string $filterstudent = 'no' | 'only' | 'both'
 * @param string $filtergroupcat = '' | 'structures' | 'affiliation' | ...
 * @return array('users' => $users, 'groups' => $groups)
 */
function mws_search($token, $usermaxrows, $groupmaxrows, $filterstudent='both', $filtergroupcat='') {
    $search_u = new mws_search_users();
    $search_u->maxrows = $usermaxrows;
    $search_u->filterstudent = $filterstudent;
    $users  = $search_u->search($token);

    if ($filtergroupcat == '') {
        $groups = mws_search_groups($token, $groupmaxrows);
    } else {
        $groups = mws_search_groups_category($token, $filtergroupcat, $groupmaxrows);
    }
    return array('users' => $users, 'groups' => $groups);
}

/**
 * search groups according to filters
 * @param string $token to search in cohort table
 * @param int $maxrows
 * @return array
 */
function mws_search_groups($token, $maxrows) {
    $wherecat = categoryToWhere();

    $res = array();
    foreach (array_keys($wherecat) as $cat) {
        $groups = mws_search_groups_category($token, $cat, $maxrows);
        // echo "<b> $cat -> $where : " . count($groups) . " results</b><br />\n" ; //DEBUG
        $res = array_merge($res, $groups);
    }
    return $res;
}

/**
 * search groups according to filters
 * @global type $DB
 * @param string $token to search in cohort table
 * @param string $category Group/cohort category, see below
 * @param int $maxrows
 * @return array
 */
function mws_search_groups_category($token, $category, $maxrows) {
    global $DB;
    $ptoken = '%' . $DB->sql_like_escape($token) . '%';

    $wherecat = categoryToWhere();
    $cterms = explode('|', $category);
    $cwhere = array();
    foreach ($cterms as $term) {
        if (isset($wherecat[$term])) {
            $cwhere[] = $wherecat[$term];
        }
    }
    if (!$cwhere) {
        return array();
    }
    $sql = "SELECT id, name, idnumber, description, descriptionformat FROM {cohort} WHERE "
        . "( name LIKE ? OR idnumber LIKE ? ) AND (" . join(' OR ', $cwhere) . ')' ;
    // echo $sql . " <br />\n" ; //DEBUG
    $records = $DB->get_records_sql($sql, array($ptoken, $ptoken), 0, $maxrows);
    $groups = array();
    $order = 0;
    foreach ($records as $record) {
        $order++;
        $size = $DB->count_records('cohort_members', array('cohortid' => $record->id));
        $groups[] = array(
            'key' => $record->idnumber,
            'name' => $record->name,
            'description' => format_text($record->description, $record->descriptionformat),
            'category' => groupKeyToCategory($record->idnumber),
            'size' => $size,
            'order' => $order
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
    $sql = "SELECT c.id, c.name, c.idnumber, c.description, c.descriptionformat "
        . "FROM {cohort} c JOIN {cohort_members} cm ON (cm.cohortid = c.id) "
        . "WHERE userid = ?";

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
 * function provided by Pascal Rigaux, cf http://tickets.silecs.info/mantis/view.php?id=1642 (5089)
 * @param string $key group key == cohort idnumber
 * @return string category, among (structures, affiliation, diploma, elp, gpelp, gpetp)
 */
function groupKeyToCategory($key) {
    if (
            preg_match('/^(structures|affiliation|diploma)-/', $key, $matches)
            || preg_match('/^groups-(gpelp|gpetp)\./', $key, $matches)
    ) {
        return $matches[1];
    } else if (startsWith($key, 'groups-mati'))
        return 'elp';
    else if (startsWith($key, 'groups-'))
        return 'other';
    else
        return null;
}

/**
 * sort of reciprocal from groupKeyToCategory
 * return array assoc. array of WHERE conditions in the SQL syntax
 */
function categoryToWhere() {
    $patterns = array(
        'structures' => 'structures-%',
        'affiliation' => 'affiliation-%',
        'diploma' => 'diploma-%',
        'gpelp' => 'groups-gpelp.%',
        //'gpetp' => 'groups-gpetp.%',
        'elp' => 'groups-mati%'
    );
    $res = array();
    $other = '';
    foreach ($patterns as $cat => $pattern) {
        $res[$cat] = "idnumber LIKE '$pattern' ";
        $other = $other . "idnumber NOT LIKE '$pattern' AND ";
    }
    $res['other'] = substr($other, 0, -4); //drop the last AND
    return $res;
}

/**
 * @param string $haystack
 * @param string $needle
 * @return boolean
 */
function startsWith($haystack, $needle)
{
    return strncmp($haystack, $needle, strlen($needle)) === 0;
}
