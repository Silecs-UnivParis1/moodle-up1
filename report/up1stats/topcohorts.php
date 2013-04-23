<?php

/**
 * UP1 Users Statistics - Top NN cohorts page
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

$howmany = optional_param('number', 50, PARAM_INT);
$prefix = optional_param('prefix', '', PARAM_ALPHANUMEXT);

// Print the header.
admin_externalpage_setup('reportup1stats', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();

echo $OUTPUT->heading('Cohortes - top '.$howmany);

$url = "$CFG->wwwroot/report/up1stats/index.php";

echo "<h3>Cohortes - top $howmany</h3>\n";
$table = new html_table();
$table->head = array('Effectif', 'Nom', 'Id');
if (empty($prefix)) {
    $table->data = report_up1stats_cohorts_top($howmany, false);
} else {
    $table->data = report_up1stats_cohorts_top($howmany, $prefix);
}
echo html_writer::table($table);

echo $OUTPUT->footer();
