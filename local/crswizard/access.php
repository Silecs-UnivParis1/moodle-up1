<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/* Cf documentation
http://docs.moodle.org/dev/NEWMODULE_Adding_capabilities
http://docs.moodle.org/dev/Hardening_new_Roles_system
 */

$capabilities = array(
    'local/crswizard:creator' => array(
        'riskbitmask'  => 0,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => array(
            'student'        => CAP_PREVENT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'          => CAP_ALLOW
        )
    ),

    'local/crswizard:validator' => array(
        'riskbitmask'  => 0,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => array(
            'student'        => CAP_PREVENT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'          => CAP_ALLOW
        )
    ),

    'local/crswizard:supervalidator' => array(
        'riskbitmask'  => 0,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => array(
            'student'        => CAP_PREVENT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'          => CAP_ALLOW
        )
    ),
    'local/crswizard:localsupervalidator' => array(
        'riskbitmask'  => 0,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes'   => array(
            'student'        => CAP_PREVENT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'          => CAP_ALLOW
        )
    ),

);

//** @todo localsupervalidator : est-ce la bonne méthode ? peut-on surcharger un nom avec 2 contextes ?