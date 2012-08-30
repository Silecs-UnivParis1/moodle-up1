<?php

/**
 * Settings and links
 *
 * @package    report
 * @subpackage rofstats
 * @copyright  2012 Silecs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('reportrofstats', get_string('pluginname', 'report_rofstats'), "$CFG->wwwroot/report/rofstats/index.php",'report/rofstats:view'));

// no report settings
$settings = null;
