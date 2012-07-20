<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains public API of synopsis report
 *
 * @package    coursereport
 * @subpackage synopsis
 * @copyright  2012 Silecs {@link http://www.silecs.info}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from package report_outline
 */

defined('MOODLE_INTERNAL') || die;



/**
 * Is current user allowed to access this report
 *
 * @private defined in lib.php for performance reasons
 *
 * @param stdClass $course
 * @return bool
 */
function report_synopsis_can_access_synopsis($course) {
    global $USER;

    $coursecontext = context_course::instance($course->id);

    if (has_capability('report/outline:view', $coursecontext)) {
        return true;
    }

    return false;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function report_synopsis_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $array = array(
        '*'                    => get_string('page-x', 'pagetype'),
        'report-*'             => get_string('page-report-x', 'pagetype'),
        'report-outline-*'     => get_string('page-report-outline-x',  'report_outline'),
        'report-outline-index' => get_string('page-report-outline-index',  'report_outline'),
    );
    return $array;
}

function synopsis_report_extend_navigation($reportnav, $course, $context) {
    $url = new moodle_url('/course/report/synopsis/index.php', array('id' => $course->id));
    $reportnav->add("Sypnosys", $url);
}
