<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains functions used by the outline reports
 *
 * @package    coursereport
 * @subpackage synopsis
 * @copyright  2012 Silecs {@link http://www.silecs.info}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from package report_outline
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/custominfo/lib.php');

/**
 * Returns list of cohorts enrolled into course.
 * @todo this function should be moved to  enrol/cohort/locallib.php
 * OR lib/accesslib.php (next to get_enrolled_users)
 *
 * @param course id $courseid
 * @param array(role_id, ...) $roleids
 * @return array of cohort records
 */
function get_enrolled_cohorts($courseid, $roleids=null) {
    global $DB;

	    $sql = "SELECT c.id, c.name, c.idnumber, c.description
              FROM {cohort} c
              JOIN {enrol} e ON (e.enrol='cohort' AND e.customint1=c.id) ";
//			  JOIN {role} r ON (r.id = e.roleid) //** @todo bugfix DML read exception; don't know why
		$sql .= " WHERE e.courseid = ? ";
	if ( isset($roleids) ) {
		$sql .= "AND roleid IN (". implode(',', $roleids) .")";
	}
	$sql .= " ORDER BY c.name ASC";

    return $DB->get_records_sql($sql, array($courseid));
}


function html_table_informations($course) {
    echo "\n\n" . '<table class="generaltable">' . "\n";
    echo html_rows_informations($course);
    echo html_rows_teachers($course);
    echo html_rows_cohorts($course);
    echo html_rows_status($course);
    echo "</table>\n";
}


function html_rows_informations($course) {
    $res = '';
    $res .= '<tr> <td>Nom</td> <td>' . $course->fullname . '</td> </tr>' . "\n";
    $res .= '<tr> <td>Nom abrégé</td> <td>' . $course->shortname . '</td> </tr>' . "\n";
    return $res;
}

function html_rows_teachers($course) {
    // output based on roles ; only editingteacher + teacher for now
    // for an output based on capabilities, use instead get_users_by_capability(): much heavier
    global $DB;
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $troles = array('editingteacher' => 'Enseignants', 'teacher' => 'Autres intervenants' );
    $res = '';
    foreach ($troles as $trole => $rowhead) {
        $role = $DB->get_record('role', array('shortname' => $trole));
        $teachers = get_role_users($role->id, $context);
        if ($teachers) {
            $res .= '<tr> <td>' . $rowhead . '</td>';
            $who = '';
            foreach ($teachers as $teacher) {
                $who .= fullname($teacher) . ', ';
            }
            $who = substr($who, 0, -2);
            $res .= '<td>' . $who . '</td> </tr>';
        }
    }
    return $res;
}

function html_rows_cohorts($course) {
    global $DB;
    $res = '';
    $sroles = array(
        'student' => 'Consultation des ressources, participation aux activités :',
        'guest' => 'Consultation des ressources uniquement :'
        );
    $res .= '<tr> <td>Groupes utilisateurs inscrits</td> <td>';
    foreach ($sroles as $srole => $title) {
        $role = $DB->get_record('role', array('shortname' => $srole));
        $cohorts = get_enrolled_cohorts($course->id, array($role->id));
        if (empty($cohorts)) {
            $res .= "$title " . get_string('Nocohort', 'coursereport_synopsis');
        } else {
            $res .= "$title";
            $res .= "<ul>";
                foreach ($cohorts as $cohort) {
                $res .= "<li> (". $cohort->idnumber .") ". $cohort->name ;
                $res .= "</li>";
            }
            $res .= "</ul>";
        }
    }
    $res .= '</td> </tr>';
    return $res;
}

function html_rows_status($course) {
    $res = '<tr> <td>État</td> <td>';
    $demandeur = up1_meta_get_user($course->id, 'demandeurid');
    $adate = up1_meta_get_date($course->id, 'datedemande');
    if ($demandeur) {
        $res .= 'Créé par ' . $demandeur['name'] . ' le ' . $adate['datetime'] . "</br>\n";
    }
    $approbateureff = up1_meta_get_user($course->id, 'approbateureffid');
    $adate = up1_meta_get_date($course->id, 'datevalid');
    if ($approbateureff) {
        $res .= 'Approuvé par ' . $demandeur['name'] . ' le ' . $adate['datetime'] . "\n";
    } else {
        $res .= "En attente d'approbation.";
    }
    $res .= '</td></tr>';
    return $res;
}


function html_custom_data($course) {
    $fieldList = custominfo_data::type('course')->get_structured_fields($course->id, true);
    echo "<ul>\n";
    foreach ($fieldList as $category => $fields) {
        if ($category == 'Other fields') continue;
            echo "<li>" . $category . "</li>\n<ul>";
            foreach ($fields as $fname => $fvalue ) {
                echo "<li>$fname : $fvalue</li>";
            }
        echo "</ul>\n";
    }
    echo "</ul>\n";
}

