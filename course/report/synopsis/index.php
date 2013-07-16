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

global $DB, $PAGE, $OUTPUT;

$id = required_param('id', PARAM_INT);       // course id
$layout = optional_param('layout', 'report', PARAM_ALPHA); // default layout=report
if ($layout != 'popup') {
    $layout = 'report';
}

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

$PAGE->set_url('/course/report/synopsis/index.php', array('id'=>$id));
$PAGE->set_pagelayout($layout);
$PAGE->requires->css(new moodle_url('/course/report/synopsis/styles.css'));

$site = get_site();
$strreport = get_string('pluginname', 'coursereport_synopsis');
$pagename = up1_meta_get_text($course->id, 'up1nomnorme', false);
if ( ! $pagename ) {
    $pagename = $course->fullname;
}
$PAGE->set_context($context);
$PAGE->set_title($pagename); // $course->shortname .': '. $strreport); // tab title
$PAGE->set_heading($site->fullname);
echo $OUTPUT->header();

echo "<h2>" . $pagename . "</h2>\n";

echo '<div id="synopsis-bigbutton">' . "\n";
html_button_join($course);
if ( has_capability('local/crswizard:supervalidator', context_system::instance()) )
{
    $urlboard = new moodle_url('/local/courseboard/view.php', array('id' => $course->id));
    $icon = $OUTPUT->action_icon($urlboard, new pix_icon('i/settings', 'Afficher le tableau de bord'));
    echo $icon;
}
echo '</div>' . "\n";

// Description
echo '<div id="synopsis-summary">'
    . format_text($course->summary, $course->summaryformat)
    . '</div>' . "\n\n";

echo '<div id="synopsis-informations">' . "\n";
echo "<h3>Informations sur l'espace de cours</h3>\n";
html_table_informations($course);
echo '</div>' . "\n";

echo '<div id="synopsis-rattachements">' . "\n";
echo "<h3>Rattachements à l'offre de formation</h3>\n";
html_table_rattachements($course);
echo '</div>' . "\n";

echo $OUTPUT->footer();