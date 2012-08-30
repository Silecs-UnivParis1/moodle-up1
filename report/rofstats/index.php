<?php

/**
 * ROF Statistics
 *
 * @package    report
 * @subpackage rofstats
 * @copyright  2012 Silecs
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



    $table = new html_table();
/*    $table->head  = array($strissue, $strstatus, $strdesc, $strconfig);
    $table->size  = array('30%', '10%', '50%', '10%' );
    $table->align = array('left', 'left', 'left', 'left');
    $table->attributes = array('class'=>'scurityreporttable generaltable');
    $table->data  = array();

    $table->data[] = $row;
*/

    echo html_writer::table($table);

    // echo $OUTPUT->box($result->details, 'generalbox boxwidthnormal boxaligncenter'); // TODO: add proper css

    // echo $OUTPUT->continue_button($url);

    echo html_writer::table($table);


echo $OUTPUT->footer();
