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

/**
 * main function for the webservice service-children
 * @param string $node is a concat of '/(catid)' and the rofpathid, ex. '/2136/03/UP1-PROG28809'
 * @return array(assoc. array()) : to be used by jqTree after json-encoding
 * @throws coding_exception
 */
function get_children($node) {
    global $DB, $PAGE;
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));

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
                    $nodeid = '/' . $category->id;
                    $result[] = array(
                        'id' => $nodeid,
                        'label' => display_name($name, $nodeid),
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
        $fieldid = $DB->get_field('custom_info_field', 'id',
                array('objectname' => 'course', 'shortname' => 'up1rofpathid'), MUST_EXIST);
        $sql = "SELECT objectid, data FROM {custom_info_data} "
             . "WHERE objectname='course' AND fieldid=? AND data LIKE ?";
        $rofcourses = $DB->get_records_sql_menu($sql, array($fieldid, '%'.$rofpath.'%'));
        $result = get_entries_from_rof_courses($rofcourses, $depth, $pseudopath, $parentcatid);
    }

    return $result;
}


function get_entry_from_course($crsid, $depth) {
    return array(
        'id' => null,
        'label' => format_course_label('', $crsid),
        'load_on_demand' => false,
        'depth' => $depth,
    );
}

/**
 * split courses as 2 arrays : the ones with a ROF rattachement (rofcourses), and the ones without (catcourses)
 * @param array $courses array of course objects (from DB)
 * @return array($rofcourses, $catcourses)
 */
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

/**
 * get component (ex. 05) from categoryid
 * @param int $catid
 * @return string component, ex. "05"
 */
function get_component_from_category($catid) {
    global $DB;
    $idnumber = $DB->get_field('course_categories', 'idnumber', array('id' => $catid), MUST_EXIST);
    return substr($idnumber, 2, 2); // ex. '4:05/Masters' -> '05'
}

/**
 * get entries from courses having a ROF rattachement
 * @param array $rofcourses as given by split_courses_from_rof() (or other source)
 * @param int $depth of target entries
 * @param array(string) $pseudopath for the parent node
 * @param int $parentcatid
 * @return array(assoc. array)
 */
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
            $item['label'] = format_course_label($name, $directcourse[$node]);
        } else {
            $item['label'] = display_name($name, $node);
        }
        if ( isset($unfold[$node]) &&  $unfold[$node] ) {
            $item['load_on_demand'] = true;
        }
        $item['id'] = $node;
        $item['depth'] = $depth;
        $items[] = $item;
    }
    return $items;
}

/**
 * format course label
 * @param string $name course/ROF name ; if empty, will be filled with the course fullname
 * @param int $crsid
 * @return strinf formatted label
 */
function format_course_label($name, $crsid) {
    global $DB, $OUTPUT;

    // main link
    $url = new moodle_url('/course/view.php', array('id' => $crsid));
    $dbcourse = $DB->get_record('course', array('id' => $crsid));
    if ($name == '') {
        $name = $dbcourse->fullname; //override ROF name with course name ?
    }
    $crslink = html_writer::link($url, $name);
    // teachers
    $titleteachers = '';
    $context = get_context_instance(CONTEXT_COURSE, $crsid);
    $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $teachers = get_role_users($role->id, $context);
    $dup = $teachers;
    $firstteacher = fullname(array_shift($dup)) . (count($teachers) > 1 ? '...' : '');
    foreach ($teachers as $teacher) {
        $titleteachers .= fullname($teacher) . ' ';
    }
    $fullteachers = '<span title="'. $titleteachers .'">' . $firstteacher . '</span>';
    // icons
    $url = new moodle_url('/course/report/synopsis/index.php', array('id' => $crsid));
    $icons = $OUTPUT->action_icon($url, new pix_icon('i/info', 'Afficher le synopsis du cours'));
    if ($myicons = enrol_get_course_info_icons($dbcourse)) { // enrolment access icons
        foreach ($myicons as $pix_icon) {
            $icons .= $OUTPUT->render($pix_icon);
        }
    }
    //die($crslink .' '. $fullteachers . ' ' . $icons);
    return $crslink .' '. $fullteachers . ' ' . $icons;
}

/**
 * returns the "name" part of the label, with a span showing the node-id depending on class jqtree-hidden
 * @param string $name
 * @param string $nodeid 0
 * @return string
 */
function display_name($name, $nodeid) {
    return '<span class="jqtree-hidden">[' . $nodeid . ']</span> &nbsp; ' . $name ;
}