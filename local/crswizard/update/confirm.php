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
require_once($CFG->libdir . '/custominfo/lib.php');
require_once(__DIR__ . '/../lib_wizard.php');

class course_wizard_confirm extends moodleform {

    function definition() {
        global $USER, $DB, $SESSION, $OUTPUT;

        $myconfig = new my_elements_config();
        $mform = $this->_form;

        $mform->addElement('header', 'resume', get_string('upsummaryof', 'local_crswizard'));

        $displaylist = array();
        $parentlist = array();
        make_categories_list($displaylist, $parentlist);
        $form2 = $SESSION->wizard['form_step2'];
        if (isset($form2['rattachement1']) ) {
            $idratt1 = $form2['rattachement1'];
            $mform->addElement('text', 'category',  get_string('categoryblockE3', 'local_crswizard') . ' : ', 'size="100"');
            $mform->setConstant('category' , $displaylist[$idratt1] . ' / ' . $form2['fullname']);
        } else {
            $mform->addElement('select', 'category', get_string('categoryblockE3', 'local_crswizard') . ' : ', $displaylist);
        }

        if (!empty($SESSION->wizard['form_step3']['rattachements'])) {
            $paths = wizard_get_myComposantelist($form2['category'], true);
            $first = true;
            foreach ($SESSION->wizard['form_step3']['rattachements'] as $pathid) {
                $select = $mform->createElement('text', "rattachements$pathid",
                    ($first? get_string('labelE7ratt2', 'local_crswizard') : ''), 'size="100"');
                $select->setValue($paths[$pathid]);
                $mform->addElement($select);
                $first = false;
            }
        }

        // rattachement secondaire - cas 2 + hybride
        if (isset($form2['rattachement2'])) {
            $rof2 = $form2['rattachement2'];
            if(count($rof2)) {
                $racine = '';
                if ($SESSION->wizard['wizardcase'] == 2) {
                    $racine = $displaylist[$form2['category']];
                } elseif ($SESSION->wizard['wizardcase'] == 3) {
                    $tabcategories = get_list_category($form2['category']);
                    $racine = $tabcategories[0] . ' / ' . $tabcategories[1];
                }
                $htmlrof2 = '<div class="fitem"><div class="fitemtitle">'
                    . '<div class="fstaticlabel"><label>'
                    . get_string('labelE7ratt2', 'local_crswizard')
                    . '</label></div></div>';
                foreach ($rof2 as $chemin) {
                    $htmlrof2 .= '<div class="felement fstatic">' . $racine . ' / ' . $chemin . '</div>';
                }
                $htmlrof2 .= '</div>';
                $mform->addElement('html', $htmlrof2);
            }
        }

        $mform->addElement('text', 'fullname', get_string('fullnamecourse', 'local_crswizard'), 'maxlength="254" size="100"');

        $mform->addElement('text', 'shortname', get_string('shortnamecourse', 'local_crswizard'), 'maxlength="100" size="40"');

        /** @todo display the summary correctly, with Moodle's conversion functions */
        $htmlsummary = '<div class="fitemtitle"><div class="fstaticlabel"><label>'
                . get_string('coursesummary', 'local_crswizard') . '</label></div></div>'
                . '<div class="felement fstatic">' . $form2['summary_editor']['text'] . '</div>';
        $mform->addElement('html', html_writer::tag('div', $htmlsummary, array('class' => 'fitem')));

        $mform->addElement('date_selector', 'startdate', get_string('coursestartdate', 'local_crswizard'));

        $mform->addElement('date_selector', 'up1datefermeture', get_string('up1datefermeture', 'local_crswizard'));

        if (!empty($SESSION->wizard['form_step5']['all-cohorts'])) {
            $groupsbyrole = $SESSION->wizard['form_step5']['all-cohorts'];
            $mform->addElement('header', 'groups', get_string('cohorts', 'local_crswizard'));
            $labels = $myconfig->role_cohort;
            foreach ($groupsbyrole as $role => $groups) {
                $label = $role;
                if (isset($labels[$role])) {
                    $label = get_string($labels[$role], 'local_crswizard');
                }
                $first = true;
                foreach ($groups as $id => $group) {
                    $mform->addElement('text', 'cohort' . $id, ($first ? $label . ' : ' : ''));
                    $mform->setConstant('cohort' . $id, $group->name . ' — ' . "{$group->size} inscrits");
                    $first = false;
                }
            }
        }

        /** @todo Do not set the values here, share the code that parses the forms data */
        if (isset($SESSION->wizard['form_step6'])) {
            $form6 = $SESSION->wizard['form_step6'];
            $clefs = wizard_list_clef($form6);
            if (count($clefs)) {
                $mform->addElement('header', 'clefs', get_string('enrolkey', 'local_crswizard'));
                foreach ($clefs as $type => $clef) {
                    $mform->addElement('html', html_writer::tag('h4', $type . ' : '));
                    $c = $clef['code'];
                    $mform->addElement('text', 'valeur' . $c, get_string('enrolkey', 'local_crswizard') . ' : ');
                    $mform->setConstant('valeur' . $c, $clef['password']);
                    if (isset($clef['enrolstartdate']) && $clef['enrolstartdate'] != 0) {
                        $mform->addElement('date_selector', 'enrolstartdate' . $c, get_string('enrolstartdate', 'enrol_self') . ' : ');
                        $mform->setConstant('enrolstartdate' . $c, $clef['enrolstartdate']);
                    }
                    if (isset($clef['enrolenddate']) && $clef['enrolenddate'] != 0) {
                        $mform->addElement('date_selector', 'enrolenddate' . $c, get_string('enrolenddate', 'enrol_self') . ' : ');
                        $mform->setConstant('enrolenddate' . $c, $clef['enrolenddate']);
                    }
                }
            }
        }

        //--------------------------------------------------------------------------------
      /**  if (isset($SESSION->wizard['idcourse'])) {
            $idcourse = (int) $SESSION->wizard['idcourse'];
            $custominfo_data = custominfo_data::type('course');
            $cinfos = $custominfo_data->get_record($idcourse);

            foreach ($cinfos as $label => $info) {
                $htmlinfo = '<div class="fitemtitle"><div class="fstaticlabel"><label>'
                        . $label . '</label></div></div>'
                        . '<div class="felement fstatic">' . $info . '</div>';
                $mform->addElement('html', html_writer::tag('div', $htmlinfo, array('class' => 'fitem')));
            }
        }**/
//--------------------------------------------------------------------------------

        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 7);

        $buttonarray = array();
        $buttonarray[] = $mform->createElement(
            'link', 'previousstage', null,
            new moodle_url($SESSION->wizard['wizardurl'], array('stepin' => 6)),
            get_string('previousstage', 'local_crswizard'), array('class' => 'previousstage'));
        $buttonarray[] = $mform->createElement('submit', 'stepgo_8', get_string('upsavechanges', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', null, false);
        $mform->closeHeaderBefore('buttonar');

        $mform->hardFreezeAllVisibleExcept(array('remarques', 'buttonar'));
    }

}
