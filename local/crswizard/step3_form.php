<?php
/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/custominfo/lib.php');

class course_wizard_step3_form extends moodleform {

    protected $custominfo;

    function definition() {
        global $USER, $DB, $SESSION;

        $tabfreeze = array();
        $mform    = $this->_form;

        $bockhelpE3 = get_string('bockhelpE3', 'local_crswizard');
        $mform->addElement('html', html_writer::tag('div', $bockhelpE3, array('class' => 'fitem')));

        $mform->addElement('header','general', get_string('categoryblockE3', 'local_crswizard'));

		$myconfig = new my_elements_config();

        // Next the customisable fields
        $this->custominfo = new custominfo_form_extension('course');

		if (isset($SESSION->wizard['form_step2']['category'])) {
			$idcat = (int) $SESSION->wizard['form_step2']['category'];
            $tabcategories = get_list_category($idcat);
            //Composante
            $type = $myconfig->categorie_cours[2];
            $nom = $tabcategories[2];
            $mform->addElement('text', $type, $type, 'maxlength="40" size="20", disabled="disabled"');
            $mform->setConstant($type, $nom);
            $tabfreeze[] = $type;

            $field = $DB->get_record('custom_info_field', array('objectname' => 'course', 'shortname' => 'up1composante'));
            $formfield = custominfo_field_factory('course', $field->datatype, $field->id);
            $formfield->edit_field($mform);

            //Niveau
            $type = $myconfig->categorie_cours[3];
            $nom = $tabcategories[3];
            $mform->addElement('text', $type, $type, 'maxlength="40" size="20", disabled="disabled"');
            $mform->setConstant($type, $nom);
            $tabfreeze[] = $type;

            $field = $DB->get_record('custom_info_field', array('objectname' => 'course', 'shortname' => 'up1niveau'));
            $formfield = custominfo_field_factory('course', $field->datatype, $field->id);
            $formfield->edit_field($mform);
		}

        // champs de la catégorie "Identification"
        $customcat = $this->custominfo->getCategories();
        $this->custominfo->setCategoriesByNames(array('Diplome'));
        $customDiplome = $this->custominfo->getFields();

        $tabDip = array();
        foreach ($customDiplome as $id => $objet) {
            $tabDip[$objet->shortname] = $objet;
        }
        $formfield = custominfo_field_factory('course', $tabDip['up1domaine']->datatype, $tabDip['up1domaine']->id);
        $formfield->edit_field($mform);

        $formfield = custominfo_field_factory('course', $tabDip['up1mention']->datatype, $tabDip['up1mention']->id);
        $formfield->edit_field($mform);

        $formfield = custominfo_field_factory('course', $tabDip['up1parcours']->datatype, $tabDip['up1parcours']->id);
        $formfield->edit_field($mform);

        $formfield = custominfo_field_factory('course', $tabDip['up1specialite']->datatype, $tabDip['up1specialite']->id);
        $formfield->edit_field($mform);

        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 3);

        $mform->hardFreeze($tabfreeze);

        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 3);

//--------------------------------------------------------------------------------

		$mform->addElement('header','gestion', get_string('managecourseblock', 'local_crswizard'));
        $mform->addElement('text', 'user_name', get_string('username', 'local_crswizard'),
			'maxlength="40" size="20", disabled="disabled"');
        $mform->setConstant('user_name', $USER->firstname . ' '. $USER->lastname);
        $tabfreeze[] = 'user_name';

        $mform->addElement('date_selector', 'requestdate',  get_string('courserequestdate', 'local_crswizard'));
        $mform->setDefault('requestdate', time());
        $tabfreeze[] = 'requestdate';
        $mform->hardFreeze($tabfreeze);

//---------------------------------------------------------------------------------

        $buttonarray=array();
        $buttonarray[] = $mform->createElement('submit', 'stepgo_2', get_string('previousstage', 'local_crswizard'));
        $buttonarray[] = $mform->createElement(
                'submit', 'stepgo_4', get_string('nextstage', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

}
