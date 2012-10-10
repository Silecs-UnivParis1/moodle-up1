<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');

class course_wizard_step_cle extends moodleform {

    function definition() {
       global  $SESSION;

        $mform    = $this->_form;

//--------------------------------------------------------------------------------
		$message = 'Si vous n\'avez trouvé aucun groupe d\'utilisateurs '
			. 'étudiants correspondant à la permission d\'acces que vous souhaitez '
			. 'accorder à l\'espace de cours, vous avez la possibilité de communiquer '
			. 'à vos étudiants un code (appelé "clef d\'inscription") leur permettant de '
			. 's\'inscrire aux-mêmes à l\'espace de cours lors de leur premier accès';
		$messagecle = '<b>Attention : </b>Il faut renseigner le champ ' . get_string('password', 'enrol_self')
			. ' pour que la clef soit crée.';
		$mform->addElement('html', html_writer::tag('div', $message, array('class' => 'fitem')));

		$tabCle = array('u' =>'Clé d\'inscription pour le rôle "étudiants"',
			'v' => 'Clé d\'inscription pour le rôle "visiteurs"'
		);

		foreach ($tabCle as $c => $label) {

			$mform->addElement('header','general' . $c, $label);
			$mform->addElement('html', html_writer::tag('div', $messagecle, array('class' => 'fitem')));
			$mform->addElement('passwordunmask', 'password' . $c, get_string('password', 'enrol_self'));
			$mform->addHelpButton('password' . $c, 'password', 'enrol_self');
			if (isset($SESSION->wizard['form_step6']['password' . $c])) {
				$mform->setDefault('password' . $c, $SESSION->wizard['form_step6']['password' . $c]);
			}

			$mform->addElement('date_selector', 'enrolstartdate' . $c, get_string('enrolstartdate', 'enrol_self'), array('optional' => true));
			$mform->addHelpButton('enrolstartdate' . $c, 'enrolstartdate', 'enrol_self');
			if (isset($SESSION->wizard['form_step6']['enrolstartdate' . $c])) {
				$date = $SESSION->wizard['form_step6']['enrolstartdate' . $c];
				$mform->setDefault('enrolstartdate' . $c, mktime(0, 0, 0, $date['month'], $date['day'], $date['year']));
			} else {
				$mform->setDefault('enrolstartdate' . $c, time() + 3600 * 24);
			}

			$mform->addElement('date_selector', 'enrolenddate' . $c, get_string('enrolenddate', 'enrol_self'), array('optional' => true));
			$mform->addHelpButton('enrolenddate' . $c, 'enrolenddate', 'enrol_self');
			$mform->setDefault('enrolenddate' . $c, 0);
			if (isset($SESSION->wizard['form_step6']['enrolenddate' . $c])) {
				$date = $SESSION->wizard['form_step6']['enrolenddate' . $c];
				$mform->setDefault('enrolenddate' . $c, mktime(0, 0, 0, $date['month'], $date['day'], $date['year']));
			} else {
				$mform->setDefault('enrolenddate' . $c, 0);
			}
		}

//--------------------------------------------------------------------------------
        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 6);

//--------------------------------------------------------------------------------

        $buttonarray=array();
		$buttonarray[] = &$mform->createElement('submit', 'stepgo_5', 'Etape précédente');
        $buttonarray[] = &$mform->createElement('submit', 'stepgo_7', 'Etape suivante');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}
