<?php
/**
 * @package    local
 * @subpackage up1_courselist
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__DIR__)) . "/config.php");
require_once($CFG->dirroot . "/local/up1_metadata/lib.php");
require_once($CFG->dirroot . "/local/roftools/roflib.php");
require_once($CFG->dirroot.'/course/lib.php');


class courselist_common{
    public static function html_course_table($pseudopath) {
        global $DB;
        $rofpath = strstr(substr($pseudopath, 1), '/'); // drop first component -category- of pseudopath
        $courses = courselist_roftools::get_courses_from_parent_rofpath($rofpath);

        $res = '<table class="generaltable" style="width: 100%;">' . "\n" ;
        $res .= '<tr> <th>Code</th> <th title="Niveau">Niv.</th> <th title ="Semestre">Sem.</th> '
            . "<th>Nom de l'espace de cours</th> <th>Enseignants</th> <th>&nbsp;</th></tr>";
        foreach (courselist_roftools::sort_courses($courses) as $crsid) {
            $rofpathid = $courses[$crsid];
            $res .= courselist_format::format_entry($crsid, true, 'table') . "\n";
        }
        $res .= "</table>\n";
        return $res;
    }

    public static function html_course_list($pseudopath) {
        $rofpath = strstr(substr($pseudopath, 1), '/'); // drop first component -category- of pseudopath
        $courses = courselist_roftools::get_courses_from_parent_rofpath($rofpath);

        $res = "<ul>\n" ;
        foreach (courselist_roftools::sort_courses($courses) as $crsid) {
            $rofpathid = $courses[$crsid];
            $res .= courselist_format::format_entry($crsid, true, 'list') . "\n";
        }
        $res .= "</ul>\n";
        return $res;
    }


    public static function has_multiple_rattachements($crsid) {
        $catbis = up1_meta_get_text($crsid, 'up1categoriesbis', false);
        if ( ! $catbis == '') {
            return true;
        }
        $rofpathids = up1_meta_get_text($crsid, 'up1rofpathid', false);
        $n = count(explode(';', $rofpathids));
        if ($n > 1) {
            return true ;
        }
        return false;
    }
}


class courselist_format {
    /**
     * format course label
     * @param int $crsid
     * @param boolean $leaf opt, true
     * @param string $format = 'tree' | 'table' | 'list'
     * @return string formatted label
     */
    public static function format_entry($crsid, $leaf = true, $format = 'tree') {
        global $DB;
        $cellelems = array('tree' => 'span', 'table' => 'td', 'list' => 'span');
        $seps = array('tree' => '', 'table' => '', 'list' => ' - ');
        $cellelem = $cellelems[$format];
        $sep = $seps[$format];
        $first = '';
        $teachers = '';
        $icons = '';
        $dbcourse = $DB->get_record('course', array('id' => $crsid));

        // compute the elements
        if ($format == 'table'|| $format == 'list') {
            $code = self::format_code($dbcourse, $cellelem, $sep);
            $level = self::format_level($dbcourse, $cellelem, $sep);

        }
        $crslink = self::format_name($dbcourse, $cellelem, 'coursetree-' . ($leaf ? "name" : "dir"), $format) ;
        if ($format == 'table'|| $format == 'tree') {
            $teachers = self::format_teachers($dbcourse, $cellelem, 'coursetree-teachers');
            $icons = self::format_icons($dbcourse, $cellelem, 'coursetree-icons');
        }

        // renders the line
        switch ($format) {
            case 'tree':
                return $crslink . $teachers . $icons ;
                break;
            case 'table':
                return '<tr>' . $code . $level . $crslink . $teachers . $icons . '</tr>';
                break;
            case 'list':
                return '<li>' . $level . ' - ' . $crslink . ' ('. $code . ')' . '</li>';
        }
    }

    public static function format_name($dbcourse, $element, $class, $format) {
        global $OUTPUT;
        $urlCourse = new moodle_url('/course/view.php', array('id' => $dbcourse->id));
        $crsname = $dbcourse->fullname; // could be completed with ROF $name ?
        $rmicon = '';
        if ($format == 'tree'  &&  courselist_common::has_multiple_rattachements($dbcourse->id)) {
            $rmicon .= $OUTPUT->render(new pix_icon('t/add', 'Rattachement multiple'));
        }
        $crslink = '<' . $element. ' class="' . $class . '">'
                . html_writer::link($urlCourse, $crsname) . '&nbsp;' . $rmicon
                . '</' . $element . '>';
        return $crslink;
    }

    public static function format_teachers($dbcourse, $element, $class) {
        global $DB;
        $context = get_context_instance(CONTEXT_COURSE, $dbcourse->id);
        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $teachers = get_role_users($role->id, $context);
        $firstteacher = fullname(current($teachers)) . (count($teachers) > 1 ? ', …' : '');
        $titleteachers = join(', ', array_map('fullname', $teachers));
        $fullteachers = '<' . $element . ' class="' . $class . '" style="cursor: default;" title="' . $titleteachers . '">'
                . $firstteacher
                . '</' . $element . '>';
        return $fullteachers;
    }

    public static function format_icons($dbcourse, $element, $class) {
        global $OUTPUT;
        $url = new moodle_url('/course/report/synopsis/index.php', array('id' => $dbcourse->id));
        $icons = '<' .$element. ' class="' . $class. '" style="text-align: right;">';
        $myicons = enrol_get_course_info_icons($dbcourse);
        if ($myicons) { // enrolment access icons
            foreach ($myicons as $pix_icon) {
                $icons .= $OUTPUT->render($pix_icon);
            }
        }
        if ( $dbcourse->visible == 0 ) {
            $icons .= $OUTPUT->render(new pix_icon('t/block', 'Fermé aux étudiants'));
        }
        $icons .= $OUTPUT->action_icon($url, new pix_icon('i/info', 'Afficher le synopsis du cours'));
        $icons .= '</' . $element . '>';
        return $icons;
    }

    public static function format_code($dbcourse, $element, $sep) {
        $code = strstr($dbcourse->idnumber, '-', true);
        if (courselist_common::has_multiple_rattachements($dbcourse->id)) {
            $code .= '<span title="Rattachement multiple">&nbsp;+</span>';
        }
        return   '<' . $element . '>' . $code . '</' . $element . '>' ;
    }

     public static function format_level($dbcourse, $element, $sep) {
        $convertannee = array ('?', 'L1', 'L2', 'L3', 'M1', 'M2', 'D');
        $niveauannee = up1_meta_get_text($dbcourse->id, 'niveauannee', false);
        $niveau = ( isset($convertannee[$niveauannee]) ? $convertannee[$niveauannee] : 'A' );
        $semestre = 'S' . up1_meta_get_text($dbcourse->id, 'semestre', false);
        return   '<' . $element . '>' . $niveau . '</' . $element . '>' . $sep
               . '<' . $element . '>' . $semestre. '</' . $element . '>';
    }
}


    //********** CATEGORY TOOLS
class courselist_cattools {
    /**
     * get component (ex. 05) from categoryid
     * @param int $catid
     * @return string component, ex. "05"
     */
    public static function get_component_from_category($catid) {
        global $DB;
        $idnumber = $DB->get_field('course_categories', 'idnumber', array('id' => $catid), MUST_EXIST);
        return substr($idnumber, 2, 2); // ex. '4:05/Masters' -> '05'
    }

    /**
     * recherche les rattachements des cours aux catégories (principaux ET secondaires)
     * @param int $catid
     * @return array array(int crsid)
     */
    public static function get_descendant_courses($catid) {
        $r1 = self::get_descendant_courses_from_category($catid);
        $r2 = self::get_descendant_courses_from_catbis($catid);
        return array_unique(array_merge($r1, $r2));
    }

    /**
     * recherche les rattachements principaux aux catégories (standard moodle)
     * @global moodle_database $DB
     * @param int $catid
     * @return array array(int crsid)
     */
    protected static function get_descendant_courses_from_category($catid) {
        global $DB;

        $sql = "SELECT cco.instanceid FROM {context} cco "
                . "JOIN {context} cca ON (cco.path LIKE CONCAT(cca.path, '/%') ) "
                . "WHERE cca.instanceid=? AND cco.contextlevel=? and cca.contextlevel=? ";
        $res = $DB->get_fieldset_sql($sql, array($catid, CONTEXT_COURSE, CONTEXT_COURSECAT));
        return $res;
    }

    /**
     * recherche les rattachements secondaires des catégories (up1categoriesbis)
     * @global moodle_database $DB
     * @param int $catid
     * @return array array(int crsid)
     */
    protected static function get_descendant_courses_from_catbis($catid) {
        global $DB;

        $sql = "SELECT cid.objectid, c2.path FROM {course_categories} c1 "
                . "JOIN {course_categories} c2 ON (c2.path LIKE CONCAT(c1.path, '/%') OR c2.id=c1.id) "
                . "JOIN {custom_info_data} cid ON ((CONCAT(';',data,';') LIKE CONCAT('%;',c2.id,';%'))) "
                . "WHERE c1.id = ? AND cid.fieldid = ? AND objectname='course' ";

        $fieldid = $DB->get_field('custom_info_field', 'id', array('shortname' => 'up1categoriesbis'));
        $res = $DB->get_fieldset_sql($sql, array($catid, $fieldid));
        return $res;
    }
}

class courselist_roftools {

    /**
     * return all courses rattached to the given rofpath ; only this rofpath in the returned course value
     * @global moodle_database $DB
     * @param string $rofpath ex. "/02/UP1-PROG39308/UP1-PROG24870"
     * @return array assoc-array(crsid => rofpathid) ; in case of multiple rattachements, only the matching rofpathid is returned
     */
    public static function get_courses_from_parent_rofpath($rofpath) {
        global $DB;
        // 1st step : find the matching courses
        $fieldid = $DB->get_field('custom_info_field', 'id', array('objectname' => 'course', 'shortname' => 'up1rofpathid'), MUST_EXIST);
        $sql = "SELECT objectid, data FROM {custom_info_data} "
                . "WHERE objectname='course' AND fieldid=? AND data LIKE ?";
        $res = $DB->get_records_sql_menu($sql, array($fieldid, '%' . $rofpath . '%'));
        //var_dump($res);
        // 2nd step : filter the results to keep only matching rofpaths
        $rofcourses = array();
        foreach ($res as $crsid => $rofpathids) {
            foreach (explode(';', $rofpathids) as $rofpathid) {
                if (strstr($rofpathid, $rofpath)) {
                    $rofcourses[$crsid] = $rofpathid;
                }
            }
        }
        //var_dump($rofcourses);
        return $rofcourses;
    }

    /**
     * split courses as 2 arrays : the ones with a ROF rattachement (rofcourses), and the ones without (catcourses)
     * @param array $courses array of course objects (from DB)
     * @return array array($rofcourses, $catcourses)
     */
    public static function split_courses_from_rof($courses) {
        $rofcourses = array();
        $catcourses = array();
        foreach ($courses as $crsid) {
            $rofpathid = up1_meta_get_text($crsid, 'rofpathid', false);
            if ($rofpathid) {
                $rofpath = explode(';', $rofpathid);
                $rofcourses[$crsid] = $rofpath[0]; // main rofpath only //** @todo CHECK THIS
            } else {
                $catcourses[$crsid] = $crsid;
            }
        }
        return array($rofcourses, $catcourses);
    }

    /**
     * sort courses by annee / semestre / fullname
     * @param array $courses ($crsid => $rofpathid)
     * @return array ($crsid)
     */
    public static function sort_courses($courses) {
        global $DB;

        if (empty($courses)) {
            return array();
        }
        $subquery = up1_meta_gen_sql_query('course', array('up1niveauannee', 'up1semestre'));
        $sql = "SELECT c.id "
            . "FROM {course} AS c JOIN (" . $subquery .") AS s ON (s.id = c.id) "
            . "WHERE c.id IN  ( " . implode(", ", array_keys($courses)) . " ) "
            . "ORDER BY s.up1niveauannee, s.up1semestre, c.fullname ";
        $sortcourses = $DB->get_fieldset_sql($sql);
        return $sortcourses;
    }
}
