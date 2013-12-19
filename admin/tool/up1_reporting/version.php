<?php

/**
 * Administrator reporting
 *
 * @package    tool
 * @subpackage up1_reporting
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2013121901;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2012061700;        // Requires this Moodle version
$plugin->component = 'tool_up1_reporting'; // Full name of the plugin (used for diagnostics)

$plugin->cron      = 0;
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = 'TODO';

$plugin->dependencies = array(
    'local_up1_courselist' => 2013071900,
);