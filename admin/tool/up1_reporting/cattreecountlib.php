<?php

/**
 * Administrator reporting
 *
 * @package    tool
 * @subpackage up1_reporting
 * @copyright  2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * computes the count based only on course categories tree, and no ROF rattachement
 * see http://tickets.silecs.info/mantis/view.php?id=1214#c7440
 */

defined('MOODLE_INTERNAL') || die;
require_once(__DIR__ . '/../../../config.php');

/**
 * computes a count of courses from course categories ; only true for "leaf" categories (depth=4)
 * for upper categories (depth=3) count=0 since there is no direct relation between categories and courses
 * @param int $parentcat parent category id
 * @return object records from get_records_sql() with attributes : (cat) id, (cat) path, (cat) depth, (cat) name, count
 */
function cat_tree_rawcount_courses($parentcat) {
    global $DB;

    $parentpath = $DB->get_field('course_categories', 'path', array('id' => $parentcat));
    $sql = "SELECT cc.id, cc.path, cc.depth, cc.name, COUNT(DISTINCT co.id) AS count  "
         . "FROM {course_categories} cc "
         . "LEFT JOIN {course} co ON (co.category = cc.id) "
         . "WHERE cc.path LIKE ? "
         . "GROUP BY cc.id";
    $res = $DB->get_records_sql($sql, array($parentpath . '/%'));

    return $res;
}

/**
 * computes the total count for categories of level=3
 * @param int $parentcat parent category id
 * @return object records with at least attributes : (cat) id, (cat) path, (cat) depth, count
 */
function cat_tree_smartcount_courses($parentcat) {
    $records = cat_tree_rawcount_courses($parentcat);
    $totalcounts = cat_tree_compute_total($records);
    return $totalcounts;
}

/**
 * computes a count of enrolled cohorts from course categories ; only true for "leaf" categories (depth=4)
 * for upper categories (depth=3) count=0 since there is no direct relation between categories and courses
 * @param int $parentcat parent category id
 * @return object records from get_records_sql() with attributes : (cat) id, (cat) path, (cat) depth, (cat) name, count
 */
function cat_tree_rawcount_cohorts($parentcat) {
    global $DB;

    $parentpath = $DB->get_field('course_categories', 'path', array('id' => $parentcat));
    $sql = "SELECT cc.id, cc.path, cc.depth, COUNT(DISTINCT e.id) AS count  "
         . "FROM {course_categories} cc "
         . "LEFT JOIN {course} co ON (co.category = cc.id) "
         . "LEFT JOIN {enrol} e ON (e.courseid = co.id) "
         . "WHERE cc.path LIKE ?  AND  (e.id IS NULL OR e.status = 0) "
         . "GROUP BY cc.id";
    $res = $DB->get_records_sql($sql, array($parentpath . '/%'));
//var_dump($res);

    return $res;
}

/**
 * computes the total count for cohorts of level=3
 * @param int $parentcat parent category id
 * @return object records with at least attributes : (cat) id, (cat) path, (cat) depth, count
 */
function cat_tree_smartcount_cohorts($parentcat) {
    $records = cat_tree_rawcount_cohorts($parentcat);
    $totalcounts = cat_tree_compute_total($records);
    return $totalcounts;
}

/**
 * computes a count of courses from course categories ; only true for "leaf" categories (depth=4)
 * for upper categories (depth=3) count=0 since there is no direct relation between categories and courses
 * @param int $parentcat parent category id
 * @param array $roles array of roles shortnames to be included in count
 * @return object records from get_records_sql() with attributes : (cat) id, (cat) path, (cat) depth, (cat) name, count
 */
function cat_tree_rawcount_roles($parentcat, $roles) {
    global $DB;

    $parentpath = $DB->get_field('course_categories', 'path', array('id' => $parentcat));
    list($insql, $inparams) = $DB->get_in_or_equal($roles);
    // GA le CONCAT ci-dessous est une astuce pour faire prendre en compte le LEFT JOIN sur r.shortname IN ...
    // ce n'est pas parfait car un utilisateur /pourrait théoriquement/ etre inscrit avec plusieurs roles,
    // donc etre compté plusieurs fois (ex. teacher et editingteacher)
    // @todo vérifier s'il n'y a pas moyen de faire autrement (tests unitaires indispensables)
    $sql = "SELECT cc.id, cc.path, cc.depth, cc.name, COUNT(DISTINCT CONCAT(ra.userid,':',r.id)) AS count "
         . "FROM {course_categories} cc "
         . "LEFT JOIN {course} co ON (co.category = cc.id) "
         . "LEFT JOIN {context} cx ON (cx.instanceid = co.id AND (cx.contextlevel = ?) ) "
         . "LEFT JOIN {role_assignments} ra ON (ra.contextid = cx.id) "
         . "LEFT JOIN {role} r ON (r.id = ra.roleid AND r.shortname $insql) "
         . "WHERE cc.path LIKE ? "
         . "GROUP BY cc.id";
    $sqlparams = array_merge(array(CONTEXT_COURSE), $inparams, array($parentpath . '/%'));
    $res = $DB->get_records_sql($sql, $sqlparams);

    return $res;
}

function cat_tree_smartcount_roles($parentcat, $roles) {
    $records = cat_tree_rawcount_roles($parentcat, $roles);
    $totalcounts = cat_tree_compute_total($records);
    return $totalcounts;
}



/**
 * computes the total count for categories of level 3, by summing up counts for levels 4
 * @param object $records :  records with AT LEAST attributes: (cat) id, (cat) path, (cat) depth, count
 * @return $records
 */
function cat_tree_compute_total($records) {
    foreach($records as $record) {
        if ($record->depth == 4 && preg_match('!/\d+/\d+/(\d+)/\d+!', $record->path, $matches)) {
            $parentcat = $matches[1];
            if ( isset($records[$parentcat]) ) {
                $records[$parentcat]->count += $record->count;
            }
            else { //@todo GA ne devrait jamais arriver
//                $records[$parentcat] = new StdClass;
//                $records[$parentcat]->count = 0;
            }
        }
    }
    return $records;
}

/**
 * display the html table for categories (level=3,4) counts - see
 * @param type $parentid
 * @return type
 */
function cat_tree_display_table($parentid) {

    $table = new html_table();
    $table->head = array('id', 'UFR / Diplômes', 'Espaces de cours', 'Cohortes', 'Étudiants', 'Enseignants');

    $totalcourses = cat_tree_smartcount_courses($parentid);
    $totalcohorts = cat_tree_smartcount_cohorts($parentid);
    $totalstudents = cat_tree_smartcount_roles($parentid, array('student'));
    $totalteachers = cat_tree_smartcount_roles($parentid, array('teacher', 'editingteacher'));

    $tablecontent = array();
    foreach($totalcourses as $catid => $coursecount) {
        //  @todo régler le style par des vraies CSS
        $cellstyle = "span";
        $indent = '&nbsp;&nbsp;';
        if ($coursecount->depth == 3) {
            $cellstyle = "b";
            $indent = '';
        }
        $tablecontent[] = array(
            $catid,
            $indent . html_writer::tag($cellstyle, $coursecount->name),
            html_writer::tag($cellstyle, $coursecount->count),
            html_writer::tag($cellstyle, $totalcohorts[$catid]->count),
            html_writer::tag($cellstyle, $totalstudents[$catid]->count),
            html_writer::tag($cellstyle, $totalteachers[$catid]->count),
        );
    }
    $table->data = $tablecontent;
    return html_writer::table($table);

}