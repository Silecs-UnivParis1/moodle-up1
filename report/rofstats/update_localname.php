<?php

/**
 * @package    report
 * @subpackage rofstats
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//defined('MOODLE_INTERNAL') || die();
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/roftools/roflib.php'); // to get ROF data

global $DB, $USER;
require_login();
$rofid = required_param('rofid', PARAM_ALPHANUMEXT); //
$localname = optional_param('localname', null, PARAM_RAW); //

$table = rof_get_table($rofid);
list($record, $top) = rof_get_record($rofid);

$record->localname = $localname;
$record->timemodified = time();

//var_dump($record);
//die();
$DB->update_record($table, $record, false);

$url = new moodle_url('/report/rofstats/view.php', array('rofid' => $rofid));
redirect($url);
