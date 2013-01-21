<?php

/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/custominfo/lib.php');

class course_wizard_step3_form extends moodleform {

    protected $custominfo;

    function definition() {
        global $USER, $DB, $SESSION, $OUTPUT;

        $tabfreeze = array();
        $mform = $this->_form;

        $bockhelpE3 = get_string('bockhelpE3', 'local_crswizard');
        $mform->addElement('html', html_writer::tag('div', $bockhelpE3, array('class' => 'fitem')));

        $mform->addElement('header', 'general', get_string('categoryblockE3', 'local_crswizard'));

        $myconfig = new my_elements_config();

        // Next the customisable fields
        $this->custominfo = new custominfo_form_extension('course');

        if (isset($SESSION->wizard['form_step2']['category'])) {
            $idcat = (int) $SESSION->wizard['form_step2']['category'];
            $tabcategories = get_list_category($idcat);

            //Composante
            $type = strtolower($myconfig->categorie_cours[2]);
            $mform->addElement('text', $type, ucfirst($type), 'maxlength="40" size="20"');
            $mform->setConstant($type, $tabcategories[2]);
            $tabfreeze[] = $type;

            //Niveau
            $type = strtolower($myconfig->categorie_cours[3]);
            $mform->addElement('text', $type, ucfirst($type), 'maxlength="40" size="20"');
            $mform->setConstant($type, $tabcategories[3]);
            $tabfreeze[] = $type;

            $mform->addElement('header','autre_rattachement', get_string('categoryblockE3s1', 'local_crswizard'));

            $label = 'up1composante';
            $field = 'profile_field_' . $label;
            $mform->addElement('text', $field, get_string($label, 'local_crswizard'),'maxlength="254" size="50"');
            $mform->setType($field, PARAM_TEXT);

            $label = 'up1niveau';
            $field = 'profile_field_' . $label;
            $mform->addElement('text', $field, get_string($label, 'local_crswizard'), 'maxlength="254" size="50"');
            $mform->setType($field, PARAM_TEXT);
        }

        //*********************************************
        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 3);

//--------------------------------------------------------------------------------

        $mform->addElement('header', 'gestion', get_string('managecourseblock', 'local_crswizard'));
        $mform->addElement('text', 'user_name', get_string('username', 'local_crswizard'), 'maxlength="40" size="20", disabled="disabled"');
        $mform->setConstant('user_name', fullname($USER));
        $tabfreeze[] = 'user_name';

        $mform->addElement('text', 'user_login', get_string('userlogin', 'local_crswizard'),
			'maxlength="40" size="20", disabled="disabled"');
        $mform->setConstant('user_login', $USER->username);
        $tabfreeze[] = 'user_login';

        $mform->addElement('date_selector', 'requestdate', get_string('courserequestdate', 'local_crswizard'));
        $mform->setDefault('requestdate', time());
        $tabfreeze[] = 'requestdate';

        $mform->hardFreeze($tabfreeze);

//---------------------------------------------------------------------------------

        $buttonarray = array();
        $buttonarray[] = $mform->createElement(
            'link', 'previousstage', null,
            new moodle_url('/local/crswizard/index.php', array('stepin' => 2)),
            get_string('previousstage', 'local_crswizard'), array('class' => 'previousstage'));
        $buttonarray[] = $mform->createElement(
                'submit', 'stepgo_4', get_string('nextstage', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', null, false);
        $mform->closeHeaderBefore('buttonar');
    }

}
