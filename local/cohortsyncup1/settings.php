<?php
/**
 * @package    local
 * @subpackage cohortsyncup1
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* @var $ADMIN admin_root */

defined('MOODLE_INTERNAL') || die;

if (has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
    $settings = new admin_settingpage('local_cohortsyncup1', 'Synchronisation des cohortes');
    $ADMIN->add('localplugins', $settings);

    $ws_allGroups  = new admin_setting_configtext(
            'ws_allGroups',
            'Webservice allGroups',
            'Url complète du webservice allGroups, ex. http://ticetest.univ-paris1.fr/wsgroups/allGroups',
            '',
            PARAM_NOTAGS);
    $ws_allGroups->plugin = 'local_cohortsyncup1';
    $settings->add($ws_allGroups);

    $ws_userGroupsAndRoles = new admin_setting_configtext(
            'ws_userGroupsAndRoles',
            'Webservice userGroupsAndRoles',
            'Url complète du webservice userGroupsAndRoles, ex. http://ticetest.univ-paris1.fr/wsgroups/userGroupsAndRoles',
            '',
            PARAM_NOTAGS);
    $ws_userGroupsAndRoles->plugin = 'local_cohortsyncup1';
    $settings->add($ws_userGroupsAndRoles);

}
