<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage cohortsyncup1
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2014052001;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2012061700;        // Requires this Moodle version
$plugin->component = 'local_cohortsyncup1';       // Full name of the plugin (used for diagnostics)

$plugin->cron      = 0;
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = 'TODO';

$plugin->dependencies = array(
    'auth_ldapup1' => 2013101700,
);