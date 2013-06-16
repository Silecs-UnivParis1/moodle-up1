<?php
/**
 * Light synopsis block.
 *
 * @package    block
 * @subpackage lightsynopsis
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2013061602;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2012061700;        // Requires this Moodle version
$plugin->component = 'block_lightsynopsis';       // Full name of the plugin (used for diagnostics)

$plugin->cron      = 0;
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = 'TODO';

$plugin->dependencies = array(
    'coursereport_synopsis' => 2012072000,
);