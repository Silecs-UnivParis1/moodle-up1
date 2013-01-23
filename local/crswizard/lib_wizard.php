<?php
/* @var $DB moodle_database */
require_once("$CFG->dirroot/report/rofstats/roflib.php");


/**
 * Reconstruit le tableau de chemins (période/é/c/niveau) pour le plugin jquery select-into-subselects.js
 * @return array
 * */
function wizard_get_mydisplaylist() {
    $displaylist = array();
    $parentlist = array();
    make_categories_list($displaylist, $parentlist); // separator ' / ' is hardcoded into Moodle
    $myconfig = new my_elements_config();
    $mydisplaylist = array(" Sélectionner la période / Sélectionner l'établissement / Sélectionner la composante / Sélectionner le type de diplôme");

    foreach ($displaylist as $id => $label) {
        if (array_key_exists($id, $parentlist) && count($parentlist[$id]) == 3) {
            $mydisplaylist[$id] = $label;
        }
    }
    return $mydisplaylist;
}

/**
 * Reconstruit le tableau de chemins (période/établissement) pour le plugin jquery select-into-subselects.js
 * hack de la fonction wizard_get_mydisplaylist()
 * @todo limiter établissement à Paris 1
 * @return array
 * */
function wizard_get_catlevel2() {
    $displaylist = array();
    $parentlist = array();
    make_categories_list($displaylist, $parentlist); // separator ' / ' is hardcoded into Moodle
    $mydisplaylist = array(' - / - ');

    foreach ($displaylist as $id => $label) {
        if (array_key_exists($id, $parentlist) && count($parentlist[$id]) == 1) {
            $mydisplaylist[$id] = $label;
        }
    }
    return $mydisplaylist;
}

/**
 * Returns the list of the names of the ancestor categories, including the target.
 * @global moodle_database $DB
 * @param integer $idcategory
 * @return array
 */
function get_list_category($idcategory) {
    global $DB;
    $selected = $DB->get_record('course_categories', array('id' => $idcategory));
    $tabidpath = explode('/', $selected->path);
    if (count($tabidpath) < 4) {
        throw new Exception("Wrong category with path {$selected->path}");
    }
    $tabcategory = array();
    /**
     * @todo Fetch all names in one call to $DB->get_records_menu()
     */
    foreach ($tabidpath as $id) {
        if ($id) {
            $name = $DB->get_field('course_categories', 'name', array('id' => $id));
            if ($name) {
                $tabcategory[] = $name;
            }
        }
    }
    return $tabcategory;
}

function send_course_request($message, $messagehtml) {
    global $DB, $USER;

    $eventdata = new object();
    $eventdata->component = 'moodle';
    $eventdata->name = 'courserequested';
    $eventdata->userfrom = $USER;
    $eventdata->subject = '[CourseWizardRequest]'; //** @todo get_string()
    $eventdata->fullmessageformat = FORMAT_PLAIN;   // text format
    $eventdata->fullmessage = $message;
    $eventdata->fullmessagehtml = '';   //$messagehtml;
    $eventdata->smallmessage = $message; // USED BY DEFAULT !
    // documentation : http://docs.moodle.org/dev/Messaging_2.0#Message_dispatching
    /** envoi aux supervalidateurs
    $systemcontext = get_context_instance(CONTEXT_SYSTEM);
    $supervalidators = get_users_by_capability($systemcontext, 'local/crswizard:supervalidator');
    foreach ($supervalidators as $userto) {
        $eventdata->userto = $userto;
        $res = message_send($eventdata);
        if (!$res) {
            // @todo Handle messaging errors
        }
    }
    **/
    // copie au demandeur
    $eventdata->userto = $USER;
    $res = message_send($eventdata);
}

function myenrol_cohort($courseid, $tabGroup) {
    global $DB, $CFG;
    if ($courseid == SITEID) {
        throw new coding_exception('Invalid request to add enrol instance to frontpage.');
    }
    $error = array();
    $enrol = 'cohort';
    $status = 0;   //ENROL_INSTANCE_ENABLED
    $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));

    foreach ($tabGroup as $role => $groupes) {
        $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
        foreach ($groupes as $idgroup) {
            $cohort = $DB->get_record('cohort', array('idnumber' => $idgroup));
            if ($cohort) {
                if (!$DB->record_exists('enrol', array('enrol' => $enrol, 'courseid' => $courseid, 'customint1' => $cohort->id))) {
                    $instance = new stdClass();
                    $instance->enrol = $enrol;
                    $instance->status = $status;
                    $instance->courseid = $courseid;
                    $instance->customint1 = $cohort->id;
                    $instance->roleid = $roleid;
                    $instance->enrolstartdate = 0;
                    $instance->enrolenddate = 0;
                    $instance->timemodified = time();
                    $instance->timecreated = $instance->timemodified;
                    $instance->sortorder = $DB->get_field('enrol', 'COALESCE(MAX(sortorder), -1) + 1', array('courseid' => $courseid));
                    $DB->insert_record('enrol', $instance);
                }
            } else {
                $error[] = 'groupe "' . $idgroup . '" n\'existe pas dans la base';
            }
        }
    }

    require_once("$CFG->dirroot/enrol/cohort/locallib.php");
    enrol_cohort_sync($courseid);
    return $error;
}

function affiche_error_enrolcohort($erreurs) {
    $message = '';
    $message .= '<div><h3>Messages </h3>';
    $message .= '<p>Des erreurs sont survenues lors de l\'inscription des groupes :</p><ul>';
    foreach ($erreurs as $e) {
        $message .= '<li>' . $e . '</li>';
    }
    $message .= '</ul></div>';
    return $message;
}

function wizard_navigation($stepin) {
    global $SESSION;
    $SESSION->wizard['navigation']['stepin'] = $stepin;
    $SESSION->wizard['navigation']['suite'] = $stepin + 1;
    $SESSION->wizard['navigation']['retour'] = $stepin - 1;
}

/**
 * renvoie les rôles permis pour une inscription
 * @param $labels array
 * @return array object role
 */
function wizard_role($labels) {
    global $DB;
    $roles = array();
    foreach ($labels as $key => $label) {
        $sql = "SELECT * FROM {role} WHERE "
                . "shortname = ?";
        $record = $DB->get_record_sql($sql, array($key));
        $roles[] = array(
            'shortname' => $record->shortname,
            'name' => $record->name,
            'id' => $record->id
        );
    }
    return $roles;
}

/**
 * Inscrit des utilisateurs à un cours sous le rôle sélectionné
 * @param int $courseid identifiant du cours
 * @param array $tabUsers array[rolename]=>array(iduser)
 */
function myenrol_teacher($courseid, $tabUsers) {
    global $DB, $CFG;
    require_once("$CFG->dirroot/lib/enrollib.php");
    if ($courseid == SITEID) {
        throw new coding_exception('Invalid request to add enrol instance to frontpage.');
    }
    foreach ($tabUsers as $role => $users) {
        $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
        foreach ($users as $user) {
            $userid = $DB->get_field('user', 'id', array('username' => $user));
            if ($userid) {
                enrol_try_internal_enrol($courseid, $userid, $roleid);
            }
        }
    }
}

/**
 * Construit le tableau des groupes sélectionnés
 * @return array
 */
function wizard_get_enrolement_cohorts() {
    global $DB, $SESSION;

    if (!isset($SESSION->wizard['form_step5']['group'])) {
        return false;
    }

    $list = array();
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_cohort;
    $roles = wizard_role($labels);
    $form5g = $SESSION->wizard['form_step5']['group'];

    foreach ($roles as $r) {
        $code = $r['shortname'];
        if (array_key_exists($code, $form5g)) {
            foreach ($form5g[$code] as $g) {
                $group = $DB->get_record('cohort', array('idnumber' => $g));
                if ($group) {
                    $size = $DB->count_records('cohort_members', array('cohortid' => $group->id));
                    $group->size = $size;
                    $list[$code][$group->idnumber] = $group;
                }
            }
        }
    }
    return $list;
}

/**
 * Construit le tableau des enseignants sélectionnés
 * @return array
 */
function wizard_get_enrolement_users() {
    global $DB, $SESSION;

    if (!isset($SESSION->wizard['form_step4']['user'])) {
        return false;
    }

    $list = array();
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_teachers;
    $roles = wizard_role($labels);
    $form4u = $SESSION->wizard['form_step4']['user'];
    foreach ($roles as $r) {
        $code = $r['shortname'];
        if (array_key_exists($code, $form4u)) {
            foreach ($form4u[$code] as $u) {
                $user = $DB->get_record('user', array('username' => $u));
                if ($user) {
                    $list[$code][$user->username] = $user;
                }
            }
        }
    }
    return $list;
}

/**
 * Enrole par défaut l'utilisateur comme teacher à son cours
 * @return array
 */
function wizard_enrolement_user() {
    global $DB, $SESSION, $USER;
    $list = array();
    $code = 'editingteacher';
    $user = $DB->get_record('user', array('username' => $USER->username));
    $list[$code][$user->username] = $user;
    return $list;
}

/**
 * Construit le tableau des validateurs sélectionnés
 * @return array
 */
function wizard_get_validators() {
    global $DB, $SESSION;

    if (!isset($SESSION->wizard['form_step3']['user'])) {
        return false;
    }

    $list = array();
    $form3v = $SESSION->wizard['form_step3']['user'];
    foreach ($form3v as $u) {
        $user = $DB->get_record('user', array('username' => $u));
        if ($user) {
            $list[$user->username] = $user;
        }
    }
    return $list;
}

/**
 * Construit le tableau des objets pédagogiques du rof sélectionnés
 * @return array
 */
function wizard_get_rof() {
    global $DB, $SESSION;
    if (!isset($SESSION->wizard['form_step2']['item'])) {
        return false;
    }
    $list = array();
    $formRof = array();
    $rofPath = array();
    $formRof = $SESSION->wizard['form_step2']['item'];
    $rofPath = $SESSION->wizard['form_step2']['path'];
    foreach ($formRof as $key => $rof) {
        foreach ($rof as $r) {
            $list[$r]['nature'] = $key;
            if (array_key_exists($r, $rofPath)) {
                $list[$r]['path'] = $rofPath[$r];
            }
            $tabSource = '';
            if (substr($r, 0, 5) == 'UP1-P') {
                $tabSource = 'rof_program';
            } else {
                $tabSource = 'rof_course';
            }
            $object = $DB->get_record($tabSource, array('rofid' => $r));
            if ($object) {
                $list[$r]['object'] = $object;
            }
        }
    }
    return $list;
}

/*
 * construit la liste des groupes sélectionnés encodée en json
 * @return string
 */
function wizard_preselected_cohort() {
    global $SESSION;
    if (empty($SESSION->wizard['form_step5']['all-cohorts'])) {
        return '[]';
    }
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_cohort;
    $liste = array();
    foreach ($SESSION->wizard['form_step5']['all-cohorts'] as $role => $groups) {
        $labelrole = '';
        if (isset($labels[$role])) {
            $labelrole = "<span>" . get_string($labels[$role], 'local_crswizard') . "</span> : ";
        }
        foreach ($groups as $id => $group) {
            $desc = '';
            if (isset($group->size) && $group->size) {
                $desc = '<div>(' . $group->size . ' inscrits)</div>';
            }
            $liste[] = array(
                "label" => $labelrole . '<b>' . $group->name . '</b>' . $desc,
                "value" => $id,
                "fieldName" => "group[$role]",
            );
        }
    }
    return json_encode($liste);
}

/*
 * construit la liste des enseignants sélectionnés encodée en json
 * @return string
 */
function wizard_preselected_users() {
    global $SESSION;
    if (!isset($SESSION->wizard['form_step4']['all-users'])) {
        return '[]';
    }
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_teachers;
    $liste = array();
    if (!empty($SESSION->wizard['form_step4']['all-users'])) {
        foreach ($SESSION->wizard['form_step4']['all-users'] as $role => $users) {
            $labelrole = '';
            if (isset($labels[$role])) {
                $labelrole = ' (' . get_string($labels[$role], 'local_crswizard') . ')';
            }

            foreach ($users as $id => $user) {
                $liste[] = array(
                    "label" => fullname($user) . $labelrole,
                    "value" => $id,
                    "fieldName" => "user[$role]",
                );
            }
        }
    }
    return json_encode($liste);
}

/*
 * construit la liste des objets pédagogiques du rof sélectionnés encodée en json
 * @return string
 */
function wizard_preselected_rof() {
    global $SESSION;
    if (!isset($SESSION->wizard['form_step2']['all-rof'])) {
        return '[]';
    }
    $liste = array();
    if (!empty($SESSION->wizard['form_step2']['all-rof'])) {
        foreach ($SESSION->wizard['form_step2']['all-rof'] as $rofid => $rof) {
            $object = $rof['object'];
            $liste[] = array(
                    "label" => $object->name,
                    "value" => $rofid,
                    "path" => $rof['path'],
                    "nature" => $rof['nature'],
                );
        }
    }
    return json_encode($liste);
}

/*
 * construit la liste des validateurs sélectionnés encodée en json
 * @return string
 */
function wizard_preselected_validators() {
    global $SESSION;
    if (!isset($SESSION->wizard['form_step3']['all-validators'])) {
        return '[]';
    }
    $liste = array();
    if (!empty($SESSION->wizard['form_step3']['all-validators'])) {
        foreach ($SESSION->wizard['form_step3']['all-validators'] as $id => $user) {
            $liste[] = array(
                "label" => fullname($user),
                "value" => $id,
            );
        }
    }
    return json_encode($liste);
}

function wizard_list_clef() {
    global $SESSION;
    $list = array();
    $tabCle = array('u' => 'Etudiante', 'v' => 'Visiteur');

    if (isset($SESSION->wizard['form_step6'])) {
        $form6 = $SESSION->wizard['form_step6'];

        foreach ($tabCle as $c => $type) {
            $password = 'password' . $c;
            $enrolstartdate = 'enrolstartdate' . $c;
            $enrolenddate = 'enrolenddate' . $c;
            if (isset($form6[$password])) {
                $pass = trim($form6[$password]);
                if ($pass != '') {
                    $list[$type]['code'] = $c;
                    $list[$type]['password'] = $pass;
                    if (isset($form6[$enrolstartdate])) {
                        $list[$type]['enrolstartdate'] = $form6[$enrolstartdate];
                    }
                    if (isset($form6[$enrolenddate])) {
                        $list[$type]['enrolenddate'] = $form6[$enrolenddate];
                    }
                }
            }
        }
    }
    return $list;
}

/**
 * Renvoie le nom du Course custom fields de nom abrégé $shortname
 * @param string $shortname nom abrégé du champ
 * @return string nom du champ
 */
function get_custom_info_field_label($shortname) {
    global $DB;
    return $DB->get_field('custom_info_field', 'name', array('objectname' => 'course', 'shortname' => $shortname));
}

/**
 * renvoie l'idendifiant d' l'user sélectionné comme validateur ou chaine vide
 * @return string
 */
function wizard_get_approbateurpropid() {
    global $SESSION;
    $approbateurpropid = '';
    if (isset($SESSION->wizard['form_step3']['all-validators']) && !empty($SESSION->wizard['form_step3']['all-validators'])) {
        foreach ($SESSION->wizard['form_step3']['all-validators'] as $id => $user) {
            $approbateurpropid = $user->id . ';';
        }
        $approbateurpropid = substr($approbateurpropid, 0, -1);
    }
    return $approbateurpropid;
}

/**
 * Returns a unique course idnumber, by appending a serialnumber (-01, -02 ...) to code/rofid
 * takes in account the content of the course table
 * @global moodle_database $DB
 * @param string $rofid
 * @return string new idnumber to be used in course creation
 */
function wizard_rofid_to_idnumber($rofid) {
    global $DB;

    $code = rof_get_code_or_rofid($rofid);
    $sql = "SELECT idnumber FROM {course} c WHERE idnumber LIKE '" . $code . "%'";
    $res = $DB->get_fieldset_sql($sql);
    if ($res ) {
        $serials = array_map('__get_serialnumber', $res);
        return $code .'-'. sprintf('%02d', (1 + max($serials)));
    }
    return $code . '-01';
}

function __get_serialnumber($idnumber) {
    if ( preg_match ('/^.*-(\d+)$/', $idnumber, $match)) {
        return (integer)$match[1];
    }
    return 0;
}

/**
 * Calcule idcat Moodle et identifiant cours partir d'un identifiant rof
 * @param array() $form2
 * @return array() $rof1 - idcat, apogee et idnumber
 */
function wizard_prepare_rattachement_rof_moodle($form2) {
    $rof1 = array();
    if (isset($form2['item']) && count($form2['item'])) {
        $allrof = $form2['item'];
        if (isset($allrof['p']) && count($allrof['p'])) {
            $rofid = $allrof['p'][0];
            $rof1['rofid'] = $rofid;
            if (isset($form2['path']) && array_key_exists($rofid, $form2['path'])) {
                $rofpath = $form2['path'][$rofid];
                $tabpath = explode('_', $rofpath);
                $rof1['idcat'] = rofpath_to_category($tabpath);
            }
            $rof1['apogee'] = rof_get_code_or_rofid($rofid);
            $rof1['idnumber'] = wizard_rofid_to_idnumber($rofid);
        }
    }
    return $rof1;
}

class core_wizard {
    private $formdata;
    private $user;
    private $mydata;
    public $course;

    public function __construct($formdata, $user) {
        $this->formdata = $formdata;
        $this->user = $user;
    }

    public function create_course_to_validate() {
        // créer cours
        $mydata = $this->prepare_course_to_validate();
        $course = create_course($mydata);
        add_to_log($course->id, 'crswizard', 'create', 'view.php?id='.$course->id, 'previous (ID '.$course->id.')');
        $this->course = $course;

        // on supprime les enrols par défaut
        $this->delete_default_enrol_course($course->id);
        // save custom fields data
        $mydata->id = $course->id;
        $custominfo_data = custominfo_data::type('course');
        $cleandata = $this->customfields_wash($mydata);
        $custominfo_data->save_data($cleandata);

        $this->update_session($course->id);
        //! @todo tester si le cours existe bien ?
        //$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
        // inscrire des enseignants
        if (isset($this->formdata['form_step4']['user']) && count($this->formdata['form_step4']['user'])) {
            $tabUser = $this->formdata['form_step4']['user'];
            myenrol_teacher($course->id, $tabUser);
        }

        // inscrire des cohortes
        if (isset($this->formdata['form_step5']['group']) && count($this->formdata['form_step5']['group'])) {
            $tabGroup = $this->formdata['form_step5']['group'];
            $erreurs = myenrol_cohort($course->id, $tabGroup);
            if (count($erreurs)) {
                $this->formdata['form_step5']['cohorterreur'] = $erreurs;
                return affiche_error_enrolcohort($erreurs);
            }
        } else {
            // inscrire des clefs
            $clefs = wizard_list_clef();
            if (count($clefs)) {
                $this->myenrol_clef($course->id, $clefs);
            }
        }
        $this->mydata = $mydata;
        return '';
    }

    public function get_messages() {
        $urlCategory = new moodle_url('/course/category.php', array('id' => $this->course->category, 'edit' => 'on'));
        $messagehtml = '<div>Ce message concerne la demande de création de cours ' . $this->course->fullname
                . ' ( ' . $this->course->shortname . ' )'
                . ' faite par ' . fullname($this->user) . '.</div><div>Vous pouvez valider ou supprimer ce cours : '
                . html_writer::link($urlCategory, $urlCategory)
                . '</div>';
        $message = 'Ce message concerne la demande de création de cours "' . $this->course->fullname
                . ' ( ' . $this->course->shortname . ' )"'
                . ' faite par ' . fullname($this->user) . '.';
        return array("text" => $message, "html" => $messagehtml);
    }

    private function update_session($courseid) {
        global $SESSION;
        $SESSION->wizard['idcourse'] = $courseid;
        $SESSION->wizard['idenrolment'] = 'manual';
    }

    /**
     * Returns an object with properties derived from the forms data.
     * @return object
     */
    public function prepare_course_to_validate() {
        $mydata = (object) array_merge($this->formdata['form_step2'], $this->formdata['form_step3']);

        // profile_field obligatoire pour page course_validate
        $mydata->profile_field_up1approbateureffid = '';
        $mydata->profile_field_up1rofname = '';
        $mydata->profile_field_up1niveaulmda = '';
        $mydata->profile_field_up1diplome = '';

        // on est dans le cas 2
        if (isset($this->formdata['wizardcase']) && $this->formdata['wizardcase']=='2') {
            $form2 = $this->formdata['form_step2'];
            $rof1 = wizard_prepare_rattachement_rof_moodle($form2);
            if ( array_key_exists('idcat', $rof1) && $rof1['idcat'] != false) {
                $mydata->category = $rof1['idcat'];
            }
            $mydata->shortname = $rof1['apogee'] . ' - '
                . $this->formdata['form_step2']['complement'];
            $mydata->idnumber = $rof1['idnumber'];

            // metadonnee de rof1
            $mdrof1 = rof_get_metadata($rof1['rofid']);
            foreach ($mdrof1 as $category => $data) {
                if (count($data)) {
                    foreach($data as $label => $value) {
                        $champ = 'profile_field_'.$label;
                        $mydata->$champ = $value;
                    }
                }
            }

        } else { // cas 3
            $tabcategories = get_list_category($this->formdata['form_step2']['category']);
        }

        $mydata->summary = $this->formdata['form_step2']['summary_editor']['text'];
        $mydata->summaryformat = $this->formdata['form_step2']['summary_editor']['format'];
        //$mydata->startdate = $this->formdata['form_step2']['startdate'];

        //step3 custominfo_field
        // compoante
        if (isset($mydata->profile_field_up1composante)) {
            $up1composante = trim($mydata->profile_field_up1composante);
            if ($up1composante != '' && substr($up1composante, -1) != ';') {
                $mydata->profile_field_up1composante .= ';';
            }
            $mydata->profile_field_up1composante .= trim($tabcategories[2]);
        }
        // niveau
        if (isset($mydata->profile_field_up1niveau)) {
            $up1niveau = trim($mydata->profile_field_up1niveau);
            if ($up1niveau != '' && substr($up1niveau, -1) != ';') {
                $mydata->profile_field_up1niveau .= ';';
            }
            $mydata->profile_field_up1niveau .= trim($tabcategories[3]);
        }

        // cours doit être validé
        $mydata->profile_field_up1avalider = 1;
        $mydata->profile_field_up1datevalid = 0;
        $mydata->profile_field_up1datedemande = time();
        $mydata->profile_field_up1demandeurid = $this->user->id;
        $mydata->profile_field_up1approbateurpropid = wizard_get_approbateurpropid();
        //$mydata->profile_field_up1datefermeture = $this->formdata['form_step2']['up1datefermeture'];

        return $mydata;
    }

    // supprime les méthodes d'inscriptions guest et self
    private function delete_default_enrol_course($courseid) {
        global $DB;
        $DB->delete_records('enrol', array('courseid' => $courseid, 'enrol' => 'self'));
        $DB->delete_records('enrol', array('courseid' => $courseid, 'enrol' => 'guest'));
    }

    private function myenrol_clef($courseid, $tabClefs) {
        global $DB;
        if ($courseid == SITEID) {
            throw new coding_exception('Invalid request to add enrol instance to frontpage.');
        }
        // traitement des données
        foreach ($tabClefs as $type => $tabClef) {
            $name = 'clef ' . $type;

            if ($type == 'Etudiante') {
                $enrol = 'self';
                $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
            } elseif ($type == 'Visiteur') {
                $enrol = 'guest';
                $roleid = 0;
            }
            $status = 0;   //0 pour auto-inscription
            if (isset($tabClef['enrolstartdate'])) {
                $date = $tabClef['enrolstartdate'];
                $startdate = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
            } else {
                $startdate = 0;
            }
            if (isset($tabClef['enrolenddate'])) {
                $date = $tabClef['enrolenddate'];
                $enddate = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);
            } else {
                $enddate = 0;
            }

            $instance = new stdClass();
            $instance->enrol = $enrol;
            $instance->status = $status;
            $instance->courseid = $courseid;
            $instance->roleid = $roleid;
            $instance->name = $name;
            $instance->password = $tabClef['password'];
            $instance->customint1 = 0; // clef d'inscription groupe ?
            $instance->customint2 = 0;
            $instance->customint3 = 0;
            $instance->customint4 = 0; // envoie d'un message

            $instance->enrolstartdate = $startdate;
            $instance->enrolenddate = $enddate;
            $instance->timemodified = time();
            $instance->timecreated = $instance->timemodified;
            $instance->sortorder = $DB->get_field('enrol', 'COALESCE(MAX(sortorder), -1) + 1', array('courseid' => $courseid));
            $DB->insert_record('enrol', $instance);
        }
    }

    /**
     * Convertit les champs custom_info_field de type datetime en timestamp
     * @param object $data
     * @return object $data
     */
    private function customfields_wash($data) {
        global $DB;

        $fields = $DB->get_records('custom_info_field', array('objectname' => 'course', 'datatype' => 'datetime'));
        if ($fields) {
            foreach ($fields as $field) {
                $nomc = 'profile_field_' . $field->shortname;
                if (isset($data->$nomc) && is_array($data->$nomc)) {
                    $tab = $data->$nomc;
                    $hour = 0;
                    $minute = 0;
                    if (isset($tab['hour'])) {
                        $hour = $tab['hour'];
                    }
                    if (isset($tab['minute'])) {
                        $minute = $tab['minute'];
                    }
                    $data->$nomc = mktime($hour, $minute, 0, $tab['month'], $tab['day'], $tab['year']);
                }
            }
        }
        return $data;
    }

    public function get_recapitulatif_demande() {
    $myconfig = new my_elements_config();

    $mg = '';
    $mg .= "\n" . '---------------------' . "\n";
    $mg .= 'Récapitulatif de la demande';
    $mg .= "\n" . '---------------------' . "\n";
    $mg .= get_string('username', 'local_crswizard') . fullname($this->user) . "\n";
    $mg .= get_string('userlogin', 'local_crswizard') . $this->user->username . "\n";
    $mg .= get_string('courserequestdate', 'local_crswizard') . date('d-m-Y') . "\n";

    $wizardcase = $this->formdata['wizardcase'];

    // categorie
    $displaylist = array();
    $parentlist = array();
    make_categories_list($displaylist, $parentlist);

    $form2 = $this->formdata['form_step2']; // ou $SESSION->wizard['form_step2']
  //  $idcat = $form2['category'];
    $idcat = $this->mydata->category;
    $mg .= get_string('category') . ' : ' . $displaylist[$idcat] . "\n";
    $mg .= get_string('fullnamecourse', 'local_crswizard') . $form2['fullname'] . "\n";
    $mg .= get_string('shortnamecourse', 'local_crswizard') . $this->mydata->shortname . "\n";

    $mg .= get_string('coursestartdate', 'local_crswizard') . date('d-m-Y', $form2['startdate']) . "\n";
    $mg .= get_string('up1datefermeture', 'local_crswizard') . date('d-m-Y', $form2['up1datefermeture']) . "\n";

    // validateur si il y a lieu
    $form3 =  $this->formdata['form_step3']; // ou $SESSION->wizard['form_step3']
    if (isset($form3['all-validators']) && !empty($form3['all-validators'])) {
        $allvalidators = $form3['all-validators'];
        $mg .= get_string('selectedvalidator', 'local_crswizard'). "\n";
        foreach ($allvalidators as $id => $validator) {
            $mg .= '    ' . fullname($validator) . "\n";
        }
    }

    // liste des enseignants :
    $form4 =  $this->formdata['form_step4']; // ou $SESSION->wizard['form_step4']
    $mg .= get_string('teachers', 'local_crswizard'). "\n";
    if (isset($form4['all-users']) && is_array($form4['all-users'])) {
            $allusers = $form4['all-users'];
            $labels = $myconfig->role_teachers;
            foreach ($allusers as $role => $users) {
                $label = $role;
                if (isset($labels[$role])) {
                    $label = get_string($labels[$role], 'local_crswizard');
                }
                $first = true;
                foreach ($users as $id => $user) {
                    $mg .= '    ' . ($first ? $label . ' : ': '') . fullname($user) . "\n";
                    $first = false;
                }
            }
        } else {
            $mg .= '    Aucun' . "\n";
        }

        // liste des groupes
        $form5 =  $this->formdata['form_step5']; // ou $SESSION->wizard['form_step5']
        $mg .= get_string('cohorts', 'local_crswizard'). "\n";
        if (!empty($form5['all-cohorts'])) {
            $groupsbyrole = $form5['all-cohorts'];
            $labels = $myconfig->role_cohort;
            foreach ($groupsbyrole as $role => $groups) {
                $label = $role;
                if (isset($labels[$role])) {
                    $label = get_string($labels[$role], 'local_crswizard');
                }
                $first = true;
                foreach ($groups as $id => $group) {
                    $mg .= '    ' . ($first ? $label . ' : ' : '') . $group->name . " ({$group->size})" . "\n";
                    $first = false;
                }
            }
        } else {
            $mg .= '    Aucun' . "\n";
        }

        // clefs
        $mg .= get_string('enrolkey', 'local_crswizard') . "\n";
        $clefs = wizard_list_clef();
        if (count($clefs)) {
            foreach ($clefs as $type => $clef) {
                $mg .= '    ' . $type . ' : ' . $clef['password'] . "\n";
                $mg .= '    ' . get_string('enrolstartdate', 'enrol_self') . ' : ';
                if (isset($clef['enrolstartdate']) && $clef['enrolstartdate'] != 0) {
                    $mg .= date('d-m-Y', $clef['enrolstartdate']);
                } else {
                    $mg .= 'incative';
                }
                $mg .= "\n";
                $mg .= '    ' . get_string('enrolenddate', 'enrol_self') . ' : ';
                if (isset($clef['enrolenddate']) && $clef['enrolenddate'] != 0) {
                    $mg .= date('d-m-Y', $clef['enrolenddate']);
                } else {
                    $mg .= 'incative';
                }
                $mg .= "\n";
            }
        } else {
            $mg .= '    Aucune' . "\n";
        }
    return $mg;
    }
}

class my_elements_config {
    public $categorie_cours = array(
        'Période', 'Etablissement', 'Composante : ', 'Type de diplôme : '
    );
    public $role_teachers = array(
        'editingteacher' => 'editingteacher',
        'teacher' => 'noeditingteacher'
    );
    public $role_cohort = array(
        'student' => 'student',
        'guest' => 'guest'
    );
    public $categorie_deph = array(
        '1' => 'Période',
        '2' => 'Etablissement',
        '3' => 'Composante',
        '4' => 'Niveau',
    );
}
