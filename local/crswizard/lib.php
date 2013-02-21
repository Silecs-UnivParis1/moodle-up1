<?php
/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/libaccess.php');

// doc https://moodle.org/mod/forum/discuss.php?d=170325#yui_3_7_3_2_1359043225921_310
function crswizard_extends_navigation(global_navigation $navigation) {
    global $USER, $PAGE;

    $permcreator = wizard_has_permission('creator', $USER->id);
    $permvalidator = wizard_has_permission('validator', $USER->id);
    $permassistant = false;
    $context = $PAGE->context;
    if ($context->contextlevel == 50 && $context->instanceid != 1) {
        $permassistant = wizard_update_has_permission($context->instanceid, $USER->id);
    }

    if ($permcreator || $permvalidator) {
        $node1 = $navigation->add('Assistant création de cours');
        if ($permcreator) {
            $node2 = $node1->add('Création', new moodle_url('/local/crswizard/index.php'));
        }
        if ($permvalidator) {
            $node3 = $node1->add('Approbation', new moodle_url('/local/course_validated/index.php'));
        }
        if ($permassistant) {
            $node3 = $node1->add('Paramétrage', new moodle_url('/local/crswizard/update/index.php',
                array('id' => $context->instanceid)));
        }
    } elseif ($permassistant) {
        $node1 = $navigation->add('Assistant création de cours');
        $node2 = $node1->add('Paramétrage', new moodle_url('/local/crswizard/update/index.php',
                array('id' => $context->instanceid)));
    }
}
