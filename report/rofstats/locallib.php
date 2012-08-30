<?php

/**
 * Lib functions
 *
 * @package    report
 * @subpackage rofstats
 * @copyright  2012 Silecs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function report_rofstats_generic() {
    global $DB;
    $res = array();

    $count = $DB->count_records('rof_component');
    $res[] = array('Components', $count);
    $res[] = array('', ''); //** @todo meilleur séparateur ?
    $count = $DB->count_records('rof_program', array('level' => 1));
    $res[] = array('Programs', $count);
    $count = $DB->count_records('rof_program', array('level' => 2));
    $res[] = array('SubPrograms', $count);
    $res[] = array('', ''); //** @todo meilleur séparateur ?
    $count = $DB->count_records('rof_person');
    $res[] = array('Persons', $count);
    $res[] = array('', ''); //** @todo meilleur séparateur ?

    $count = $DB->count_records('rof_course');
    $res[] = array('Courses', $count);
    $levelmax = $DB->get_record_sql('SELECT MAX(level) as levelmax FROM {rof_course} rc')->levelmax;
    for ($level = 1; $level <= 1+$levelmax ; $level++) {
       $count = $DB->count_records('rof_course', array('level' => $level));
       $res[] = array('Courses level=' . $level, $count);
    }
    return $res;
}

function report_rofstats_components() {
    global $DB;
    $res = array();

    $progsmax = $DB->get_record_sql('SELECT MAX(subnb) as progsmax FROM {rof_component}')->progsmax;
    $progsmin = $DB->get_record_sql('SELECT MIN(subnb) as progsmin FROM {rof_component} WHERE subnb>0')->progsmin;

    $components = $DB->get_records('rof_component', array('subnb' => $progsmax));
    foreach ($components as $component) {
        $res[] = array('Max', $progsmax, $component->number, $component->name);
    }
    $components = $DB->get_records('rof_component', array('subnb' => $progsmin));
    foreach ($components as $component) {
        $res[] = array('Min > 0', $progsmin, $component->number, $component->name);
    }
    $components = $DB->get_records('rof_component', array('subnb' => 0));
    foreach ($components as $component) {
        $res[] = array('None', '0', $component->number, $component->name);
    }
    return $res;
}


function report_rofstats_persons_not_empty() {
    global $DB;
    $res = array();

    $count = $DB->count_records_sql("SELECT COUNT(id) FROM {rof_program} WHERE level=1 AND refperson != ''");
    $res[] = array('Programs', $count);
    $count = $DB->count_records_sql("SELECT COUNT(id) FROM {rof_program} WHERE level=2 AND refperson != ''");
    $res[] = array('SubPrograms', $count);
    $res[] = array('', ''); //** @todo meilleur séparateur ?

    $count = $DB->count_records_sql("SELECT COUNT(id) FROM {rof_course} WHERE refperson != ''");
       $res[] = array('Courses', $count);
    $levelmax = $DB->get_record_sql('SELECT MAX(level) as levelmax FROM {rof_course} rc')->levelmax;
    for ($level = 1; $level <= $levelmax ; $level++) {
       $count = $DB->count_records_sql(
               "SELECT COUNT(id) FROM {rof_course} WHERE level=? AND refperson != ''", array($level));
       $res[] = array('Courses level=' . $level, $count);
    }
    return $res;
}