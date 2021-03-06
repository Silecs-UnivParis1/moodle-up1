<?php

/**
 * Administrator reporting
 *
 * @package    tool
 * @subpackage up1_reporting
 * @copyright  2013-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/up1_courselist/courselist_tools.php');

/**
 * prepare table content to be displayed : UFR | course count | student count | teacher count
 * @param int $parentcat parent category id
 * @return array of array of strings (html) to be displayed by html_writer::table()
 */
function report_base_counts($parentcat) {
    global $DB;

    $teachroles = array('editingteacher' => 'Enseignants', 'teacher' => 'Autres intervenants' );
    $componentcats = $DB->get_records_menu('course_categories', array('parent' => $parentcat), '', 'id, name');

    foreach ($componentcats as $catid => $ufrname) {
        $courses = courselist_cattools::get_descendant_courses($catid);
        $result[] = array(
            $ufrname,
            count($courses),
            count_roles_from_courses(array('student' => "Étudiants"), $courses),
            count_roles_from_courses($teachroles, $courses),
        );
    }
    return $result;
}


function count_roles_from_courses($roles, $courses) {
    global $DB;
    
    $res = 0;
    foreach ($roles as $role => $rolefr) {
        $dbrole = $DB->get_record('role', array('shortname' => $role));
        foreach ($courses as $courseid) {
            $context = get_context_instance(CONTEXT_COURSE, $courseid);
            $teachers = get_role_users($dbrole->id, $context);
            $res += count($teachers);
        }
    }
    return $res;
}

