<?php
/**
 * @package    local
 * @subpackage mwscoursetree
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . "/local/up1_metadata/lib.php");
require_once($CFG->dirroot . "/local/roftools/roflib.php");

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
            foreach ($catcourses as $crsid) {
                $result[] = get_entry_from_course($crsid, 5);
            }
            $result = array_merge($result, get_entries_from_rof_courses($rofcourses, 5, $pseudopath, $parentcatid));
        } else {
            throw new coding_exception('Category depth should not be > 4.');
        }

    } else { // CASE 3 under ROF root
        $rofpath = '/' . join('/', array_slice($pseudopath, 1));
        $depth = 3 + count($pseudopath);
//var_dump($rofpath);
        $fieldid = $DB->get_field('custom_info_field', 'id',
                array('objectname' => 'course', 'shortname' => 'up1rofpathid'), MUST_EXIST);
        $sql = "SELECT objectid, data FROM {custom_info_data} "
             . "WHERE objectname='course' AND fieldid=? AND data LIKE ?";
        $rofcourses = $DB->get_records_sql_menu($sql, array($fieldid, '%'.$rofpath.'%'));
//var_dump($rofcourses);
        $result = get_entries_from_rof_courses($rofcourses, $depth, $pseudopath, $parentcatid);
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
        $rofpathid = up1_meta_get_text($course->id, 'rofpathid', false);
        if ($rofpathid) {
            $rofcourses[$course->id] = $rofpathid;
        } else {
            $catcourses[$course->id] = $course->id;
        }
    }
    return array($rofcourses, $catcourses);
}

function get_component_from_category($catid) {
    global $DB;
    $idnumber = $DB->get_field('course_categories', 'idnumber', array('id' => $catid), MUST_EXIST);
    return substr($idnumber, 2, 2); // ex. '4:05/Masters' -> '05'
}

function get_entries_from_rof_courses($rofcourses, $depth, $pseudopath, $parentcatid) {
    $component = get_component_from_category($parentcatid);
    $prenodes = array();
    $items = array();

    foreach ($rofcourses as $crsid => $dummy) {
        $rofpathids = array_filter(explode(';', up1_meta_get_text($crsid, 'rofpathid', false)));
        foreach ($rofpathids as $rofpathid) {
            $arrofpath = array_filter(explode('/', $rofpathid));
            $prenode = "/$parentcatid" . '/' . join('/', array_slice($arrofpath, 0, $depth - 3));
            if (count($arrofpath) == $depth - 3 ) { // leaf
                $directcourse[$prenode] = $crsid;
            } elseif (count($arrofpath) > $depth - 3 ) { // subfolders
                $unfold[$prenode] = true;
            }
            $prenodes[] = $prenode;
        }
    }
// var_dump($prenodes);
    foreach (array_unique($prenodes) as $node) {
        $arrofpath = explode('/', $node);
        $rofid = array_pop($arrofpath);
        list($rofobject, $top) = rof_get_record($rofid);
        $name = $rofobject->name;

        if ( isset($directcourse[$node]) &&  $directcourse[$node] ) {
            $url = new moodle_url('/course/view.php', array('id' => $directcourse[$node]));
            $item['label'] = html_writer::link($url, $name);
        } else {
            $item['label'] = $name;
        }
        if ( isset($unfold[$node]) &&  $unfold[$node] ) {
            $item['load_on_demand'] = true;
        }
        $item['node'] = $node;
        $item['depth'] = $depth;
        $items[] = $item;
    }
    return $items;
}