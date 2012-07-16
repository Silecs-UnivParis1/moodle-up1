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
 * Display course synopsis page
 *
 * @package    report
 * @subpackage synopsis
 * @copyright  2012 Silecs {@link http://www.silecs.info}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from package report_outline
 */

require('../../config.php');
require_once($CFG->dirroot.'/report/outline/locallib.php');

$id = required_param('id',PARAM_INT);       // course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

$PAGE->set_url('/course/report/synopsis/index.php', array('id'=>$id));
$PAGE->set_pagelayout('report');

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('report/outline:view', $context);

// add_to_log($course->id, 'course', 'course synopsis', "course/report/synopsis/index.php?id=$course->id", $course->id);

$strreport = get_string('pluginname', 'report_synopsis');

$PAGE->set_title($course->shortname .': '. $strreport);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->fullname));



$gentable = new html_table();
$gentable->attributes['class'] = 'generaltable boxaligncenter';
$gentable->cellpadding = 5;
$gentable->id = 'outlinetable';
$gentable->head = array($stractivity, $strviews);

$sections = get_all_sections($course->id);
// $sectiontitle = get_section_name($course, $sections[$sectionnum]);

var_dump($sections);

echo html_writer::table($gentable);

echo $OUTPUT->footer();



