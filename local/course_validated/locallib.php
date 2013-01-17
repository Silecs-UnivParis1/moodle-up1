<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage course_validated
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 *
 * @global type $DB
 * @param int $approbateurid ; 0 if none
 * @param int $validated = 0, 1, 2 ; 0=not yet validated ; 1=already validated ; 2=both
 */
function get_id_courses_to_validate($approbateurid, $validated) {
    global $DB;

    $avaliderId = $DB->get_field('custom_info_field', 'id', array('objectname' => 'course', 'shortname' => 'up1avalider'));
    $datevalidId = $DB->get_field('custom_info_field', 'id', array('objectname' => 'course', 'shortname' => 'up1datevalid'));
    $approbateurpropidId = $DB->get_field('custom_info_field', 'id',
        array('objectname' => 'course', 'shortname' => 'up1approbateurpropid'));

    if ( ! ($avaliderId && $datevalidId && $approbateurpropidId) ) {
        throw new coding_exception('Erreur ! manque up1avalider ou up1datevalid ou up1approbateurid');
        // die ('Erreur ! manque up1avalider ou up1datevalid ou up1approbateurid');
        return;
    }
    $sql = "SELECT DISTINCT cd1.objectid FROM custom_info_data cd1 "
         . "JOIN custom_info_data cd2 ON (cd1.objectid=cd2.objectid) " ;
    if ($approbateurid) {
        $sql .= "JOIN custom_info_data cdq ON (cd1.objectid=cdq.objectid) " ;
    }
    $sql .= "WHERE cd1.fieldid=$avaliderId AND cd1.data=1 AND cd2.fieldid=$datevalidId ";
    if ($approbateurid) {
        $sql .= "AND cdq.fieldid=$approbateurpropidId AND cdq.data=$approbateurid " ;
    }
    if ($validated == 0) {
        $sql .= "AND cd2.data=0";
    }
    if ($validated == 1) {
        $sql .= "AND cd2.data>0";
    }
    //echo "\n\n  $sql";

    $listeId='';
    $tabIdCourse = $DB->get_fieldset_sql($sql);
    if (count($tabIdCourse)) {
        $listeId = join(', ', $tabIdCourse);
    }
    return $listeId;
}

/**
 *
 * @global moodle_database $DB
 * @param integer $approbateurid
 * @param boolean $validated
 * @return \html_table
 */
function get_table_course_to_validate($approbateurid, $validated) {
    global $DB;

    $res = new html_table();
    $res->data = array();

    $courseids = get_id_courses_to_validate($approbateurid, $validated);
    if ($courseids == '') {
        return $res;
    }

    $sql = "SELECT id, idnumber, shortname, fullname, startdate, visible FROM {course} c WHERE id IN ($courseids) ORDER BY id DESC";
    $dbcourses = $DB->get_records_sql($sql);
    foreach ($dbcourses as $dbcourse) {
        $row = array();
        $row[0] = new html_table_cell('');
        $row[0]->attributes = array('title' => '', 'class' => '');
        $row[1] = new html_table_cell($dbcourse->fullname);
        $row[1]->attributes = array('title' => $dbcourse->shortname .' ['. $dbcourse->idnumber.'] '. $dbcourse->fullname, 'class' => '');
        $row[2] = new html_table_cell(up1_meta_get_text($dbcourse->id, 'avalider'));
        $row[2]->attributes = array('title' => '', 'class' => '');
        $approbateurprop = up1_meta_get_user($dbcourse->id, 'approbateurpropid');
        $row[3] = new html_table_cell($approbateurprop['name']);
        $row[3]->attributes = array('title' => '', 'class' => '');
        $res->data[] = $row;
    }

    return $res;
}

function get_table_course_header() {
    $headings = array('', get_string('fullnamecourse'), '', 'Approbateur');
    $row = array();
    foreach ($headings as $h) {
        $cell = new html_table_cell($h);
        $cell->header = true;
        $row[] = $cell;
    }
    return array($row);
}
