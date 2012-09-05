<?php

/**
 * UP1 Users Statistics
 *
 * @package    report
 * @subpackage up1stats
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require('../../config.php');
require_once($CFG->dirroot.'/report/up1stats/locallib.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();

// $issue = optional_param('issue', '', PARAM_ALPHANUMEXT); // show detailed info about one issue only

// Print the header.
admin_externalpage_setup('reportup1stats', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', 'report_up1stats'));

$url = "$CFG->wwwroot/report/up1stats/index.php";


echo "<h3>Connecteurs Annuaire / Groupes</h3>\n";
$table = new html_table();
$table->head = array('Items', 'Nb');
$table->data = report_up1stats_generic();
echo html_writer::table($table);

echo "<h3>Cohortes</h3>\n";
$table = new html_table();
$table->head = array('Motif', 'Nb');
$table->data = report_up1stats_cohorts();
echo html_writer::table($table);

echo "<h3>Cohortes - top 10</h3>\n";
$table = new html_table();
$table->head = array('Effectif', 'Nom', 'Id');
$table->data = report_up1stats_cohorts_top(10);
echo html_writer::table($table);

echo "<h3>Last synchronizations</h3>\n";
$table = new html_table();
$table->head = array('Reference', 'Begin', 'End');
$table->data = report_up1stats_sync();
echo html_writer::table($table);


/*  $table->head  = array($strissue, $strstatus, $strdesc, $strconfig);
    $table->size  = array('30%', '10%', '50%', '10%' );
    $table->align = array('left', 'left', 'left', 'left');
    $table->attributes = array('class'=>'scurityreporttable generaltable');
    $table->data  = array();
    $table->data[] = $row;
*/

echo $OUTPUT->footer();
