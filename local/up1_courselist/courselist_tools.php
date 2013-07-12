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


class courselist_common {

    //** @todo validate_pseudopath 

    public static function get_courses_from_pseudopath($pseudopath) {

        if ( preg_match('/^\/cat(\d+)$/', $pseudopath, $matches) ) { // limited to a course category
            $cat = (int) $matches[1];  // catid = 0 special value for all courses
            $crs = courselist_cattools::get_descendant_courses($cat);
            if ($crs) {
                $courses = array_combine($crs, $crs); //
            } else {
                $courses = array();
            }
        } else { // at least one ROF item (component)
            $rofpath = strstr(substr($pseudopath, 1), '/'); // drop first component -category- of pseudopath
            $courses = courselist_roftools::get_courses_from_parent_rofpath($rofpath);
        }
        return $courses;
    }

    /**
     * Builds a HTML table listing each course in the pseudopath.
     *
     * @param string $pseudopath
     * @param string $format table|list
     * @return string HTML of the table.
     */
    public static function list_courses_html($pseudopath, $format) {
        $courses = courselist_common::get_courses_from_pseudopath($pseudopath);
        if ($courses) {
            $courseformatter = new courselist_format($format);
            $res = $courseformatter->get_header();
            foreach (courselist_roftools::sort_courses($courses) as $crsid) {
                // $rofpathid = $courses[$crsid];
                $res .= $courseformatter->format_entry($crsid, true) . "\n";
            }
            $res .= $courseformatter->get_footer() . "\n";
        } else { // no course
            $res = "<p><b>Aucun espace n'est pour le moment référencé avec les critères de sélection indiqués.</b></p>";
        }
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
    private $format = 'tree';
    private $cellelem;
    private $sep;
    private $header;
    private $footer;

    private $role;
    private $courseboard; /** bool : only if supervalidator */

    /**
     * Constructor.
     *
     * @param string $format = 'tree' | 'table' | 'list'
     */
    public function __construct($format = 'tree') {
        global $DB;

        $this->format = $format;

        switch ($this->format) {
            case 'tree':
                $this->cellelem = 'span';
                $this->sep = '';
                $this->header = '';
                $this->footer = '';
                break;
            case 'table':
                $this->cellelem = 'td';
                $this->sep = '';
                $this->header = <<<EOL
<table class="generaltable sortable" style="width: 100%;">
<thead>
    <tr>
        <th>Code</th>
        <th title="Niveau">Niv.</th>
        <th title="Semestre">Sem.</th>
        <th>Nom de l'espace de cours</th>
        <th>Enseignants</th>
        <th>&nbsp;</th>
    </tr>
</thead>
<tbody>
EOL;
                $this->footer = '</tbody></table>';
                break;
            case 'list':
                $this->cellelem = 'span';
                $this->sep = ' - ';
                $this->header = '<ul>';
                $this->footer = '</ul>';
                break;
            default:
                throw new Exception("coding error");

        }

        $this->role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        // disabled for complete compatibility with filter cache, which cannot depend upon user
        // $this->courseboard = has_capability('local/crswizard:supervalidator', context_system::instance());
        $this->courseboard = false;
        /** @todo disabled only if displayed via a filter */
    }

    /**
     * Return a formated course label.
     *
     * @param int $courseid
     * @param boolean $leaf opt, true
     * @return string formatted label
     */
    public function format_entry($courseid, $leaf = true) {
        global $DB;

        $dbcourse = $DB->get_record('course', array('id' => (int) $courseid));
        if (empty($dbcourse)) {
            return '';
        }
        return $this->format_course($dbcourse, $leaf);
    }

    /**
     * Return a formated course label.
     *
     * @param stdClass $course
     * @param boolean $leaf (opt) true
     * @return string formatted label
     */
    public function format_course($course, $leaf = true) {
        $teachers = '';
        $icons = '';

        // compute the elements
        if ($this->format == 'table'|| $this->format == 'list') {
            $code = self::format_code($course);
            $level = self::format_level($course);

        }
        if ($this->format == 'table' || $this->format == 'tree') {
            $teachers = self::format_teachers($course, 'coursetree-teachers');
            $icons = self::format_icons($course, 'coursetree-icons');
        }
        $crslink = self::format_name($course, 'coursetree-' . ($leaf ? "name" : "dir")) ;

        // renders the line
        switch ($this->format) {
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

    public function get_header() {
        return $this->header;
    }

    public function get_footer() {
        return $this->footer;
    }

    private function format_name($dbcourse, $class) {
        global $OUTPUT;
        $urlCourse = new moodle_url('/course/view.php', array('id' => $dbcourse->id));
        $crsname = get_course_display_name_for_list($dbcourse); // could be completed with ROF $name ?
        $rmicon = '';
        if ($this->format == 'tree'  &&  courselist_common::has_multiple_rattachements($dbcourse->id)) {
            $rmicon .= $OUTPUT->render(new pix_icon('t/add', 'Rattachement multiple'));
        }
        $crslink = '<' . $this->cellelem. ' class="' . $class . '">'
                . html_writer::link($urlCourse, $crsname) . '&nbsp;' . $rmicon
                . '</' . $this->cellelem . '>';
        return $crslink;
    }

    /**
     * format teachers : returns an abbreviated list with a title representing full list
     *
     * @global moodle_database $DB
     * @param stdClass $dbcourse course db record
     * @param string $class html class
     * @param integer $number number of teachers to display (1 or more)
     * @return string
     */
    public function format_teachers($dbcourse, $class, $number=1) {
        $context = get_context_instance(CONTEXT_COURSE, $dbcourse->id);
        $teachers = get_role_users($this->role->id, $context);

        $dispteachers = array_slice($teachers, 0, $number);
        $headteachers = join(', ', array_map('fullname', $dispteachers)) . (count($teachers) > $number ? ', …' : '');
        $titleteachers = join(', ', array_map('fullname', $teachers));
        $fullteachers = '<' . $this->cellelem . ' class="' . $class . '" style="cursor: default;" title="' . $titleteachers . '">'
                . $headteachers
                . '</' . $this->cellelem . '>';
        return $fullteachers;
    }

    public function format_icons($dbcourse, $class) {
        global $OUTPUT;

        $urlsynopsis = new moodle_url('/course/report/synopsis/index.php', array('id' => $dbcourse->id));
        $icons = '<' .$this->cellelem. ' class="' . $class. '">';
        $myicons = enrol_get_course_info_icons($dbcourse);
        if ($myicons) { // enrolment access icons
            foreach ($myicons as $pix_icon) {
                $icons .= $OUTPUT->render($pix_icon);
            }
        }
        if ( $dbcourse->visible == 0 ) {
            $icons .= $OUTPUT->render(new pix_icon('t/block', 'Fermé aux étudiants'));
        }
        $icons .= $OUTPUT->action_icon($urlsynopsis, new pix_icon('i/info', 'Afficher le synopsis du cours'));
        if ($this->courseboard) {
            $urlboard = new moodle_url('/local/courseboard/view.php', array('id' => $dbcourse->id));
            $icons .= $OUTPUT->action_icon($urlboard, new pix_icon('i/settings', 'Afficher le tableau de bord'));
        }
        $icons .= '</' . $this->cellelem . '>';
        return $icons;
    }

    private function format_code($dbcourse) {
        $code = strstr($dbcourse->idnumber, '-', true);
        if (courselist_common::has_multiple_rattachements($dbcourse->id)) {
            $code .= '<span title="Rattachement multiple">&nbsp;+</span>';
        }
        return   '<' . $this->cellelem . '>' . $code . '</' . $this->cellelem . '>' ;
    }

     private function format_level($dbcourse) {
        $niveau = up1_meta_html_multi($dbcourse->id, 'niveau', false, '');
        $semestre = up1_meta_html_multi($dbcourse->id, 'semestre', false, 'S.');
        return   '<' . $this->cellelem . '>' . $niveau . '</' . $this->cellelem . '>' . $this->sep
               . '<' . $this->cellelem . '>' . $semestre. '</' . $this->cellelem . '>';
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
        $r2 = self::get_descendant_courses_from_catbis($catid, 'up1categoriesbis');
        $r3 = self::get_descendant_courses_from_catbis($catid, 'up1categoriesbisrof');
        return array_unique(array_merge($r1, $r2, $r3));
    }

    /**
     * recherche les rattachements principaux aux catégories (standard moodle)
     * @global moodle_database $DB
     * @param int $catid ; 0 is accepted as a special value for all courses
     * @return array array(int crsid)
     */
    protected static function get_descendant_courses_from_category($catid) {
        global $DB;

        if ($catid === 0) {
            $res = $DB->get_fieldset_select('course', 'id', '');
            return $res;
        } else {
            $sql = "SELECT cco.instanceid FROM {context} cco "
                . "JOIN {context} cca ON (cco.path LIKE CONCAT(cca.path, '/%') ) "
                . "WHERE cca.instanceid=? AND cco.contextlevel=? and cca.contextlevel=? ";
            $res = $DB->get_fieldset_sql($sql, array($catid, CONTEXT_COURSE, CONTEXT_COURSECAT));
            return $res;
        }
    }

    /**
     * recherche les rattachements secondaires des catégories (up1categoriesbis)
     * @global moodle_database $DB
     * @param int $catid
     * @param string $metadatacat 'up1categoriesbis' or 'up1categoriesbisrof'
     * @return array array(int crsid)
     */
    protected static function get_descendant_courses_from_catbis($catid, $metadatacat) {
        global $DB;

        $sql = "SELECT cid.objectid, c2.path FROM {course_categories} c1 "
                . "JOIN {course_categories} c2 ON (c2.path LIKE CONCAT(c1.path, '/%') OR c2.id=c1.id) "
                . "JOIN {custom_info_data} cid ON ((CONCAT(';',data,';') LIKE CONCAT('%;',c2.id,';%'))) "
                . "WHERE c1.id = ? AND cid.fieldid = ? AND objectname='course' ";

        $fieldid = $DB->get_field('custom_info_field', 'id', array('shortname' => $metadatacat));
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
