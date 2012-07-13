<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');

class course_wizard_step3_form extends moodleform {
    function definition() {
        global $USER, $DB, $SESSION;

        $tabfreeze = array();
        $mform    = $this->_form;

        $mform->addElement('header','general', 'Rattachement de l\'espace de cours');
        $mform->addElement('text', 'niveau', 'Niveau', 'maxlength="40" size="20", disabled="disabled"');
		$mform->addElement('text', 'composante', 'Composante', 'maxlength="40" size="20", disabled="disabled"');
		if (isset($SESSION->wizard['form_step2']['category'])) {
			$idcat = (int) $SESSION->wizard['form_step2']['category'];
			$nameniveau = $DB->get_field_select('course_categories', 'name', "id = ?", array($idcat));
			$namecomposante = $DB->get_field_select('course_categories', 'name', "parent = ?", array($idcat));

			$mform->setConstant('niveau', $nameniveau);
			$mform->setConstant('composante', $namecomposante);
		}
		$tabfreeze[] = 'niveau';
		$tabfreeze[] = 'composante';

        $mform->addElement('header','gestion', 'Gestion de l\'espace de cours');
        $mform->addElement('text', 'user_name', 'Nom du demandeur', 'maxlength="40" size="20", disabled="disabled"');
        $mform->setConstant('user_name', $USER->firstname . ' '. $USER->lastname);
        $tabfreeze[] = 'user_name';

        $mform->addElement('date_selector', 'requestdate', 'Date de la demande de création');
        $mform->setDefault('requestdate', time());
        $tabfreeze[] = 'requestdate';


        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 3);

        $mform->hardFreeze($tabfreeze);

        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 3);


        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'stepgo_2', 'étape précédente');
        $buttonarray[] = &$mform->createElement('submit', 'stepgo_4', 'étape suivante');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}
