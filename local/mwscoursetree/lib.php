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

}





