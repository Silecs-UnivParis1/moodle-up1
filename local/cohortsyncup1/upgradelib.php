<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage cohortsyncup1
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/local/cohortsyncup1/locallib.php');
require_once($CFG->dirroot . '/local/mwsgroups/lib.php');

function upgrade_cohort_set_period($verb) {
    global $DB;

    $sql = "SELECT id, name, idnumber, component FROM {cohort} " 
         . " WHERE component LIKE 'local_cohortsyncup1%' AND up1category='' ";
    $records = $DB->get_records_sql($sql);
    $groupYearly = groupYearlyPredicate();
    $count = array ('old' => 0, 'current' => 0, 'none' => 0);

    foreach ($records as $cohort) {
        progressBar($verb, 1, '.');
        $groupcategory = groupKeyToCategory($cohort->idnumber);

        if ( preg_match('/-(201[0-9])$/', $cohort->idnumber, $matches) ) {
            $year = $matches[1];
            $cohort->up1period = $year;
            $count['old']++;
        } elseif ( $groupYearly[$groupcategory] ) {
            $year = get_config('local_cohortsyncup1', 'cohort_period');
            $cohort->up1period = $year;
            $count['current']++;
        } else {
            $count['none']++;
        }
        $cohort->up1category = $groupcategory;
        $DB->update_record('cohort', $cohort, true);
    }

    echo "Comptages : ";
    print_r($count);
}
