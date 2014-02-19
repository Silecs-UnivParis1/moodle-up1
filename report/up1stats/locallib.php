<?php

/**
 * Lib functions
 *
 * @package    report
 * @subpackage up1stats
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/auth/ldapup1/auth.php');
require_once($CFG->dirroot . '/local/cohortsyncup1/lib.php');

$cohortPrefixes = array('structures-', 'diploma-', 'groups-', 'affiliation-');

defined('MOODLE_INTERNAL') || die;

function report_up1stats_users() {
    global $DB;
    $res = array();

    $count = $DB->count_records('user_sync', array('ref_plugin' => 'auth_ldapup1'));
    $res[] = array('Utilisateurs annuaire (user_sync)', $count);

    $rows = $DB->get_records_sql("SELECT auth, COUNT(id) AS cnt FROM {user} GROUP BY auth WITH ROLLUP");
    foreach ($rows as $row) {
        if ($row->auth == '') {
            $auth = "TOTAL auth.";
        } else {
            $auth = 'Auth. ' . $row->auth;
        }
        $res[] = array($auth, $row->cnt);
    }
    return $res;
}

function report_up1stats_users_by_affiliation() {
    global $DB;
    $res = array();

    $fieldid = $DB->get_field('custom_info_field', 'id',
            array('objectname'=>'user', 'shortname'=>'up1edupersonprimaryaffiliation'), MUST_EXIST);
    $sql = "SELECT data, count(id) as cnt FROM {custom_info_data} WHERE fieldid = ? GROUP BY data";
    $rows = $DB->get_records_sql($sql, array($fieldid));
    foreach ($rows as $row) {
        $res[] = array($row->data, $row->cnt);
    }
    return $res;
}


function report_up1stats_cohorts_generic() {
    global $DB;
    $res = array();

    $count = $DB->count_records('cohort', array('component' => 'local_cohortsyncup1'));
    $res[] = array('Cohortes UP1', $count);
    $sql = "SELECT COUNT(*) FROM {cohort_members} cm "
        . "JOIN {cohort} c ON (cm.cohortid = c.id) WHERE c.component = 'local_cohortsyncup1'";
    $count = $DB->count_records_sql($sql);
    $res[] = array('Appartenances UP1', $count);
    return $res;
}

function report_up1stats_cohorts_prefix() {
    global $DB, $cohortPrefixes;
    $res = array();

    $wherediff = '';
    foreach ($cohortPrefixes as $prefix) {
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

function report_up1stats_cohorts_top($limit, $prefix=false) {
    global $DB;
    $res = array();

    $sql = "SELECT cm.cohortid, c.idnumber, c.name, COUNT(cm.id) AS cnt "
        . "FROM {cohort_members} cm "
        . "JOIN {cohort} c ON (c.id = cm.cohortid) "
        . ($prefix ? "WHERE idnumber LIKE '".$prefix."%' " : '')
        . "GROUP BY cohortid  ORDER BY cnt DESC  LIMIT " . $limit;
    $cohorts = $DB->get_records_sql($sql);
    foreach ($cohorts as $cohort) {
        $url = new moodle_url('/cohort/view.php', array('id' => $cohort->cohortid));
        $res[] = array($cohort->cnt,
            html_writer::link($url, $cohort->idnumber),
            $cohort->name);
    }
    return $res;
}

function report_up1stats_cohorts_top_by_prefix($limit) {
    global $cohortPrefixes;
    $res = array();

    foreach ($cohortPrefixes as $prefix) {
        $linkdetails = html_writer::link(
            new moodle_url('/report/up1stats/topcohorts.php', array('number'=>50, 'prefix'=>$prefix)),
            'Détails');
        $res[] = array('', $prefix, $linkdetails);
        $tres = report_up1stats_cohorts_top($limit, $prefix);
        $res = array_merge($res, $tres);
    }
    return $res;
}


function report_up1stats_last_sync() {
    // $ldap = auth_plugin_ldapup1::get_last_sync(); // because non-static method
    $ldap = get_auth_plugin('ldapup1')->get_last_sync();
    $cohorts = get_cohort_last_sync('syncAllGroups');

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
    $logs = $DB->get_records_sql($sql, array('sync%', $plugin));
    $datebegin = '?';

    $logs = array_reverse($logs);
    foreach($logs as $log) {
        if (preg_match('/:begin/', $log->action)) {
            $datebegin = date('Y-m-d H:i:s ', $log->time);
        }
        if (preg_match('/(.*):end/', $log->action, $matches)) {
            $res[] = array($datebegin, date('Y-m-d H:i:s ', $log->time), $matches[1], $log->info);
            $datebegin = '?';
        }
    }
    return $res;
}