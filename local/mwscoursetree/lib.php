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
     * @param string $node is a concat of '/(catid)' and the rofpathid, ex. '/2136/03/UP1-PROG28809'
     * @global moodle_database $DB
     * @return course_tree
     */
    public function set_node($node = null) {
        global $DB;

        if ($node) {
            $this->pseudopath = explode('/', substr($node, 1));
            $this->parentcatid = (int) $this->pseudopath[0];
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
            'label' => $this->format_course_label('', $crsid),
            'load_on_demand' => false,
            'depth' => $depth,
        );
    }

    /**
     * format course label
     * @param string $name course/ROF name ; if empty, will be filled with the course fullname
     * @param int $crsid
     * @param boolean $leaf opt, true
     * @return string formatted label
     */
    public function format_course_label($name, $crsid, $leaf = true) {
        global $DB, $OUTPUT;

        // main link
        $urlCourse = new moodle_url('/course/view.php', array('id' => $crsid));
        $dbcourse = $DB->get_record('course', array('id' => $crsid));
        if ($name == '') {
            $name = $dbcourse->fullname; //override ROF name with course name ?
        }
        $crslink = '<span class="coursetree-' . ($leaf ? "name" : "dir") . '">'
                . html_writer::link($urlCourse, $name) . '</span>';

        // teachers
        $context = get_context_instance(CONTEXT_COURSE, $crsid);
        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $teachers = get_role_users($role->id, $context);
        $firstteacher = fullname(current($teachers)) . (count($teachers) > 1 ? '…' : '');
        $titleteachers = join(', ', array_map('fullname', $teachers));
        $fullteachers = '<span class="coursetree-teachers" title="' . $titleteachers . '">' . $firstteacher . '</span>';

        // icons
        $url = new moodle_url('/course/report/synopsis/index.php', array('id' => $crsid));
        $icons = '<span class="coursetree-icons">';
        $myicons = enrol_get_course_info_icons($dbcourse);
        if ($myicons) { // enrolment access icons
            foreach ($myicons as $pix_icon) {
                $icons .= $OUTPUT->render($pix_icon);
            }
        }
        $icons .= $OUTPUT->action_icon($url, new pix_icon('i/info', 'Afficher le synopsis du cours'));
        $icons .= '</span>';
        //die($crslink .' '. $fullteachers . ' ' . $icons);
        return $crslink . $fullteachers . $icons;
    }

    /**
     * returns the "name" part of the label, with a span showing the node-id depending on class jqtree-hidden
     * @param string $name
     * @param string $nodeid 0
     * @param boolean $leaf opt, false
     * @return string
     */
    public function display_name($name, $nodeid, $leaf = false) {
        return '<span class="jqtree-hidden">[' . $nodeid . ']</span>&nbsp;'
                . '<span class="coursetree-' . ($leaf ? "name" : "dir") . '">' . $name . "</span>";
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
                $rofcourses[$crsid] = $rofpathid;
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
        foreach ($rofcourses as $crsid => $rofpathid) {
            $arrofpath = array_filter(explode('/', $rofpathid));
            $prenode = "/{$this->coursetree->parentcatid}" . '/' . join('/', array_slice($arrofpath, 0, $depth - 3));
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
                    $item['label'] = $this->coursetree->format_course_label('', $crsid, !$item['load_on_demand']);
                    $item['id'] = $node . '/' . $crsid;
                    $item['depth'] = $depth;
                    $items[] = $item;
                }
            } else {
                $item['label'] = $this->coursetree->display_name($name, $node, !$item['load_on_demand']);
                $item['id'] = $node;
                $item['depth'] = $depth;
                $items[] = $item;
            }
        }
        return $items;
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
            if ($n >= 1) { //** @todo ce calcul est idiot
                $name = $category->name . ' (' . $n . ') ';
                $nodeid = '/' . $category->id;
                $result[] = array(
                    'id' => $nodeid,
                    'label' => $this->coursetree->display_name($name, $nodeid),
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
