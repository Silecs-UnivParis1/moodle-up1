<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage mwsgroups
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * emulates wsgroups "search" action from Moodle data
 * @global type $DB
 * @param string $token to search in user and cohort tables
 * @param int $maxrows (default 10)
 * @return array('users' => $users, 'groups' => $groups)
 */
function mws_search($token, $maxrows=10) {
    global $DB;
    $ptoken = '%' . $token . '%';

    $sql = "SELECT username, firstname, lastname FROM {user} WHERE "
        . "username LIKE ? OR firstname LIKE ? OR lastname LIKE ?" ;
    $records = $DB->get_records_sql($sql, array($ptoken, $ptoken, $ptoken), 0, $maxrows);
    $users = array();
    foreach ($records as $record) {
        $users[] = array(
            'uid' => $record->username,
            'displayName' => $record->firstname .' '. $record->lastname
        );
    }

    $sql = "SELECT id, name, idnumber, description FROM {cohort} WHERE "
        . "name LIKE ? OR idnumber LIKE ? OR description LIKE ?" ;
    $records = $DB->get_records_sql($sql, array($ptoken, $ptoken, $ptoken), 0, $maxrows);
    $groups = array();
    foreach ($records as $record) {
        $size = $DB->count_records('cohort_members', array('cohortid' => $record->id));
        $groups[] = array(
            'key' => $record->idnumber,
            'name' => $record->name,
            'description' => strip_tags($record->description),
            'size' => $size
        );
    }
    return array('users' => $users, 'groups' => $groups);
}

// fonction abandonnée, remplacée par mws_userGroupsId_fast
// lenteur dûe sans doute à la 2e jointure sur user.id
function mws_userGroupsId($uid) {
    global $DB;

    $groups = array();
    $sql = "SELECT c.name, c.idnumber, c.description FROM {cohort} c "
        . "JOIN {cohort_members} cm ON (cm.cohortid = c.id) "
        . "JOIN {user} u ON (u.id = cm.userid) "
        . "WHERE username=?";

    $records = $DB->get_records_sql($sql, array($uid));
        foreach ($records as $record) {
        $groups[] = array(
            'key' => $record->idnumber,
            'name' => $record->name,
            'description' => strip_tags($record->description)
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
function mws_userGroupsId_fast($uid) {
    global $DB;

    $user = $DB->get_record('user', array('username' => $uid), 'id', MUST_EXIST);
    $groups = array();
    $sql = "SELECT c.name, c.idnumber, c.description FROM {cohort} c "
        . "JOIN {cohort_members} cm ON (cm.cohortid = c.id) "
        . "WHERE userid=?";

    $records = $DB->get_records_sql($sql, array($user->id));
        foreach ($records as $record) {
        $groups[] = array(
            'key' => $record->idnumber,
            'name' => $record->name,
            'description' => strip_tags($record->description)
        );
    }
    return $groups;
}