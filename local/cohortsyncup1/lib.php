<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage cohortsyncup1
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * In this file, the functions related to cohorts sync used outside of local/cohortsyncup1
 */

/**
 * returns the last sync from the logs
 * @param $synctype = 'syncFromUsers'|'syncAllGroups'
 * @return array('begin' => integer, 'end' => integer) as moodle timestamps
 * @uses exit
 */
function get_cohort_last_sync($synctype) {
    global $DB;

    $allowedSyncs = array('syncFromUsers', 'syncAllGroups');
    if ( ! in_array($synctype, $allowedSyncs)) {
        throw new coding_exception('unknown sync type: ['. $synctype . '].');
    }
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