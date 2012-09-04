<?php

/**
 * Settings and links
 *
 * @package    report
 * @subpackage up1stats
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports',
        new admin_externalpage('reportup1stats',
                get_string('pluginname', 'report_up1stats'),
                "$CFG->wwwroot/report/up1stats/index.php",
                'report/up1stats:view')
        );

// no report settings
$settings = null;
