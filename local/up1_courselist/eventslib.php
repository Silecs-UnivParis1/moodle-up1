<?php
/**
 * @package    local
 * @subpackage up1_courselist
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// documentation http://docs.moodle.org/dev/Events_API#Handling_an_event

require_once("../../config.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/textlib.class.php');


/**
 * This function is called by handlers course_created and course_updated (in ./db/events.php)
 * to resort the courses under the same parent category with respect to fullname
 * @param object $eventdata : course db object
 */
function handle_course_modified($eventdata) {
    global $DB;
    $category = $DB->get_record('course_categories', array('id' => $eventdata->category));
    if (! $category) {
        throw new moodle_exception('unknowncategory');
    }

    // copied from course/category.php l.87-95
    if ($courses = get_courses($eventdata->category, '', 'c.id,c.fullname,c.sortorder')) {
        collatorlib::asort_objects_by_property($courses, 'fullname', collatorlib::SORT_NATURAL);
        $i = 1;
        foreach ($courses as $course) {
            $DB->set_field('course', 'sortorder', $category->sortorder+$i, array('id'=>$course->id));
            $i++;
        }
        fix_course_sortorder(); // should not be needed
    }
}