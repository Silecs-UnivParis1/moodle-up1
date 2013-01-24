<?php
/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* @var $ADMIN admin_root */

defined('MOODLE_INTERNAL') || die;

if (has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
    $settings = new admin_settingpage('local_crswizard', 'Assistant de création de cours');
    $ADMIN->add('localplugins', $settings);

    $cohorts_cap_creator = new admin_setting_configtext(
            'cohorts_cap_creator',
            'Cohortes autorisées en création',
            'Liste des cohortes autorisées à utiliser l\'assistant : identifiants séparés par des espaces.',
            '',
            PARAM_NOTAGS);
    $cohorts_cap_creator->plugin = 'local_crswizard';
    $settings->add($cohorts_cap_creator);

    $cohorts_cap_validator = new admin_setting_configtext(
            'cohorts_cap_validator',
            'Cohortes autorisées en approbation',
            'Liste des cohortes autorisées à approuver les cours soumis via l\'assistant : identifiants séparés par des espaces.',
            '',
            PARAM_NOTAGS);
    $cohorts_cap_validator->plugin = 'local_crswizard';
    $settings->add($cohorts_cap_validator);

    $email_notification_course_creation = new admin_setting_configtext(
            'email_notification_course_creation',
            'Email de notification (création cours)',
            'Adresse Email recevant une notification à la création d\'un cours : une adresse email.',
            '',
            PARAM_NOTAGS);
    $email_notification_course_creation->plugin = 'local_crswizard';
    $settings->add($email_notification_course_creation);
}
