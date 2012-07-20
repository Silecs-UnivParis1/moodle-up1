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
 * @package    coursereport
 * @subpackage synopsis
 * @copyright  2012 Silecs {@link http://www.silecs.info}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from package report_outline
 */

require('../../../config.php');
require_once($CFG->dirroot.'/course/report/synopsis/locallib.php');
require_once($CFG->libdir.'/custominfo/lib.php');

$id = required_param('id',PARAM_INT);       // course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

$PAGE->set_url('/course/report/synopsis/index.php', array('id'=>$id));
$PAGE->set_pagelayout('report');

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('report/outline:view', $context); //** @todo trouver une meilleure capacité

// add_to_log($course->id, 'course', 'course synopsis', "course/report/synopsis/index.php?id=$course->id", $course->id);

$strreport = get_string('pluginname', 'coursereport_synopsis');

$PAGE->set_title($course->shortname .': '. $strreport);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->fullname));

echo "<h2>" . get_string('Description', 'coursereport_synopsis') . "</h2>\n";

echo "<ul>\n";
echo "<li>Abrégé : ". $course->shortname ."</li>\n";
echo "<li>Code : ". $course->idnumber ."</li>\n";
echo "</ul>\n";
echo '<div id="summary">' . $course->summary . '</div>';


//** @todo utiliser les fonctions prévues
// $customdata = custominfo_data::type('course')->get_record($course->id);
// $customdata = custominfo_data::type('course')->display_fields($course->id);
// var_dump($customdata);

$custominfo_data = custominfo_data::type('course');
$cinfos = $custominfo_data->get_record($course->id);
echo "<ul>\n";
foreach ($cinfos as $label=>$info) {
    echo "<li>" . $label ." : ". $info. "</li>\n";
}
echo "</ul>\n";


echo "<h2>" . get_string('Teachers', 'coursereport_synopsis') . "</h2>\n";
// output based on roles ; only editingteacher for now
// for an output based on capabilities, use instead get_users_by_capability(): much heavier
$teach_context = get_context_instance(CONTEXT_COURSE, $course->id);
echo "<ul>\n";
$troles = array('editingteacher', 'teacher');
foreach ($troles as $trole) {
    $role = $DB->get_record('role', array('shortname' => $trole));
    $teachers = get_role_users($role->id, $teach_context);
    foreach ($teachers as $teacher) {
        echo "<li>" . fullname($teacher) . " - " . $teacher->rolename . "</li>\n";
    }
}
echo "</ul>\n";


echo "<h2>" . get_string('Cohorts', 'coursereport_synopsis') . "</h2>\n";


echo "<h2>" . get_string('Outline', 'coursereport_synopsis') . "</h2>\n";
$sections = get_all_sections($course->id);
echo "<ol>\n";
foreach ($sections as $section) {
    $sectiontitle = get_section_name($course, $section);
    echo "<li>" . $sectiontitle . "</li>";
}
echo "</ol>\n";


echo $OUTPUT->footer();



