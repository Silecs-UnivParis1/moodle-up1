<?php
/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');

if (has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
    $settings = new admin_settingpage('local_crswizard', 'Assistant de création de cours');
    $ADMIN->add('localplugins', $settings);
    $settings->add(new admin_setting_configtext(
            'crswizard_cohorts_cap_creator',
            'Cohortes autorisées en création',
            'Liste des cohortes autorisées à utiliser l\'assistant : identifiants séparés par des espaces.',
            '',
            PARAM_NOTAGS));

    $settings->add(new admin_setting_configtext(
            'crswizard_cohorts_cap_validator',
            'Cohortes autorisées en approbation',
            'Liste des cohortes autorisées à approuver les cours soumis via l\'assistant : identifiants séparés par des espaces.',
            '',
            PARAM_NOTAGS));
}
