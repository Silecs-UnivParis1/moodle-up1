<?php

/* 
 * @license http://www.gnu.org/licenses/gpl-3.0.html  GNU GPL v3
 */

require_once($CFG->dirroot . '/course/batch_lib.php');
require_once $CFG->dirroot . '/local/up1_courselist/courselist_tools.php';

/**
 * Returns the HTML that lists the courses that match the criteria.
 *
 * @param string $format "table" | "list"
 * @param stdClass $criteria
 * @param boolean  $visible (opt, true) Only show public courses
 * @return string HTML
 */
function widget_courselist_query($format, $criteria, $visible=true) {
    $courses = null;
    if ($criteria) {
        if ($visible) {
            $criteria->visible = 1;
        }
        $totalcount = 0;

        if ($format === 'table' && !empty($criteria->tableconfig)) {
            $tableconfig = 'data-tableconfig="' . htmlspecialchars($criteria->tableconfig) . '"';
            unset($criteria->tableconfig);
        } else {
            $tableconfig = '';
        }

        // 'search', 'startdateafter', 'startdatebefore', 'createdafter', 'createdbefore',
        // 'topcategory', 'node', 'enrolled', 'enrolledroles'
        if (empty($criteria->search)) {
            $criteria->search = '';
        }

        $courses = get_courses_batch_search($criteria, "c.fullname ASC", 0, 9999, $totalcount);
    }

    if (empty($courses)) {
        if ($criteria) {
            return "<p>Aucun cours ne correspond aux critères.</p>";
        }
        return '';
    } else {
        $courseformatter = new courselist_format($format);
        $html = $courseformatter->get_header($tableconfig);
        foreach ($courses as $course) {
            $html .= $courseformatter->format_course($course, true) . "\n";
        }
        $html .= $courseformatter->get_footer() . '<div style="clear:both;"></div>' . "\n";
        return $html;
    }
}
