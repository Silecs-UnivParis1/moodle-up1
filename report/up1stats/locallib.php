<?php

/**
 * Lib functions
 *
 * @package    report
 * @subpackage up1stats
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        . "FROM cohort_members cm "
        . "JOIN cohort c ON (c.id = cm.cohortid) "
        . "GROUP BY cohortid  ORDER BY cnt DESC  LIMIT " . $limit;
    $cohorts = $DB->get_records_sql($sql);
    foreach ($cohorts as $cohort) {
        $res[] = array($cohort->cnt, $cohort->idnumber, $cohort->name);
    }
    return $res;
}