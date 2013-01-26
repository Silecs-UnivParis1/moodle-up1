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

class course_wizard_step2_rof_form extends moodleform {

    function definition() {
        global $OUTPUT, $SESSION;

        $mform = $this->_form;

        $editoroptions = $this->_customdata['editoroptions'];
        $courseconfig = get_config('moodlecourse');

        $bockhelpE2 = get_string('bockhelpE2Rof1', 'local_crswizard');
        $bockhelpE2 .= '&nbsp;«&nbsp;<span class="collapsed">&nbsp;+&nbsp;</span>&nbsp;»&nbsp;et&nbsp;«&nbsp;<span class="expanded">&nbsp;-&nbsp;&nbsp;</span>&nbsp;»&nbsp;';
        $bockhelpE2 .= get_string('bockhelpE2Rof2', 'local_crswizard');
        $mform->addElement('html', html_writer::tag('div', $bockhelpE2, array('class' => 'fitem')));

/// form definition with new course defaults
//--------------------------------------------------------------------------------
        $mform->addElement('header', 'categoryheader', get_string('categoryblockE2F', 'local_crswizard'));

        $default_cat = get_config('local_crswizard','cas2_default_etablissement');
        $mform->addElement(
                'select', 'category', '', wizard_get_catlevel2(),
                array(
                    'class' => 'transformIntoSubselects cache',
                    'data-labels' => '["Période :", "Établissement :"]'
                )
        );
        $mform->setDefault('category', $default_cat);

        $labelrof =  '<br/><div class="fitemtitle required mylabel"><label>Elément pédagogique : *</label></div>';
        $mform->addElement('html',  $labelrof);
        $mform->addElement('html', '<div id="mgerrorrof"></div>');

        $preselected = wizard_preselected_rof();
        $codeJ = '<script type="text/javascript">' . "\n"
            . '//<![CDATA['."\n"
            . 'jQuery(document).ready(function () {'
            . '$(\'#items-selected\').autocompleteRof({'
            . 'preSelected: '.$preselected
            .'});'
            . '});'
            . '//]]>'. "\n"
            . '</script>';

        // ajout du selecteur ROF
        $rofseleted = '<div class="by-widget"><h3>Rechercher un élément pédagogique</h3>'
            . '<div class="item-select"></div>'
            . '</div>'
            . '<div class="block-item-selected">'
            . '<h3>Éléments pédagogiques sélectionnés</h3>'
            . '<div id="items-selected">'
            . '<div id="items-selected1"><span>' . get_string('rofselected1', 'local_crswizard') . '</span></div>'
            . '<div id="items-selected2"><span>' . get_string('rofselected2', 'local_crswizard') . '</span></div>'
            . '</div>'
            . '</div>'
            . $codeJ;

        $mform->addElement('html', $rofseleted);

        $mform->addElement('header', 'general', get_string('generalinfoblock', 'local_crswizard'));
        $coursegeneralhelp = get_string('coursegeneralhelpRof', 'local_crswizard');
        $mform->addElement('html', html_writer::tag('div', $coursegeneralhelp, array('class' => 'fitem')));

        $labelname = '';
        $valcomplement = '';
        if (isset($SESSION->wizard['form_step2']['fullname'])) {
            $labelname = $SESSION->wizard['form_step2']['fullname'];
        }
        if (isset($SESSION->wizard['form_step2']['complement'])) {
            $valcomplement = $SESSION->wizard['form_step2']['complement'];
        }
        $htmlcn = '<div id="fgroup_id_coursename" class="fitem required fitem_fgroup">'
            . '<div class="fitemtitle">'
            . '<div class="fgrouplabel">'
            . '<label>' . get_string('fullnamecourse', 'local_crswizard') . ' * </label>'
            . '</div>'
            . '</div>'
            . '<fieldset class="felement fgroup">'
            . '<span id="fullnamelab">' . $labelname . '</span> - '
            . '<label class="accesshide" for="id_complement"> </label>'
            . '<input maxlength="254" size="50" name="complement" type="text" '
            . 'id="id_complement" value="' . $valcomplement . '">'
            . '</fieldset>'
            . '</div>';
        $mform->addElement('html',$htmlcn);

        $mform->addElement('hidden', 'fullname', null, array('id' => 'fullname'));
        $mform->setType('fullname', PARAM_MULTILANG);

        $mform->addElement('editor', 'summary_editor', get_string('coursesummary', 'local_crswizard'), null, $editoroptions);
        //$mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);

        $mform->addElement('header', 'parametre', get_string('coursesettingsblock', 'local_crswizard'));

        $coursesettingshelp = get_string('coursesettingshelp', 'local_crswizard');
        $mform->addElement('html', html_writer::tag('div', $coursesettingshelp, array('class' => 'fitem')));

        $mform->addElement('date_selector', 'startdate', get_string('coursestartdate', 'local_crswizard'));
        // $mform->addHelpButton('startdate', 'startdate');
        $mform->setDefault('startdate', time());

        $datefermeture = 'up1datefermeture';
        $mform->addElement('date_selector', $datefermeture, get_string('up1datefermeture', 'local_crswizard'));
        $mform->setDefault($datefermeture, time());

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

        $buttonarray = array();
        $buttonarray[] = $mform->createElement(
            'link', 'previousstage', null,
            new moodle_url('/local/crswizard/index.php', array('stepin' => 1)),
            get_string('previousstage', 'local_crswizard'), array('class' => 'previousstage'));
        $buttonarray[] = $mform->createElement('submit', 'stepgo_3', get_string('nextstage', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', null, false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($errors)) {
            //$this->validation_shortname($data['shortname'], $errors);
            $this->validation_category($data['category'], $errors);
        }
        return $errors;
    }

    private function validation_shortname($shortname, &$errors) {
        global $DB;

        $foundcourses = $DB->get_records('course', array('shortname' => $shortname));
        if ($foundcourses) {
            foreach ($foundcourses as $foundcourse) {
                $foundcoursenames[] = $foundcourse->fullname;
            }
            $foundcoursenamestring = implode(',', $foundcoursenames);
            $errors['shortname'] = get_string('shortnametaken', '', $foundcoursenamestring);
        }
        return $errors;
    }

    private function validation_category($idcategory, &$errors) {
        global $DB;

        $category = $DB->get_record('course_categories', array('id' => $idcategory));
        if ($category) {
            if ($category->depth < 1) {
                $errors['category'] = get_string('categoryerrormsg1', 'local_crswizard');
            }
        } else {
            $errors['category'] = get_string('categoryerrormsg2', 'local_crswizard');
        }
        return $errors;
    }
}
