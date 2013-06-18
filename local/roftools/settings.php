<?php
/**
 * @package    local
 * @subpackage roftools
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/* @var $ADMIN admin_root */

if (has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {

    $settings = new admin_settingpage('local_roftools', 'Paramètres outils ROF');
    $ADMIN->add('localplugins', $settings);


    $settings->add(
            new admin_setting_heading('rofcatcreate', 'Création des catégories de cours issues du ROF', '')
            );    

    $rofyearname = new admin_setting_configtext(
            'rof_year_name',
            'Année',
            'Année universitaire pour la création des catégories de cours (ex. "Année 2013-2014").',
            'Année 2013-2014',
            PARAM_NOTAGS);
    $rofyearname->plugin = 'local_roftools';
    $settings->add($rofyearname);

    $rofyearcode = new admin_setting_configtext(
            'rof_year_code',
            'Code année',
            'Code année préfixé par la profondeur des catégories de cours (ex. "1:2013-2014").',
            '1:2013-2014',
            PARAM_NOTAGS);
    $rofyearcode->plugin = 'local_roftools';
    $settings->add($rofyearcode);


    $rofetabname = new admin_setting_configtext(
            'rof_etab_name',
            'Établissement',
            'Établissement pour la création des catégories de cours (ex. "Paris 1").',
            'Paris 1',
            PARAM_NOTAGS);
    $rofetabname->plugin = 'local_roftools';
    $settings->add($rofetabname);

    $rofetabcode = new admin_setting_configtext(
            'rof_etab_code',
            'Code établ.',
            'Code établissement préfixé par la profondeur des catégories de cours (ex. "2:UP1").',
            '2:UP1',
            PARAM_NOTAGS);
    $rofetabcode->plugin = 'local_roftools';
    $settings->add($rofetabcode);

}
