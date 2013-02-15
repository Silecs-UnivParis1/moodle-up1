<?php
/**
 * @package    local
 * @subpackage mwscoursetree
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . "/local/up1_metadata/lib.php");

function get_children($node) {
    global $DB;

    $result = array();
    if ( isset($node) ) {
        $pseudopath = explode('/', substr($node, 1) );
        $parentcatid = (int) $pseudopath[0] ;
    } else {
        $parentcatid = 0;
        $pseudopath = array('0');
    }
    $parentcat = $DB->get_record('course_categories', array('id' => $parentcatid));
    if ( count($pseudopath) == 1 ) { // course categories

        if ($parentcatid === 0 || ($parentcat && $parentcat->depth < 4)) { // CASE 1 node=category and children=categories
            $categories = get_categories($parentcatid);
            foreach ($categories as $category) {
                $courses = get_courses($category->id, "c.sortorder ASC", "c.id");
                $n = count($courses);
                if ($n >= 0) { //** @todo ce calcul est idiot
                    $name = $category->name;
                    $result[] = array(
                        'node' => '/' . $category->id,
                        'label' => $name,
                        'load_on_demand' => true,
                        'depth' => $category->depth,
                    );

                }
            }

        } elseif ($parentcat->depth == 4) { // CASE 2 node=category and children = ROF entries or courses
            $courses = get_courses($parentcatid, "c.sortorder ASC", "c.id");
            list($rofcourses, $catcourses) = split_courses_from_rof($courses);
//var_dump($rofcourses);
//var_dump($catcourses);
//die();
            foreach ($catcourses as $crsid) {
                $result[] = get_entry_from_course($crsid, 5);
            }
            foreach ($rofcourses as $course) {
                //
            }
        } else {
            throw new coding_exception('Category depth should not be > 4.');
        }

    } else { // under ROF root

    }

    return $result;
}



function get_entry_from_course($crsid, $depth) {
    global $DB;

    $course = $DB->get_record('course', array('id' => $crsid));
    $url = new moodle_url('/course/view.php', array('id' => $crsid));
    return array(
        'node' => null,
        'label' => html_writer::link($url, $course->fullname),
        'load_on_demand' => false,
        'depth' => $depth,
    );
}

function split_courses_from_rof($courses) {
    $rofcourses = array();
    $catcourses = array();
    foreach($courses as $course) {
        $rofpathid = up1_meta_get_text($course->id, 'rofpathid', $error=false);
        if ($rofpathid) {
            $rofcourses[$course->id] = $rofpathid;
        } else {
            $catcourses[$course->id] = $course->id;
        }
    }
    return array($rofcourses, $catcourses);
}