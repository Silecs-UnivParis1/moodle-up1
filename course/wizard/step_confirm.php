<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/custominfo/lib.php');

class course_wizard_step_confirm extends moodleform {

    function definition() {
        global $USER, $DB, $SESSION;

        $tabfreeze = array();
        $mform    = $this->_form;

        $mform->addElement('header','confirmation', 'Confirmation');

        $mform->addElement('html', html_writer::tag('p', 'Votre demande d\'ouverture d\'espace de cours va être transmise aux modérateurs de la plateforme :'));
        $mform->addElement('html', html_writer::tag('p', '[Prénom Nom du modérateur et/ou membres du service TICE]'));
        $mform->addElement('textarea', 'remarques', 'Vos remarques ou questions concernant cet espace de cours', array('rows'=>15, 'cols'=>80));
        $mform->setType('content', PARAM_RAW);

        $user_name = $USER->firstname . ' '. $USER->lastname;
        $mform->addElement('header','resume', 'Récapitulatif de la demande');
        $mform->addElement('text', 'user_name', 'Nom du demandeur', 'maxlength="40" size="20", disabled="disabled"');
        $mform->setConstant('user_name', $user_name);
        $tabfreeze[] = 'user_name';
        $mform->addElement('date_selector', 'requestdate', 'Date de la demande de création');
        $mform->setDefault('requestdate', time());
        $tabfreeze[] = 'requestdate';

        $idcat = $SESSION->wizard['form_step2']['category'];
        $displaylist = array();
        $parentlist = array();
        make_categories_list($displaylist, $parentlist);
        $mform->addElement('select', 'category', get_string('category'), $displaylist);
        $mform->setConstant('category',$idcat);
        $tabfreeze[] = 'category';

        $fullname = $SESSION->wizard['form_step2']['fullname'];
        $mform->addElement('text','fullname', get_string('fullnamecourse'),'maxlength="254" size="50"');
        $mform->setConstant('fullname', $fullname);
        $tabfreeze[] = 'fullname';

        $shortname = $SESSION->wizard['form_step2']['shortname'];
        $mform->addElement('text', 'shortname', get_string('shortnamecourse'), 'maxlength="100" size="20"');
        $mform->setConstant('shortname', $shortname);
        $tabfreeze[] = 'shortname';

        $htmlsummary = '<div class="fitemtitle"><div class="fstaticlabel"><label>'
            . get_string('coursesummary') . '</label></div></div>'
            . '<div class="felement fstatic">' . $SESSION->wizard['form_step2']['summary_editor']['text'] . '</div>';
        $mform->addElement('html', html_writer::tag('div', $htmlsummary, array('class' => 'fitem')));

        $date = $SESSION->wizard['form_step2']['startdate'];
		$startdate = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
		$mform->addElement('date_selector', 'startdate', get_string('startdate'));
		$mform->setConstant('startdate', $startdate);
        $tabfreeze[] = 'startdate';

        //--------------------------------------------------------------------------------
        if (isset($SESSION->wizard['idcourse'])) {
		    $idcourse = (int) $SESSION->wizard['idcourse'];
            $custominfo_data = custominfo_data::type('course');
            $cinfos = $custominfo_data->get_record($idcourse);

            foreach ($cinfos as $label=>$info) {
			    $htmlinfo = '<div class="fitemtitle"><div class="fstaticlabel"><label>'
                    . $label . '</label></div></div>'
                    . '<div class="felement fstatic">' . $info . '</div>';
                $mform->addElement('html', html_writer::tag('div', $htmlinfo, array('class' => 'fitem')));
		    }
		}
//--------------------------------------------------------------------------------

        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 7);

        $urlCategory = new moodle_url('/course/category.php', array('id' => $idcat, 'edit' => 'on' ));
        $messagehtml = '<div>Ce message concerne la demande de création de cours '. $fullname . ' ( ' . $shortname . ' )'
            .' faite par ' . $user_name . '.</div><div>Vous pouvez valider ou supprimer ce cours : '
            . html_writer::link($urlCategory, $urlCategory)
            . '</div>';
        $message = 'Ce message concerne la demande de création de cours '. $fullname . ' ( ' . $shortname . ' )'
            .' faite par ' . $user_name . '. Vous pouvez valider ou supprimer ce cours : ' . $urlCategory;
        $mform->addElement('hidden', 'messagehtml', null);
        $mform->setType('messagehtml', PARAM_RAW);
        $mform->setConstant('messagehtml', $messagehtml);

        $mform->addElement('hidden', 'message', null);
        $mform->setType('message', PARAM_RAW);
        $mform->setConstant('message', $message);

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'stepgo_8', 'Terminer');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $mform->hardFreeze($tabfreeze);
    }

}
