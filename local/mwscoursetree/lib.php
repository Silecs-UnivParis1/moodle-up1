<?php

/**
 * @package    local
 * @subpackage mwscoursetree
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/* @var $PAGE page_base */

global $CFG;
require_once($CFG->dirroot . "/local/up1_courselist/courselist_tools.php");

/**
 * @todo compter les cours descendants dans le cas 2 ?
 * @todo compter les cours descendants dans le cas 3 ?
 * @todo limiter le dépliage au niveau 8 matière (ROFcourse niv.2)
 */
class course_tree {

    public $pseudopath;
    public $parentcatid;
    public $parentcat;
    public $component; // component ('01' to ... ; or NULL if level=1,2)

    const DEPTH_ROF_BEGIN = 4;

    const LEVEL_CATEGORY = 1;
    const LEVEL_CATEGORY_AND_ROF = 2;
    const LEVEL_ROF = 3;
    const LEVEL_ERROR = 4;

    public function __construct() {
        global $PAGE;
        if (empty($PAGE->context)) {
            $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
        }
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
        if ($this->parentcat && $this->parentcat->depth >= 3) {
            $this->component = courselist_cattools::get_component_from_category($this->parentcatid);
        } else {
            $this->component = null;
        }
    }

    /**
     * main function for the webservice service-children
     * @return array array(assoc-array()) : to be used by jqTree after json-encoding
     * @throws coding_exception
     */
    public function get_children() {

        $result = array();
        switch ($this->get_pseudopath_level()) {
            case self::LEVEL_CATEGORY:
                // CASE 1 node=category and children=categories
                $categories = get_categories($this->parentcatid);
                $result = $this->get_entries_from_categories($categories);
                break;
            case self::LEVEL_CATEGORY_AND_ROF:
                // CASE 2 node=category ($this->parentcatid)
                // and children = ROF entries (component 01 to ...) or courses (if no ROF rattachment)
                $courses = courselist_cattools::get_descendant_courses($this->parentcatid);
                list($rofcourses, $catcourses) = courselist_roftools::split_courses_from_rof($courses, $this->component);
                foreach ($catcourses as $crsid) {
                    $result[] = $this->get_entry_from_course($crsid, 1 + self::DEPTH_ROF_BEGIN);
                }
                $result = array_merge(
                        $result,
                        $this->get_entries_from_rof_courses($rofcourses, 1 + self::DEPTH_ROF_BEGIN)
                );
                break;
            case self::LEVEL_ROF:
                // CASE 3 under ROF root
                $rofpath = '/' . join('/', array_slice($this->pseudopath, 1));
                $depth = self::DEPTH_ROF_BEGIN - 1 + count($this->pseudopath);
                $rofcourses = courselist_roftools::get_courses_from_parent_rofpath($rofpath);
                $result = $this->get_entries_from_rof_courses($rofcourses, $depth);
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
            if ( $this->parentcatid === 0 ||
                 ($this->parentcat && $this->parentcat->depth < self::DEPTH_ROF_BEGIN) ) {
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
        static $courseformatter = false;
        if (!$courseformatter) {
            $courseformatter = new courselist_format('tree');
        }
        return array(
            'id' => null,
            'label' => $courseformatter->format_entry($crsid),
            'load_on_demand' => false,
            'depth' => $depth,
        );
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


    /**
     * get entries from courses having a ROF rattachement
     * @param array $rofcourses as given by split_courses_from_rof() (or other source)
     * @param int $depth of target entries
     * @return array array(assoc. array)
     */
    public function get_entries_from_rof_courses($rofcourses, $depth) {
        //$component = $this->coursetree->get_component_from_category($this->coursetree->parentcatid);
        $parentrofpath = '/' . join('/', array_slice($this->pseudopath, 1)); // le chemin sans la catégorie

        $prenodes = array();
        $directcourse = array();
        $unfold = array();
        //NORMALEMENT, après les traitements sur $rofcourses, $rofpathid devrait toujours être unique (sans ;)
        foreach ($rofcourses as $crsid => $rofpathid) {
            $arrofpath = array_filter(explode('/', $rofpathid));
            $prenode = "/cat{$this->parentcatid}" . '/' . join('/', array_slice($arrofpath, 0, $depth - 3));
            if (count($arrofpath) == $depth - 3) { // leaf
                $directcourse[$prenode][] = $crsid; // il peut y avoir plusieurs cours attachés à un même ROFid
            } elseif (count($arrofpath) > $depth - 3) { // subfolders
                $unfold[$prenode] = true;
            }
            $prenodes[] = $prenode;
        }
// var_dump($prenodes);

        $courseformatter = new courselist_format('tree');
        $items = array();
        foreach (array_unique($prenodes) as $node) {
            $arrofpath = explode('/', $node);
            $rofid = array_pop($arrofpath);
            list($rofobject, ) = rof_get_record($rofid);
            $name = $rofobject->name;

            $item['load_on_demand'] = !empty($unfold[$node]);
            if (isset($directcourse[$node]) && $directcourse[$node]) {
                foreach ($directcourse[$node] as $crsid) { // tous les cours directement rattachés au noeud
                    $item['load_on_demand'] = false;
                    $item['label'] = $courseformatter->format_entry($crsid, !$item['load_on_demand']);
                    $item['id'] = $node . '/' . $crsid;
                    $item['depth'] = $depth;
                    $items[] = $item;
                }
                // maintenant le sous-arbre partant du noeud
                $childcourses = courselist_roftools::get_courses_from_parent_rofpath($parentrofpath);
                if ($childcourses) { // au moins un fils
                    $itemsub['load_on_demand'] = true;
                    $itemsub['label'] = $this->display_entry_name($name, $node, false);
                    $itemsub['id'] = $node ;
                    $itemsub['depth'] = $depth;
                    $items[] = $itemsub;
                }
            } else {
                $item['label'] = $this->display_entry_name($name, $node, !$item['load_on_demand']);
                $item['id'] = $node;
                $item['depth'] = $depth;
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Returns an array of nodes.
     *
     * @param array $categories
     * @return array
     */
    public function get_entries_from_categories($categories) {
        $result = array();
        foreach ($categories as $category) {
            $courses = courselist_cattools::get_descendant_courses($category->id);
            $n = count($courses);
            if ($n >= 1) {
                $name = $category->name . ' (' . $n . ') ';
                $nodeid = '/cat' . $category->id;
                $result[] = array(
                    'id' => $nodeid,
                    'label' => $this->display_entry_name($name, $nodeid),
                    'load_on_demand' => true,
                    'depth' => $category->depth,
                );
            }
        }
        return $result;
    }
}
