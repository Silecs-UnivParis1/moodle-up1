<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');

class course_wizard_step2_form extends moodleform {

    function definition() {
        global $USER, $CFG, $DB;

        $mform    = $this->_form;

        $editoroptions = $this->_customdata['editoroptions'];
        $courseconfig = get_config('moodlecourse');


/// form definition with new course defaults
//--------------------------------------------------------------------------------
        $mform->addElement('header','categorie', 'Catégorie (rattachement principal de l\'espace de cours');

        $displaylist = array();
        $parentlist = array();
        make_categories_list($displaylist, $parentlist, 'moodle/course:create');
        $mform->addElement('select', 'category', get_string('category'), $displaylist);
        $mform->addHelpButton('category', 'category');

        $mform->addElement('header','general', 'Informations générales de l\'espace de cours');

        $mform->addElement('text','fullname', get_string('fullnamecourse'),'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);

        $mform->addElement('text', 'shortname', get_string('shortnamecourse'), 'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_MULTILANG);

        $mform->addElement('editor','summary_editor', get_string('coursesummary'), null, $editoroptions);
        $mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);

        $mform->addElement('header','parametre', 'Paramétrage de l\'espace de cours');

        $mform->addElement('date_selector', 'startdate', get_string('startdate'));
        $mform->addHelpButton('startdate', 'startdate');
        $mform->setDefault('startdate', time() + 3600 * 24);

        /**
         * liste des paramètres de cours ayant une valeur par défaut
         */

         // si demande de validation à 0
        $mform->addElement('hidden', 'visible', null);
        $mform->setType('visible', PARAM_INT);
        $mform->setConstant('visible', 0);

        $mform->addElement('hidden', 'format', null);
        $mform->setType('format', PARAM_ALPHANUM);
        $mform->setConstant('format', $courseconfig->format);

        $mform->addElement('hidden', 'coursedisplay', null);
        $mform->setType('coursedisplay', PARAM_INT);
        $mform->setConstant('coursedisplay', COURSE_DISPLAY_SINGLEPAGE);

        $mform->addElement('hidden', 'numsections', null);
        $mform->setType('numsections', PARAM_INT);
        $mform->setConstant('numsections', $courseconfig->numsections);

        $mform->addElement('hidden', 'hiddensections', null);
        $mform->setType('hiddensections', PARAM_INT);
        $mform->setConstant('hiddensections', $courseconfig->hiddensections);

        $mform->addElement('hidden', 'newsitemsnumber', null);
        $mform->setType('newsitemsnumber', PARAM_INT);
        $mform->setConstant('newsitemsnumber', $courseconfig->newsitemsnumber);

        $mform->addElement('hidden', 'newsitems', null);
        $mform->setType('newsitems', PARAM_INT);
        $mform->setConstant('newsitems', $courseconfig->newsitems);

        $mform->addElement('hidden', 'showgrades', null);
        $mform->setType('showgrades', PARAM_INT);
        $mform->setConstant('showgrades', $courseconfig->showgrades);

        $mform->addElement('hidden', 'showreports', null);
        $mform->setType('showreports', PARAM_INT);
        $mform->setConstant('showreports', $courseconfig->showreports);

        $mform->addElement('hidden', 'maxbytes', null);
        $mform->setType('maxbytes', PARAM_INT);
        $mform->setConstant('maxbytes', $courseconfig->maxbytes);

        $mform->addElement('hidden', 'groupmode', null);
        $mform->setType('groupmode', PARAM_INT);
        $mform->setConstant('groupmode', $courseconfig->groupmode);

        $mform->addElement('hidden', 'groupmodeforce', null);
        $mform->setType('groupmodeforce', PARAM_INT);
        $mform->setConstant('groupmodeforce', $courseconfig->groupmodeforce);

        $mform->addElement('hidden', 'defaultgroupingid', null);
        $mform->setType('defaultgroupingid', PARAM_INT);
        $mform->setConstant('defaultgroupingid', $courseconfig->defaultgroupingid);

        $mform->addElement('hidden', 'lang', null);
        $mform->setType('lang', PARAM_INT);
        $mform->setConstant('lang', $courseconfig->lang);

        // à supprimer ?
        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);
//--------------------------------------------------------------------------------
        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 2);

//--------------------------------------------------------------------------------

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'stepgo_1', 'étape précédente');
        $buttonarray[] = &$mform->createElement('submit', 'stepgo_3', 'étape suivante');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}
