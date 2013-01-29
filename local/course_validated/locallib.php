<?php
/**
 * @package    local
 * @subpackage course_validated
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../up1_metadata/lib.php');

/**
 * @todo checkboxes multiple selections + dropdown menu
 * @todo master checkbox : all/none
 * @todo sort by clicking on column header ???
 */

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
        $sql .= " AND cd2.data=0 ";
    }
    if ($validated == 1) {
        $sql .= " AND cd2.data>0 ";
    }
    $sql .= "ORDER BY objectid DESC ";
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
 * @return \html_table
 */
function get_table_course_to_validate($approbateurid, $context) {
    global $DB;
    $etat = array(
        false => "En attente",
        true => "Approuvé"
    );

    $res = new html_table();
    $res->data = array();
    $count = 0;

    $courseids0 = get_id_courses_to_validate($approbateurid, 0);
    $dbcourses = array();
    if ($courseids0 != '') {
        $sql = "SELECT id, idnumber, shortname, fullname, startdate, visible "
             . " FROM {course} c WHERE id IN ($courseids0) ";
        $dbcourses = $DB->get_records_sql($sql);
    }
    $courseids1 = get_id_courses_to_validate($approbateurid, 1);
    if ($courseids1 != '') {
        $sql = "SELECT id, idnumber, shortname, fullname, startdate, visible "
             . " FROM {course} c WHERE id IN ($courseids1) ";
        $dbcourses1 = $DB->get_records_sql($sql);
        $dbcourses = array_merge($dbcourses, $dbcourses1);
    }

    foreach ($dbcourses as $dbcourse) {
        $count++;
        $row = new html_table_row();
        $row->cells[0] = new html_table_cell($count);
        $row->cells[0]->attributes = array('title' => '', 'class' => '');
        $url = new moodle_url('/course/view.php', array('id' => $dbcourse->id));
        $row->cells[1] = new html_table_cell(html_writer::link($url, $dbcourse->fullname));
        $row->cells[1]->attributes = array('title' => $dbcourse->shortname .' ['. $dbcourse->idnumber.'] '. $dbcourse->fullname, 'class' => '');
        $validated = up1_meta_get_text($dbcourse->id, 'datevalid') > 0;
        // $row->cells[2] = new html_table_cell($etat[$validated]);
        // $row->cells[2]->attributes = array('title' => '', 'class' => '');
        $row->cells[2] = new html_table_cell(action_icons($dbcourse->id, $validated, $dbcourse->visible, $context));
        $row->cells[2]->attributes = array('title' => '', 'class' => '');
        if ( ! $validated ) {
            $row->attributes = array('class' => 'highlight');
        }

        $demandeur = up1_meta_get_user($dbcourse->id, 'demandeurid');
        $url = new moodle_url('/user/profile.php', array('id' => $demandeur['id']));
        $row->cells[3] = new html_table_cell(html_writer::link($url, $demandeur['name']));
        $row->cells[3]->attributes = array('title' => '', 'class' => '');
        $adate = up1_meta_get_date($dbcourse->id, 'datedemande');
        $row->cells[4] = new html_table_cell($adate['date']);
        $row->cells[4]->attributes = array('title' => $adate['datetime'], 'class' => '');

        $approbateurprop = up1_meta_get_user($dbcourse->id, 'approbateurpropid');
        $approbateureff = up1_meta_get_user($dbcourse->id, 'approbateureffid');
        if ($validated) { // si la validation a déjà eu lieu
            $approbateur = $approbateureff;
        } else {
            $approbateur = $approbateurprop;
        }
        $row->cells[5] = new html_table_cell($approbateur['name']);
        $row->cells[5]->attributes = array('title' => 'Proposé='.$approbateurprop['name'].' ; effectif='.$approbateureff['name'], 'class' => '');
        $adate = up1_meta_get_date($dbcourse->id, 'datevalid');
        $row->cells[6] = new html_table_cell( (! empty($adate['date']) ? $adate['date'] : '<b>En attente</b>') );
        $row->cells[6]->attributes = array('title' => $adate['datetime'], 'class' => '');
        $row->cells[7] = new html_table_cell(userdate($dbcourse->startdate, '%Y-%m-%d'));
        $row->cells[7]->attributes = array('title' => '', 'class' => '');
        $rofname = up1_meta_get_text($dbcourse->id, 'rofname');
        if ( empty($rofname) ) {
            $row->cells[8] = new html_table_cell('Hors ROF');
            $row->cells[8]->attributes = array('title' => 'UP1 > ' . up1_meta_get_text($dbcourse->id, 'composante') . ' > ' .
                up1_meta_get_text($dbcourse->id, 'niveaulmda') . ' > ' .up1_meta_get_text($dbcourse->id, 'diplome'),
                'class' => '' );
        } else {
            $row->cells[8] = new html_table_cell($rofname);
            $row->cells[8]->attributes = array('title' => up1_meta_get_text($dbcourse->id, 'rofpath'), 'class' => '');
        }

        $res->data[] = $row;
    }

    return $res;
}


function action_icons($crsid, $validated, $visible, $context) {
    global $DB, $OUTPUT;
    $res = '';
    $coursecontext = get_context_instance(CONTEXT_COURSE, $crsid);
    $baseurl = new moodle_url('/local/course_validated/index.php');
    //$res .= html_writer::start_tag('div', array('class'=>'action'));

    // Approuver la demande
    if ( ! $validated ) {
        $url = new moodle_url($baseurl, array('validate' => $crsid));
        $res .= $OUTPUT->action_icon($url, new pix_icon('i/tick_green_small', 'Approuver la demande d\'ouverture'));
    }
    // Modif. paramètres cours
    if (has_capability('moodle/course:update', $coursecontext)) {
		$url = new moodle_url('/course/edit.php', array('id' => $crsid));
		$res .= $OUTPUT->action_icon($url, new pix_icon('t/edit', 'Modifier les paramètres de l\'espace de cours'));
		$res .= '&nbsp;';
    }
    // Supprimer le cours
    if (can_delete_course($crsid)) {
		$url = new moodle_url('/course/delete.php', array('id' => $crsid));
        $res .= $OUTPUT->action_icon($url, new pix_icon('t/delete', 'Supprimer l\'espace de cours'));
        $res .= '&nbsp;';
    }
    // Ouvrir / fermer le cours
    if (has_capability('moodle/course:visibility', $coursecontext)) {
		if ($visible) {
			$url = new moodle_url($baseurl, array('hide' => $crsid));
            $res .= $OUTPUT->action_icon($url, new pix_icon('t/hide', 'Ne pas ouvrir l\'espace de cours aux étudiants'));
        } else {
			$url = new moodle_url($baseurl, array('show' => $crsid));
            $res .= $OUTPUT->action_icon($url, new pix_icon('t/show', 'Ouvrir l\'espace de cours aux étudiants'));
        }
        $res .= '&nbsp;';
    }

    if (has_capability('local/crswizard:supervalidator', $context)) {
        // Tableau de bord
        $url = new moodle_url('/local/courseboard/view.php', array('id' => $crsid));
        $res .= $OUTPUT->action_icon($url, new pix_icon('i/settings', 'Afficher le tableau de bord'));
    } else { // approbateur lambda
        // Synopsis
        $url = new moodle_url('/course/report/synopsis/index.php', array('id' => $crsid));
        $res .= $OUTPUT->action_icon($url, new pix_icon('i/info', 'Afficher le synopsis du cours'));
        // Demande d'assistance
        $helpuser = get_config('local_crswizard', 'helpdesk_user');
        if ( isset($helpuser) ) {
            $userid = $DB->get_field('user', 'id', array('username' => $helpuser));
            if ($userid) {
                $url = new moodle_url('/message/index.php', array('id' => $userid));
                $res .= ' ' . $OUTPUT->action_icon($url, new pix_icon('a/help', 'Demander de l\'assistance'));
            }
        }
    }

	//$res .= html_writer::end_tag('div');
    return $res;
}



function get_table_course_header() {
    $headings = array('', get_string('fullnamecourse'), 'Actions', 'Demandeur', 'Date demande',
        'Approbateur', 'Date approbation', 'Date ouverture', 'Élément pédagogique');
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
        $action = 'hide';
    } else {
		$course = $DB->get_record('course', array('id' => $show));
        $visible = 1;
        $action = 'show';
	}

    if ($course) {
		$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        require_capability('moodle/course:visibility', $coursecontext);
        // Set the visibility of the course. we set the old flag when user manually changes visibility of course.
        $DB->update_record('course', array('id' => $course->id, 'visible' => $visible, 'visibleold' => $visible, 'timemodified' => time()));
        add_to_log($course->id, 'course_validated', $action, '/local/course_validated/index.php', '');
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
    add_to_log($crsid, 'course_validated', 'validate', '/local/course_validated/index.php', 'course validated');
    return true;
}

