<?php
/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');

class course_wizard_step2_form extends moodleform {

    function definition() {
       global  $SESSION;

        $mform    = $this->_form;

        $editoroptions = $this->_customdata['editoroptions'];
        $courseconfig = get_config('moodlecourse');

        $bockhelpE2 = get_string('bockhelpE2', 'local_crswizard');
        $mform->addElement('html', html_writer::tag('div', $bockhelpE2, array('class' => 'fitem')));

/// form definition with new course defaults
//--------------------------------------------------------------------------------
        $mform->addElement('header','categorie', get_string('categoryblock', 'local_crswizard'));

        $mform->addElement('select', 'category', '', wizard_get_mydisplaylist(),
            array('class' => 'transformIntoSubselects cache'));
        if (isset($SESSION->wizard['form_step2']['category'])) {
			 $mform->setConstant('category', $SESSION->wizard['form_step2']['category']);
		}
        if (isset($SESSION->wizard['form_step2']['erreurs']['category'])) {
            $mform->addElement('html', html_writer::tag('div', $SESSION->wizard['form_step2']['erreurs']['category'], array('class' => 'required')));
 	    }

        $mform->addElement('header','general', get_string('generalinfoblock', 'local_crswizard'));

        $mform->addElement('text','fullname', get_string('fullnamecourse', 'local_crswizard'),'maxlength="254" size="50"');
        //$mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);
        if (isset($SESSION->wizard['form_step2']['fullname'])) {
            $mform->setConstant('fullname', $SESSION->wizard['form_step2']['fullname']);
 	    }

        $mform->addElement('text', 'shortname', get_string('shortnamecourse', 'local_crswizard'), 'maxlength="100" size="20"');
        //$mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_MULTILANG);
        if (isset($SESSION->wizard['form_step2']['shortname'])) {
            $mform->setConstant('shortname', $SESSION->wizard['form_step2']['shortname']);
 	    }
        if (isset($SESSION->wizard['form_step2']['erreurs']['shortname'])) {
            $mform->addElement('html', html_writer::tag('div', $SESSION->wizard['form_step2']['erreurs']['shortname'], array('class' => 'required')));
 	    }

        $mform->addElement('editor','summary_editor', get_string('coursesummary', 'local_crswizard'), null, $editoroptions);
        //$mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);
        if (isset($SESSION->wizard['form_step2']['summary_editor'])) {
            $mform->setConstant('summary_editor', $SESSION->wizard['form_step2']['summary_editor']);
 	    }

        $mform->addElement('header','parametre', get_string('coursesettingsblock', 'local_crswizard'));

        $coursesettingshelp = get_string('coursesettingshelp', 'local_crswizard');
        $mform->addElement('html', html_writer::tag('div', $coursesettingshelp, array('class' => 'fitem')));

        $mform->addElement('date_selector', 'startdate', get_string('coursestartdate', 'local_crswizard'));
       // $mform->addHelpButton('startdate', 'startdate');
        if (isset($SESSION->wizard['form_step2']['startdate'])) {
			$date = $SESSION->wizard['form_step2']['startdate'];
			$mform->setDefault('startdate', mktime(0, 0, 0, $date['month'], $date['day'], $date['year']));
		} else {
            $mform->setDefault('startdate', time());
        }

        $datefermeture = 'up1datefermeture';
        $label_up1datefermeture = get_custom_info_field_label($datefermeture);
        $mform->addElement('date_selector', $datefermeture, $label_up1datefermeture);
        if (isset($SESSION->wizard['form_step2'][$datefermeture])) {
			$date = $SESSION->wizard['form_step2'][$datefermeture];
			$mform->setDefault($datefermeture, mktime(0, 0, 0, $date['month'], $date['day'], $date['year']));
		} else {
            $mform->setDefault($datefermeture, time());
        }

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
        $mform->setConstant('defaultgroupingid', 0);

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
        $buttonarray[] = &$mform->createElement('submit', 'stepgo_1', get_string('previousstage', 'local_crswizard'),
			array('onclick'=>'skipClientValidation = true; return true;'));
        $buttonarray[] = &$mform->createElement('submit', 'stepgo_3', get_string('nextstage', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}
