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
    $etat = array(
        false => "En attente",
        true => "Approuvé"
    );

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
        $valid = up1_meta_get_text($dbcourse->id, 'datevalid') > 0;
        $row[2] = new html_table_cell($etat[$valid]);
        $row[2]->attributes = array('title' => '', 'class' => '');
        $demandeur = up1_meta_get_user($dbcourse->id, 'demandeurid');
        $row[3] = new html_table_cell($demandeur['name']);
        $row[3]->attributes = array('title' => '', 'class' => '');
        $adate = up1_meta_get_date($dbcourse->id, 'datedemande');
        $row[4] = new html_table_cell($adate['date']);
        $row[4]->attributes = array('title' => $adate['datetime'], 'class' => '');

        $approbateurprop = up1_meta_get_user($dbcourse->id, 'approbateurpropid');
        $approbateureff = up1_meta_get_user($dbcourse->id, 'approbateureffid');
        if ($valid) { // si la validation a déjà eu lieu
            $approbateur = $approbateureff;
        } else {
            $approbateur = $approbateurprop;
        }
        $row[5] = new html_table_cell($approbateur['name']);
        $row[5]->attributes = array('title' => 'Proposé='.$approbateurprop['name'].' ; effectif='.$approbateureff['name'], 'class' => '');
        $adate = up1_meta_get_date($dbcourse->id, 'datevalid');
        $row[6] = new html_table_cell($adate['date']);
        $row[6]->attributes = array('title' => $adate['datetime'], 'class' => '');
        $row[7] = new html_table_cell(userdate($dbcourse->startdate, '%Y-%m-%d'));
        $row[7]->attributes = array('title' => '', 'class' => '');
        $rofname = up1_meta_get_text($dbcourse->id, 'rofname');
        if ( empty($rofname) ) {
            $row[8] = new html_table_cell('Hors ROF');
            $row[8]->attributes = array('title' => 'UP1 > ' . up1_meta_get_text($dbcourse->id, 'composante') . ' > ' .
                up1_meta_get_text($dbcourse->id, 'niveaulmda') . ' > ' .up1_meta_get_text($dbcourse->id, 'diplome'),
                'class' => '' );
        } else {
            $row[8] = new html_table_cell($rofname);
            $row[8]->attributes = array('title' => up1_meta_get_text($dbcourse->id, 'rofpath'));
        }

        $row[9] = new html_table_cell('');
        $row[9]->attributes = array('title' => '', 'class' => '');
        $res->data[] = $row;
    }

    return $res;
}

function get_table_course_header() {
    $headings = array('', get_string('fullnamecourse'), 'État', 'Demandeur', 'Date demande',
        'Approbateur', 'Date approbation', 'Date ouverture', 'Élément pédagogique', 'Modifier');
    $row = array();
    foreach ($headings as $h) {
        $cell = new html_table_cell($h);
        $cell->header = true;
        $row[] = $cell;
    }
    return array($row);
}


// ACTION functions

/**
 * Set a course visible or invisible
 * copied from course/category.php l. 132-150
 * @param int $show course id to become visible
 * @param int $hide course id
 */

function show_or_hide($show, $hide) {
	global $DB;
	if (!empty($hide)) {
		$course = $DB->get_record('course', array('id' => $hide));
        $visible = 0;
    } else {
		$course = $DB->get_record('course', array('id' => $show));
        $visible = 1;
	}

    if ($course) {
		$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        require_capability('moodle/course:visibility', $coursecontext);
        // Set the visibility of the course. we set the old flag when user manually changes visibility of course.
        $DB->update_record('course', array('id' => $course->id, 'visible' => $visible, 'visibleold' => $visible, 'timemodified' => time()));
    }
}


/**
 * validate a course (set the custom data up1validatedate to time()
 * @param int $crsid identifiant du cours à valider
 */
function validate_course ($crsid) {
	global $DB, $USER;

	$iddate = up1_meta_get_id($crsid, 'datevalid');
    $idwho = up1_meta_get_id($crsid, 'approbateureffid');

	if ( ! ($iddate && $idwho)) {
        throw new coding_exception('Erreur ! manque up1datevalid ou up1approbateureffid pour le cours ' . $crsid);
        return false;
    }
    $DB->update_record('custom_info_data', array('id' => $iddate, 'data' => time()));
    $DB->update_record('custom_info_data', array('id' => $idwho, 'data' => $USER->id));
    return true;
}

