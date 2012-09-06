<?php

/**
 * Lib functions
 *
 * @package    report
 * @subpackage up1stats
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/auth/ldapup1/auth.php');
require_once($CFG->dirroot . '/local/cohortsyncup1/lib.php');


defined('MOODLE_INTERNAL') || die;

function report_up1stats_generic() {
    global $DB;
    $res = array();

    $count = $DB->count_records('user_sync', array('ref_plugin' => 'auth_ldapup1'));
    $res[] = array('Utilisateurs annuaire', $count);
    $count = $DB->count_records('user', array('auth' => 'shibboleth'));
    $res[] = array('Utilisateurs Shibboleth', $count);

    $count = $DB->count_records('cohort', array('component' => 'local_cohortsyncup1'));
    $res[] = array('Cohortes UP1', $count);
    $sql = "SELECT COUNT(*) FROM {cohort_members} cm "
        . "JOIN {cohort} c ON (cm.cohortid = c.id) WHERE c.component = 'local_cohortsyncup1'";
    $count = $DB->count_records_sql($sql);
    $res[] = array('Appartenances UP1', $count);
    return $res;
}


function report_up1stats_cohorts() {
    global $DB;
    $res = array();

    $cohortsprefix = array('structures-', 'diploma-', 'groups-', 'affiliation-');
    $wherediff = '';
    foreach ($cohortsprefix as $prefix) {
        $sql = "SELECT COUNT(*) FROM {cohort} c WHERE idnumber LIKE '".$prefix."%' ";
        $count = $DB->count_records_sql($sql);
        $res[] = array($prefix, $count);
        $wherediff .= " AND idnumber NOT LIKE '" . $prefix."%' ";
    }
    $sql = "SELECT COUNT(*) FROM {cohort} c WHERE TRUE" . $wherediff;
    $count = $DB->count_records_sql($sql);
    $res[] = array('Autres', $count);
    return $res;
}

function report_up1stats_cohorts_top($limit) {
    global $DB;
    $res = array();

    $sql = "SELECT cm.cohortid, c.idnumber, c.name, COUNT(cm.id) AS cnt "
        . "FROM {cohort_members} cm "
        . "JOIN {cohort} c ON (c.id = cm.cohortid) "
        . "GROUP BY cohortid  ORDER BY cnt DESC  LIMIT " . $limit;
    $cohorts = $DB->get_records_sql($sql);
    foreach ($cohorts as $cohort) {
        $res[] = array($cohort->cnt, $cohort->idnumber, $cohort->name);
    }
    return $res;
}

function report_up1stats_last_sync() {
    // $ldap = auth_plugin_ldapup1::get_last_sync(); // because non-static method
    $ldap = get_auth_plugin('ldapup1')->get_last_sync();
    $cohorts = get_cohort_last_sync();

    $res = array(
        array('LDAP', $ldap['begin'], $ldap['end']),
        array('Cohorts',
            date('Y-m-d H:i:s ', $cohorts['begin']),
            date('Y-m-d H:i:s ', $cohorts['end'])),
    );
    return $res;
}

function report_up1stats_syncs($plugin, $howmany) {
    global $DB;

    $res = array();
    $sql = "SELECT * FROM log WHERE action LIKE ? AND module LIKE ? ORDER BY id DESC LIMIT ". (2*$howmany);
    $logs = $DB->get_records_sql($sql, array('sync:%', $plugin));

    $logs = array_reverse($logs);
    foreach($logs as $log) {
        if ($log->action == 'sync:begin') {
            $datebegin = date('Y-m-d H:i:s ', $log->time);
        }
        if ($log->action == 'sync:end') {
            $res[] = array($datebegin, date('Y-m-d H:i:s ', $log->time), $log->module, $log->info);
            $datebegin = '?';
        }
    }
    return $res;
}