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

        $hybridattachment_permission = false;
        $idcourse = 1;
        if (isset($SESSION->wizard['idcourse'])) {
             $idcourse = $SESSION->wizard['idcourse'];
        }
        $hybridattachment_permission = wizard_has_hybridattachment_permission($idcourse, $USER->id);

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
            $valdiplome = 'Aucun';
            if (isset($tabcategories[3])) {
               $valdiplome = $tabcategories[3];
            }
            $mform->setConstant($type, $valdiplome);
            $tabfreeze[] = $type;

            $mform->addElement('header','autre_rattachement', get_string('categoryblockE3s1', 'local_crswizard'));
            $select = $mform->createElement(
                'select', 'rattachements', '', wizard_get_myComposantelist($idcat),
                array('class' => 'transformRattachements')
            );
            $select->setMultiple(true);
            $mform->addElement($select);

            if ($hybridattachment_permission) {
                $mform->addElement('header', 'rofheader', 'Rattachements au ROF');

                //Période
                $periode = strtolower($myconfig->categorie_cours[0]);
                $mform->addElement('text', $periode, ucfirst($periode), 'maxlength="40" size="20"');
                $mform->setConstant($periode, $tabcategories[0]);
                $tabfreeze[] = $periode;

                //Etablissement
                $etab = strtolower($myconfig->categorie_cours[1]);
                $mform->addElement('text', $etab, ucfirst($etab), 'maxlength="40" size="20"');
                $mform->setConstant($etab, $tabcategories[1]);
                $tabfreeze[] = $etab;

                $labelrof =  '<br/><div class="fitemtitle mylabel"><label>Elément pédagogique : </label></div>';
                $mform->addElement('html',  $labelrof);
                $mform->addElement('html', '<div id="mgerrorrof"></div>');
                $preselected = wizard_preselected_rof('form_step3');
                $codeJ = '<script type="text/javascript">' . "\n"
                    . '//<![CDATA['."\n"
                    . 'jQuery(document).ready(function () {'
                    . '$(\'#items-selected\').autocompleteRof({';
                $codeJ .= 'preSelected: '.$preselected
                    .'});'
                    . '});'
                    . '//]]>'. "\n"
                    . '</script>';
                // ajout du selecteur ROF
                $rofseleted = '<div class="by-widget"><h3>Rechercher un élément pédagogique</h3>'
                    . '<div class="item-select" id="choose-item-select"></div>'
                    . '</div>'
                    . '<div class="block-item-selected">'
                    . '<h3>Éléments pédagogiques sélectionnés</h3>'
                    . '<div id="items-selected">'
                    . '<div id="items-selected2"><span>' . get_string('rofselected2', 'local_crswizard') . '</span></div>'
                    .    '</div>'
                    .    '</div>'
                    . $codeJ;
                $mform->addElement('html', $rofseleted);
            } else {
                // si update
                if ($idcourse != 1 ) {
                    $form3 = $SESSION->wizard['init_course']['form_step3'];
                    if (isset($form3['rattachement2'])) {
                        $rof2 = $form3['rattachement2'];
                        if(count($rof2)) {
                            $racine = '';
                            $tabcategories = get_list_category($SESSION->wizard['form_step2']['category']);
                            $racine = $tabcategories[0] . ' / ' . $tabcategories[1];
                            $htmlrof2 = '<div class="fitem"><div class="fitemtitle">'
                                . '<div class="fstaticlabel"><label>'
                                . get_string('labelE7ratt2', 'local_crswizard')
                                . '</label></div></div>';
                            foreach ($rof2 as $chemin) {
                                $htmlrof2 .= '<div class="felement fstatic">' . $racine
                                    . ' / ' . $SESSION->wizard['form_step3']['all-rof'][$chemin]['chemin'] . '</div>';
                            }
                            $htmlrof2 .= '</div>';
                            $mform->addElement('html', $htmlrof2);
                        }
                    }
                }
            }
            // ajout métadonnée supp. indexation
            $mform->addElement('header', 'indexation', get_string('indexationE3', 'local_crswizard'));
            // Niveau année

            $mform->addElement('html', '<div>');

            $selectAnnee = $mform->createElement(
                'select', 'up1niveauannee', '', get_list_metadonnees('up1niveauannee'),
                array('class' => 'niveauanneeRattachements')
            );
            $selectAnnee->setMultiple(true);
            $mform->addElement($selectAnnee);
            $mform->addElement('html', '</div>');
            // Semestre
            $mform->addElement('html', '<div>');
            $selectSemestre = $mform->createElement(
                'select', 'up1semestre', '', get_list_metadonnees('up1semestre'),
                array('class' => 'semestreRattachements')
            );
            $selectSemestre->setMultiple(true);
            $mform->addElement($selectSemestre);

            $mform->addElement('html', '</div>');
            // Niveau
             $selectNiveau = $mform->createElement(
                'select', 'up1niveau', '', get_list_metadonnees('up1niveau'),
                array('class' => 'niveauRattachements')
            );
            $selectNiveau->setMultiple(true);
            $mform->addElement($selectNiveau);
        }

        //*********************************************
        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 3);

//--------------------------------------------------------------------------------

        $mform->addElement('header', 'gestion', get_string('managecourseblock', 'local_crswizard'));
        $mform->addElement('text', 'user_name', get_string('username', 'local_crswizard'), 'maxlength="40" size="20", disabled="disabled"');
        $tabfreeze[] = 'user_name';

        $mform->addElement('text', 'user_login', get_string('userlogin', 'local_crswizard'),
			'maxlength="40" size="20", disabled="disabled"');
        $tabfreeze[] = 'user_login';

        $mform->addElement('date_selector', 'requestdate', get_string('courserequestdate', 'local_crswizard'));
        $tabfreeze[] = 'requestdate';

        $mform->hardFreeze($tabfreeze);

//---------------------------------------------------------------------------------

        $buttonarray = array();
        $buttonarray[] = $mform->createElement(
            'link', 'previousstage', null,
            new moodle_url($SESSION->wizard['wizardurl'], array('stepin' => 2)),
            get_string('previousstage', 'local_crswizard'), array('class' => 'previousstage'));
        $buttonarray[] = $mform->createElement(
                'submit', 'stepgo_4', get_string('nextstage', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', null, false);
        $mform->closeHeaderBefore('buttonar');
    }

}
