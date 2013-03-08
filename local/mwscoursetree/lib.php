<?php

/**
 * @package    local
 * @subpackage mwscoursetree
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/local/up1_metadata/lib.php");
require_once($CFG->dirroot . "/local/roftools/roflib.php");

/**
 * @todo compter les cours descendants dans le cas 2 ?
 * @todo compter les cours descendants dans le cas 3 ?
 * @todo limiter le dépliage au niveau 8 matière (ROFcourse niv.2)
 */
class course_tree {

    public $pseudopath;
    public $parentcatid;
    public $parentcat;

    const DEPTH_ROF_BEGIN = 4;

    const LEVEL_CATEGORY = 1;
    const LEVEL_CATEGORY_AND_ROF = 2;
    const LEVEL_ROF = 3;
    const LEVEL_ERROR = 4;

    public function __construct() {
        global $PAGE;
        $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    }

    /**
     * Static constructor that avoids using "new".
     *
     * @param string $node
     * @return course_tree
     */
    static public function from_node($node) {
        $new = new self;
        $new->set_node($node);
        return $new;
    }

    /**
     *
     * @param string $node is a concat of '/cat(catid)' and the rofpathid, ex. '/cat2136/03/UP1-PROG28809'
     * @global moodle_database $DB
     * @return course_tree
     */
    public function set_node($node = null) {
        global $DB;

        if ($node) {
            $this->pseudopath = explode('/', substr($node, 1));
            if (preg_match('/cat(\d+)/', $this->pseudopath[0], $matches)) {
                $this->parentcatid = (int) $matches[1];
            } else {
                $this->parentcatid = 0;
                $this->pseudopath = array('0');
            }
        } else {
            $this->parentcatid = 0;
            $this->pseudopath = array('0');
        }
        $this->parentcat = $DB->get_record('course_categories', array('id' => $this->parentcatid));
    }

    /**
     * main function for the webservice service-children
     * @return array array(assoc-array()) : to be used by jqTree after json-encoding
     * @throws coding_exception
     */
    public function get_children() {
        $rof = new rof_tools($this);
        $cat = new category_tools($this);

        $result = array();
        switch ($this->get_pseudopath_level()) {
            case self::LEVEL_CATEGORY:
                // CASE 1 node=category and children=categories
                $categories = get_categories($this->parentcatid);
                $result = $cat->get_entries_from_categories($categories);
                break;
            case self::LEVEL_CATEGORY_AND_ROF:
                // CASE 2 node=category and children = ROF entries or courses
                $courses = $cat->get_descendant_courses($this->parentcatid);
                list($rofcourses, $catcourses) = $rof->split_courses_from_rof($courses);
                foreach ($catcourses as $crsid) {
                    $result[] = $this->get_entry_from_course($crsid, 1 + self::DEPTH_ROF_BEGIN);
                }
                $result = array_merge(
                        $result,
                        $rof->get_entries_from_rof_courses($rofcourses, 1 + self::DEPTH_ROF_BEGIN)
                );
                break;
            case self::LEVEL_ROF:
                // CASE 3 under ROF root
                $rofpath = '/' . join('/', array_slice($this->pseudopath, 1));
                $depth = self::DEPTH_ROF_BEGIN - 1 + count($this->pseudopath);
                $rofcourses = $rof->get_courses_from_parent_rofpath($rofpath);
                $result = $rof->get_entries_from_rof_courses($rofcourses, $depth);
                break;
            case self::LEVEL_ERROR:
            default:
                throw new coding_exception('Category depth should not be > ' . self::DEPTH_ROF_BEGIN);
        }

        return $result;
    }

    /**
     * Returns a LEVEL_* constant according to the current node's position
     *
     * @return integer
     */
    protected function get_pseudopath_level() {
        if (count($this->pseudopath) == 1) { // course categories
            if ($this->parentcatid === 0 || ($this->parentcat && $this->parentcat->depth < self::DEPTH_ROF_BEGIN)) {
                return self::LEVEL_CATEGORY;
            } else if ($this->parentcat->depth == self::DEPTH_ROF_BEGIN) {
                return self::LEVEL_CATEGORY_AND_ROF;
            } else {
                return self::LEVEL_ERROR;
            }
        } else {
            return self::LEVEL_ROF;
        }
    }


    protected function get_entry_from_course($crsid, $depth) {
        return array(
            'id' => null,
            'label' => $this->format_course_entry('', $crsid),
            'load_on_demand' => false,
            'depth' => $depth,
        );
    }

    /**
     * format course label
     * @param string $name course/ROF name ; if empty, will be filled with the course fullname
     * @param int $crsid
     * @param boolean $leaf opt, true
     * @param string $format = 'tree' | 'table' | 'list'
     * @return string formatted label
     */
    public function format_course_entry($name, $crsid, $leaf = true, $format = 'tree') {
        global $DB;
        $rowelems = array('tree' => '', 'table' => 'tr', 'list' => 'li');
        $cellelems = array('tree' => 'span', 'table' => 'td', 'list' => 'span');
        $seps = array('tree' => '', 'table' => '', 'list' => ' - ');
        $rowelem = $rowelems[$format];
        $cellelem = $cellelems[$format];
        $sep = $seps[$format];
        $dbcourse = $DB->get_record('course', array('id' => $crsid));
        $first = '';
        $teachers = '';
        $icons = '';

        if ($format == 'table'|| $format == 'list') {
            $first = $this->format_course_firstcols($dbcourse, $cellelem, $sep);
        }
        $crslink = $this->format_course_name($dbcourse, $cellelem, 'coursetree-' . ($leaf ? "name" : "dir")) ;
        if ($format == 'table'|| $format == 'tree') {
            $teachers = $this->format_course_teachers($dbcourse, $cellelem, 'coursetree-teachers');
            $icons = $this->format_course_icons($dbcourse, $cellelem, 'coursetree-icons');
        }
        $sep2 = (! empty($teachers) ? $sep : '');
        return (empty($rowelem) ? '' : '<' . $rowelem . '> ')
                . $first .$sep. $crslink .$sep2. $teachers .$sep2. $icons .
               (empty($rowelem) ? '' : '</' . $rowelem . '>');
    }

    public function format_course_name($dbcourse, $element, $class) {
        global $OUTPUT;
        $urlCourse = new moodle_url('/course/view.php', array('id' => $dbcourse->id));
        $crsname = $dbcourse->fullname; // could be completed with ROF $name ?
        $rmicon = '';
        if ($this->has_multiple_rattachements($dbcourse->id)) {
            $rmicon .= $OUTPUT->render(new pix_icon('t/add', 'Rattachement multiple'));
        }
        $crslink = '<' . $element. ' class="' . $class . '">'
                . html_writer::link($urlCourse, $crsname) . '&nbsp;' . $rmicon
                . '</' . $element . '>';
        return $crslink;
    }

    public function format_course_teachers($dbcourse, $element, $class) {
        global $DB;
        $context = get_context_instance(CONTEXT_COURSE, $dbcourse->id);
        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $teachers = get_role_users($role->id, $context);
        $firstteacher = fullname(current($teachers)) . (count($teachers) > 1 ? '…' : '');
        $titleteachers = join(', ', array_map('fullname', $teachers));
        $fullteachers = '<' . $element . ' class="' . $class . '" title="' . $titleteachers . '">'
                . $firstteacher
                . '</' . $element . '>';
        return $fullteachers;
    }

    public function format_course_icons($dbcourse, $element, $class) {
        global $OUTPUT;
        $url = new moodle_url('/course/report/synopsis/index.php', array('id' => $dbcourse->id));
        $icons = '<' .$element. ' class="' . $class. '">';
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

    public function format_course_firstcols($dbcourse, $element, $sep) {
        $convertannee = array ('?', 'L1', 'L2', 'L3', 'M1', 'M2', 'D');
        $code = strstr($dbcourse->idnumber, '-', true);
        $niveauannee = up1_meta_get_text($dbcourse->id, 'niveauannee', false);
        $niveau = $convertannee[$niveauannee];
        $semestre = 'S' . up1_meta_get_text($dbcourse->id, 'semestre', false);
        return   '<' . $element . '>' . $code . '</' . $element . '>' . $sep
               . '<' . $element . '>' . $niveau . '</' . $element . '>' . $sep
               . '<' . $element . '>' . $semestre. '</' . $element . '>';
    }


    /**
     * returns the "name" part of the label, with a span showing the node-id depending on class jqtree-hidden
     * used for all non-course entries (course categories and ROF entries)
     * @param string $name
     * @param string $nodeid to display optionally
     * @param boolean $leaf opt, false
     * @return string
     */
    public function display_entry_name($name, $nodeid, $leaf = false) {
        return '<span class="jqtree-hidden">[' . $nodeid . ']</span>&nbsp;'
                . '<span class="coursetree-' . ($leaf ? "name" : "dir") . '">' . $name . "</span>";
    }


    public function has_multiple_rattachements($crsid) {
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


class rof_tools {
    private $coursetree;

    public function __construct(course_tree $coursetree) {
        $this->coursetree = $coursetree;
    }

    /**
     * return all courses rattached to the given rofpath ; only this rofpath in the returned course value
     * @global moodle_database $DB
     * @param string $rofpath ex. "/02/UP1-PROG39308/UP1-PROG24870"
     * @return array assoc-array(crsid => rofpathid) ; in case of multiple rattachements, only the matching rofpathid is returned
     */
    public function get_courses_from_parent_rofpath($rofpath) {
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
    public function split_courses_from_rof($courses) {
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
     * get entries from courses having a ROF rattachement
     * @param array $rofcourses as given by split_courses_from_rof() (or other source)
     * @param int $depth of target entries
     * @return array array(assoc. array)
     */
    public function get_entries_from_rof_courses($rofcourses, $depth) {
        //$component = $this->coursetree->get_component_from_category($this->coursetree->parentcatid);
        //$parentrofpath = '/' . join('/', array_slice($this->pseudopath, 1)); // le chemin sans la catégorie

        $prenodes = array();
        $directcourse = array();
        $unfold = array();
        //NORMALEMENT, après les traitements sur $rofcourses, $rofpathid devrait toujours être unique (sans ;)
        foreach ($rofcourses as $crsid => $rofpathid) {
            $arrofpath = array_filter(explode('/', $rofpathid));
            $prenode = "/cat{$this->coursetree->parentcatid}" . '/' . join('/', array_slice($arrofpath, 0, $depth - 3));
            if (count($arrofpath) == $depth - 3) { // leaf
                $directcourse[$prenode][] = $crsid; // il peut y avoir plusieurs cours attachés à un même ROFid
            } elseif (count($arrofpath) > $depth - 3) { // subfolders
                $unfold[$prenode] = true;
            }
            $prenodes[] = $prenode;
        }
// var_dump($prenodes);

        $items = array();
        foreach (array_unique($prenodes) as $node) {
            $arrofpath = explode('/', $node);
            $rofid = array_pop($arrofpath);
            list($rofobject, ) = rof_get_record($rofid);
            $name = $rofobject->name;

            $item['load_on_demand'] = !empty($unfold[$node]);
            if (isset($directcourse[$node]) && $directcourse[$node]) {
                foreach ($directcourse[$node] as $crsid) {
                    $item['label'] = $this->coursetree->format_course_entry('', $crsid, !$item['load_on_demand']);
                    $item['id'] = $node . '/' . $crsid;
                    $item['depth'] = $depth;
                    $items[] = $item;
                }
            } else {
                $item['label'] = $this->coursetree->display_entry_name($name, $node, !$item['load_on_demand']);
                $item['id'] = $node;
                $item['depth'] = $depth;
                $items[] = $item;
            }
        }
        return $items;
    }

    public function html_course_table($pseudopath) {
        $rofpath = strstr(substr($pseudopath, 1), '/'); // drop first component -category- of pseudopath
        $courses = $this->get_courses_from_parent_rofpath($rofpath);
        //@todo sort $courses by niveau / semestre / nom

        echo '<table class="generaltable">' . "\n" ;
        echo "<tr> <th>Code</th> <th>Niveau</th> <th>Semestre</th> "
            . "<th>Nom du cours</th> <th>Enseignants</th> <th>Icônes</th></tr>";
        foreach ($courses as $crsid => $rofpathid) {
            echo $this->coursetree->format_course_entry('', $crsid, true, 'table') . "\n";
        }
        echo "</table>\n";
    }

    public function html_course_list($pseudopath) {
        $rofpath = strstr(substr($pseudopath, 1), '/'); // drop first component -category- of pseudopath
        $courses = $this->get_courses_from_parent_rofpath($rofpath);
        //@todo sort $courses by niveau / semestre / nom

        echo "<ol>\n" ;
        foreach ($courses as $crsid => $rofpathid) {
            echo $this->coursetree->format_course_entry('', $crsid, true, 'list') . "\n";
        }
        echo "</ol>\n";
    }
}


class category_tools {
    private $coursetree;

    public function __construct(course_tree $coursetree) {
        $this->coursetree = $coursetree;
    }

    /**
     * get component (ex. 05) from categoryid
     * @param int $catid
     * @return string component, ex. "05"
     */
    public function get_component_from_category($catid) {
        global $DB;
        $idnumber = $DB->get_field('course_categories', 'idnumber', array('id' => $catid), MUST_EXIST);
        return substr($idnumber, 2, 2); // ex. '4:05/Masters' -> '05'
    }

    /**
     * Returns an arry of nodes.
     *
     * @param array $categories
     * @return array
     */
    public function get_entries_from_categories($categories) {
        $result = array();
        foreach ($categories as $category) {
            $courses = $this->get_descendant_courses($category->id);
            $n = count($courses);
            if ($n >= 1) {
                $name = $category->name . ' (' . $n . ') ';
                $nodeid = '/cat' . $category->id;
                $result[] = array(
                    'id' => $nodeid,
                    'label' => $this->coursetree->display_entry_name($name, $nodeid),
                    'load_on_demand' => true,
                    'depth' => $category->depth,
                );
            }
        }
        return $result;
    }

    /**
     * recherche les rattachements des cours aux catégories (principaux ET secondaires)
     * @param int $catid
     * @return array array(int crsid)
     */
    public function get_descendant_courses($catid) {
        $r1 = $this->get_descendant_courses_from_category($catid);
        $r2 = $this->get_descendant_courses_from_catbis($catid);
        return array_unique(array_merge($r1, $r2));
    }

    /**
     * recherche les rattachements principaux aux catégories (standard moodle)
     * @global moodle_database $DB
     * @param int $catid
     * @return array array(int crsid)
     */
    protected function get_descendant_courses_from_category($catid) {
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
    protected function get_descendant_courses_from_catbis($catid) {
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
