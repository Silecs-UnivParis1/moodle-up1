<?php

/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');

class course_wizard_step_model extends moodleform {
    function definition() {
        global $OUTPUT, $SESSION;

        $mform = $this->_form;

        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 1);

        $mform->addElement('html', html_writer::tag('div', get_string('blocHelp1SModel', 'local_crswizard'), array('class' => 'fitem')));

        $mform->addElement('header', 'general', "Vous souhaitez créer un nouvel espace :");

        $mform->addElement('radio', 'modeletype', '', 'à partir du modèle par défaut', 0);


        $course_model_list = wizard_get_course_model_list();
        if (count($course_model_list)) {
            $m1array = array();
            $m1array[] = $mform->CreateElement('radio', 'modeletype', '', 'à partir du modèle', 'selm1');
            $m1array[] = $mform->CreateElement('select', 'selm1', '', $course_model_list);
            $mform->addGroup($m1array, 'm1array', "", array(' : ', ' '), false);
            $mform->disabledIf('selm1', 'modeletype', 'neq', 'selm1');
        }

        $course_list_teacher = wizard_get_course_list_teacher();
        if (count($course_list_teacher)) {
            $mform->addElement('radio', 'modeletype', '', 'par duplication et réinitialisation de l\'espace', 'selm2');
            $mform->addElement('select', 'selm2', '', $course_list_teacher,  array(
                    'class' => 'transformIntoSubselects boitex',
                ));
            $mform->disabledIf('selm2', 'modeletype', 'neq', 'selm2');
        }

        $buttonarray = array();
        $buttonarray[] = $mform->createElement(
            'link', 'previousstage', null,
            new moodle_url($SESSION->wizard['wizardurl'], array('stepin' => 0)),
            get_string('previousstage', 'local_crswizard'), array('class' => 'previousstage'));
        $buttonarray[] = $mform->createElement('submit', 'stepgo_2', get_string('nextstage', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', null, false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($errors)) {
            $this->validation_category($data, $errors);
        }
        return $errors;
    }

    private function validation_category($data, &$errors) {

        if ($data['modeletype'] == 'selm2' && ($data['selm2'] == 0) ) {
            $errors['selm2'] = 'Veuillez sélectionner une période et un cours';
        }
        return $errors;
    }
}
