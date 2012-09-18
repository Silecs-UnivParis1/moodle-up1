<?php

/**
 * ROF Statistics
 *
 * @package    report
 * @subpackage rofstats
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require('../../config.php');
require_once($CFG->dirroot.'/report/rofstats/locallib.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();

// $issue = optional_param('issue', '', PARAM_ALPHANUMEXT); // show detailed info about one issue only

// Print the header.
admin_externalpage_setup('reportrofstats', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', 'report_rofstats'));

$url = "$CFG->wwwroot/report/rofstats/index.php";

$browserurl = "$CFG->wwwroot/local/rof_browser/rof_browser.php";
echo '<div><a href="' . $browserurl. '">ROF browser</a></div>';


echo "<h3>Compteurs</h3>\n";
$table = new html_table();
$table->head = array('Items', 'Nb');
$table->data = report_rofstats_generic();
echo html_writer::table($table);

echo "<h3>Composantes</h3>\n";
$table = new html_table();
$table->head = array('', '# Programmes', 'Id. ROF', 'Nom');
$table->data = report_rofstats_components();
echo html_writer::table($table);

echo "<h3>Cours ROF</h3>\n";
$table = new html_table();
$table->head = array('Items', 'Nb');
$table->data = report_rofstats_courses();
echo html_writer::table($table);

echo "<h3>Personnes</h3>\n";
$table = new html_table();
$table->head = array('Niveaux', 'Personnes non vides');
$table->data = report_rofstats_persons_not_empty();
echo html_writer::table($table);


echo "<h2>Anomalies ?</h2>";

echo "<h3>Programmes hybrides</h3>\n";
$table = new html_table();
$table->head = array('Programme', 'Titre', 'ss-prog.', 'cours');
$table->data = report_rofstats_hybrid_programs();
echo html_writer::table($table);

/*  $table->head  = array($strissue, $strstatus, $strdesc, $strconfig);
    $table->size  = array('30%', '10%', '50%', '10%' );
    $table->align = array('left', 'left', 'left', 'left');
    $table->attributes = array('class'=>'scurityreporttable generaltable');
    $table->data  = array();
    $table->data[] = $row;
*/

echo '<div><a href="' . $browserurl. '">ROF browser</a></div>';

echo $OUTPUT->footer();
