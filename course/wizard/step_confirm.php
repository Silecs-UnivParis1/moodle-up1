<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

class course_wizard_step_confirm extends moodleform {

    function definition() {
        global $USER, $DB, $SESSION;

        $tabfreeze = array();
        $mform    = $this->_form;

        $mform->addElement('header','confirmation', 'Confirmation');

        $mform->addElement('html', html_writer::tag('p', 'Votre demande d\'ouverture d\'espace de cours va être transmise aux modérateurs de la plateforme :'));
        $mform->addElement('html', html_writer::tag('p', '[Prénom Nom et/ou membres du service TICE]'));
        $mform->addElement('textarea', 'remarques', 'Vos remarques ou questions concernant cet espace de cours', array('rows'=>15, 'cols'=>80));
        $mform->setType('content', PARAM_RAW);

        $mform->addElement('header','resume', 'Récapitulatif de la demande');
        $mform->addElement('text', 'user_name', 'Nom du demandeur', 'maxlength="40" size="20", disabled="disabled"');
        $mform->setConstant('user_name', $USER->firstname . ' '. $USER->lastname);
        $tabfreeze[] = 'user_name';
        $mform->addElement('date_selector', 'requestdate', 'Date de la demande de création');
        $mform->setDefault('requestdate', time());
        $tabfreeze[] = 'requestdate';

        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 7);

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'stepgo_8', 'Terminer');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $mform->hardFreeze($tabfreeze);
    }

}
