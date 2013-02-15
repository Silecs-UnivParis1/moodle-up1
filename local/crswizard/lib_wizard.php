<?php
/* @var $DB moodle_database */

require_once("$CFG->dirroot/local/roftools/roflib.php");

/**
 * Reconstruit le tableau de chemins (période/é/c/niveau) pour le plugin jquery select-into-subselects.js
 * @return array
 * */
function wizard_get_mydisplaylist() {
    $displaylist = array();
    $parentlist = array();
    make_categories_list($displaylist, $parentlist); // separator ' / ' is hardcoded into Moodle
    $mydisplaylist = array(" Sélectionner la période / Sélectionner l'établissement / Sélectionner la composante / Sélectionner le type de diplôme");

    $enfantlist = array();
    foreach ($parentlist as $id => $tabp) {
        if (count($tabp)>1) {
            foreach($tabp as $idp) {
                $enfantlist[$idp][]=$id;
            }
        }
    }

    foreach ($displaylist as $id => $label) {
        if (array_key_exists($id, $parentlist) &&   count($parentlist[$id]) > 1) {
            $depth = count($parentlist[$id]);
            if ($depth == 2) {
                if(!array_key_exists($id, $enfantlist)) {
                    $mydisplaylist[$id] = $label;
                }
            } else {
                $mydisplaylist[$id] = $label;
            }
        }
    }
    return $mydisplaylist;
}

/**
 * Reconstruit le tableau de chemins (période/établissement) pour le plugin jquery select-into-subselects.js
 * hack de la fonction wizard_get_mydisplaylist()
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
 * Reconstruit le tableau de chemins (composantes/diplômes) pour le plugin jquery select-into-subselects.js
 * hack de la fonction wizard_get_mydisplaylist()
 * @param $idcat identifiant de la catégorie diplôme sélectionné à l'étape précédente
 * @param bool $fullpath chemin complet des catégories
 * @return array
 * */
function wizard_get_myComposantelist($idcat, $fullpath=false) {
    global $DB;
    $displaylist = array();
    $parentlist = array();
    $category = $DB->get_record('course_categories', array('id' => $idcat));
    $tpath = explode('/', $category->path);
    $annee = $DB->get_field('course_categories', 'name', array('id' => $tpath[1]));
    $selected = $DB->get_record('course_categories', array('id' => $tpath[2]));
    make_categories_list($displaylist, $parentlist, '', 0, $selected); // separator ' / ' is hardcoded into Moodle

    $mydisplaylist = array(" Sélectionner la composante / Sélectionner le type de diplôme");
    foreach ($displaylist as $id => $label) {
        if ($id != $selected->id) {
            if ($fullpath) {
                $mydisplaylist[$id]  = $annee . ' / ' . $label;
            } else {
                $pos = strpos($label, '/');
                $mydisplaylist[$id] = substr($label, $pos+2);
            }
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

/**
 * Envoie un email à l'adresse mail spécifiée
 * @param string $email
 * @param string $subject,
 * @param string $message
 * @return false ou resultat de la fonction email_to_user()
 **/
function wizard_send_email($email, $subject, $message) {
    if (!isset($email) && empty($email)) {
        return false;
    }
    $supportuser = generate_email_supportuser();
    $user = new stdClass();
    $user->email = $email;
    return email_to_user($user, $supportuser, $subject, $message);
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

function wizard_unenrol_cohort($courseid, $tabGroup) {
    global $DB, $CFG;
    if ($courseid == SITEID) {
        throw new coding_exception('Invalid request to add enrol instance to frontpage.');
    }
    require_once("$CFG->dirroot/enrol/cohort/locallib.php");

    $enrol = 'cohort';
    $plugin_enrol = enrol_get_plugin($enrol);
    foreach ($tabGroup as $role => $groupes) {
        $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
        foreach ($groupes as $idgroup) {
            $cohort = $DB->get_record('cohort', array('idnumber' => $idgroup));
            if ($cohort) {
                $instance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => $enrol,
                    'roleid' => $roleid, 'customint1' => $cohort->id));
                if ($instance) {
                    $plugin_enrol->delete_instance($instance);
                }
            }
        }
    }
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
 * @param $labels array role_shortname => label
 * @return array object role
 */
function wizard_role($labels) {
    global $DB;
    $roles = array();
    $sql = "SELECT * FROM {role} WHERE shortname = ?";
    foreach (array_keys($labels) as $key) {
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
    global $DB, $USER;
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
            $labelrole = "<span>" . get_string($labels[$role], 'local_crswizard') . "</span>";
        }
        foreach ($groups as $id => $group) {
            $desc = '';
            if (isset($group->size) && $group->size) {
                $desc =  $group->size . ' inscrits';
            }
            $liste[] = array(
                "label" => $group->name . ' — ' . $desc . ' (' . $labelrole . ')',
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
                    "label" => fullname($user) . ' — ' . $user->username . $labelrole,
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
            $tabrof = rof_get_combined_path(explode('_', $rof['path']));
            $chemin = substr(rof_format_path($tabrof, 'name', false, ' > '), 3);
            $liste[] = array(
                    "label" => $object->name,
                    "value" => $rofid,
                    "path" => $rof['path'],
                    "nature" => $rof['nature'],
                    "chemin" => $chemin,
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
    $labelrole = ' (approbateur)';
    if (!empty($SESSION->wizard['form_step3']['all-validators'])) {
        foreach ($SESSION->wizard['form_step3']['all-validators'] as $id => $user) {
            $liste[] = array(
                "label" => fullname($user) . ' — ' . $user->username . $labelrole,
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
        foreach ($SESSION->wizard['form_step3']['all-validators'] as $user) {
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
    if (preg_match('/-(\d+)$/', $idnumber, $match)) {
        return (integer)$match[1];
    }
    return 0;
}

/**
 * Calcule idcat Moodle et identifiant cours partir d'un identifiant rof
 * @param array() $form2
 * @param bool $change
 * @return array() $rof1 - idcat, apogee et idnumber
 */
function wizard_prepare_rattachement_rof_moodle($form2, $change=false) {
    global $DB;
    $rof1 = array();
    if (isset($form2['item']) && count($form2['item'])) {
        $allrof = $form2['item'];
        if (isset($allrof['p']) && count($allrof['p'])) {
            $rofid = $allrof['p'][0];
            $rof1['rofid'] = $rofid;
            if (isset($form2['path']) && array_key_exists($rofid, $form2['path'])) {
                $rofpath = $form2['path'][$rofid];
                $tabpath = explode('_', $rofpath);
                $rof1['tabpath'] = $tabpath;
                $idcategory = rof_rofpath_to_category($tabpath);
                if ($idcategory) {
                    $rof1['idcat'] = $idcategory;
                    $category = $DB->get_record('course_categories', array('id' => $idcategory));
                    $rof1['up1niveaulmda'] = $category->name;
                    $rof1['up1composante'] = $DB->get_field('course_categories', 'name', array('id' => $category->parent));
                }
            }
            $rof1['apogee'] = rof_get_code_or_rofid($rofid);
            if ($change == false) {
                $rof1['idnumber'] = wizard_rofid_to_idnumber($rofid);
            }
        }
    }
    return $rof1;
}

/**
 * Retourne le rofid, rofpathid et rofname des rattachements secondaires
 * @param array() $form2
 * @return array() $rof2 - rofid, rofpathid et rofname
 */
function wizard_prepare_rattachement_second($form2) {
    $rof2 = array();
    if (isset($form2['item']) && count($form2['item'])) {
        $allrof = $form2['item'];
        if (isset($allrof['s']) && count($allrof['s'])) {
            foreach($allrof['s'] as $rofid) {
                $rof2['rofid'][] = $rofid;
                if (isset($form2['path']) && array_key_exists($rofid, $form2['path'])) {
                    $rofpath = $form2['path'][$rofid];
                    $path = strtr($rofpath, '_', '/');
                    $rof2['rofpathid'][] = $path;

                    $tabpath = explode('_', $rofpath);
                    $tabrof = rof_get_combined_path($tabpath);
                    $chemin = substr(rof_format_path($tabrof, 'name', false, ' / '), 3);
                    $rof2['rofchemin'][] = $chemin;
                }
                if (isset($form2['all-rof']) && array_key_exists($rofid, $form2['all-rof'])) {
                    $rofobjet =  $form2['all-rof'][$rofid]['object'];
                    $rof2['rofname'][] = $rofobjet->name;
                }
            }
        }
    }
    return $rof2;
}

function wizard_get_rattachement_fieldup1($tabcat, $tabcategories) {
   global $DB;
    $fieldup1 = array();
    $niveau = '';
    $composante = $tabcategories[2];
    if (isset($tabcategories[3])) {
        $niveau = $tabcategories[3];
    }
    if (count($tabcat)) {
        $listecat = implode(",", $tabcat);
        $sqlcatComp = "SELECT DISTINCT name FROM course_categories WHERE id IN (" . $listecat . ") AND depth=3";
        $catComp = array();
        $catComp = $DB->get_fieldset_sql($sqlcatComp);

        $sqlcatDip = "SELECT * FROM course_categories WHERE id IN (" . $listecat . ") AND depth=4";
        $catDip = $DB->get_records_sql($sqlcatDip);

        $tabnewcomp = array();
        $tabdip = array();
        foreach ($catDip as $dip) {
            if(!in_array($dip->parent, $tabcat)) {
                $tabnewcomp[] = $dip->parent;
            }
            if ($dip->name != $niveau) {
                 $tabdip[$dip->name] = $dip->name;
            }
        }
        foreach ($tabdip as $dip) {
            $niveau .= ';' . $dip;
        }

        $catnewComp = array();
        if (count($tabnewcomp)) {
            $listenewcat = implode(",", $tabnewcomp);
            $sqlcatComp = "SELECT DISTINCT name FROM course_categories WHERE id IN (" . $listenewcat . ") AND depth=3";
            $catnewComp = $DB->get_fieldset_sql($sqlcatComp);
        }

        $tabcres =  array_merge($catComp, $catnewComp);
        $comps = array_unique($tabcres);
        foreach($comps as $comp) {
            if ($comp != $tabcategories[2]) {
                $composante .= ';' . $comp;
            }
        }
    }
    $fieldup1['profile_field_up1composante'] = $composante;
    $fieldup1['profile_field_up1niveaulmda'] = $niveau;
    return $fieldup1;
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
        }
        // inscrire des clefs
        $clefs = wizard_list_clef();
        if (count($clefs)) {
            $this->myenrol_clef($course, $clefs);
        }

        $this->mydata = $mydata;
        return '';
    }

    /**
     * créé la partie variable (selon validateur ou créateur du message de
     * notification envoyé à la création du cours
     * @return array array("mgvalidator" => $mgv, "mgcreator" => $mgc);
     */
    public function get_messages() {
        global $CFG;
        $urlguide = $CFG->wwwroot .'/guide';
        $urlvalidator = $CFG->wwwroot .'/local/course_validated/index.php';
        $idval = array();
        $form3 =  $this->formdata['form_step3'];
        if (isset($form3['all-validators']) && !empty($form3['all-validators'])) {
            $allvalidators = $form3['all-validators'];
            foreach ($allvalidators as $validator) {
                 $idval['fullname'] = fullname($validator);
                 $idval['username'] = $validator->username;
            }
        }

        $nomcours = $this->mydata->course_nom_norme;

        $signature = 'Cordialement,' . "\n\n";
        $signature .= 'L\'assistance EPI' . "\n\n";
        $signature .= 'Service TICE - Pôle Ingénieries pédagogique et de formation' . "\n";
        $signature .= 'Université Paris 1 Panthéon-Sorbonne' . "\n";
        $signature .= 'Courriel : assistance-epi@univ-paris1.fr' . "\n";

        $mgc = 'Bonjour,' . "\n\n";
        $mgc .= 'Vous venez de créer l\'espace de cours "' . $nomcours . '" sur la plateforme '. $CFG->wwwroot . "\n\n";
        if (count($idval)) {
            $mgc .= 'Votre demande a été transmise à ' . $idval['fullname'] . ', ainsi qu\'aux gestionnaires de '
            . 'la plateforme pour approbation, avant son ouverture aux étudiants';
        } else {
            $mgc .=  'Votre demande a été transmise aux gestionnaires de '
            . 'la plateforme pour approbation, avant son ouverture aux étudiants.';
        }
        $mgc .= "\n\n";
        $mgc .= 'Notez cependant que toutes les personnes auxquelles vous avez attribué '
            . 'des droits de contribution ont d\'ores et déjà la possibilité de s\'approprier ce nouvel espace de cours : '
            . 'personnaliser le texte de présentation, organiser et nommer à leur convenance '
            . 'les différentes sections, déposer des documents, etc.' . "\n\n";
        $mgc .= 'Vous trouverez à cette adresse ' . $urlguide . ' des informations sur le processus d\'approbation des espaces '
            . 'nouvellement créés.' . "\n\n";
        $mgc .= 'N\'hésitez pas à contacter l\'un des membres de l\'équipe du service TICE :' . "\n";
        $mgc .= '- si vous souhaitez participer à l\'une des sessions de prise en mains régulièrement organisées ;' . "\n";
        $mgc .= '- si vous rencontrez une difficulté ou si vous constatez une anomalie de fonctionnement.' . "\n\n";
        $mgc .= 'Conservez ce message. Le récapitulatif technique présenté ci-après peut vous être utile, '
            . 'notamment pour dialoguer avec l\'équipe d\'assistance.' . "\n\n";
        $mgc .= $signature;


        $mgv = 'Bonjour,' . "\n\n";
        $mgv .= fullname($this->user) . ' ('.$this->user->email.') '
            . 'vient de créer l\'espace de cours "' . $nomcours . '" sur la plateforme '
            . $CFG->wwwroot . ' et vous a indiqué comme la personne pouvant valider sa création. ' . "\n\n";
        $mgv .= 'Pour donner votre accord :' . "\n\n";
        $mgv .= '1. Cliquez sur le lien suivant '. $urlvalidator . ' ;' . "\n";
        if (count($idval)) {
            $mgv .= '2. Si nécessaire, authentifiez-vous avec votre compte Paris 1 : ' . $idval['username'] . ' ;' . "\n";
        } else {
            $mgv .= '2. Si nécessaire, authentifiez-vous avec votre compte Paris 1. ' . "\n";
        }
        $mgv .= '3. Cliquez sur l\'icône "coche verte" située dans la colonne "Modifier" ;' . "\n";
        $mgv .= '4. '.fullname($this->user).' sera automatiquement prévenu-e par courriel de votre approbation.'  . "\n\n";
        $mgv .= 'Vous trouverez à cette adresse ' . $urlguide . ' des informations sur le processus d\'approbation des espaces '
             . 'nouvellement créés.' . "\n\n";
        $mgv .= 'Le récapitulatif technique présenté ci-après peut vous apporter des précisions sur cette demande.'  . "\n\n";
        $mgv .= 'Si cette demande ne vous concerne pas ou si vous ne souhaitez pas y donner suite, '
            . 'merci d\'en faire part à l\'équipe d\'assistance en lui transférant ce message '
            . '(assistance-epi@univ-paris1.fr).'  . "\n\n";
        $mgv .= $signature;

        return array("mgvalidator" => $mgv, "mgcreator" => $mgc);
    }

    private function update_session($courseid) {
        global $SESSION;
        $SESSION->wizard['idcourse'] = $courseid;
        $SESSION->wizard['idenrolment'] = 'manual';
    }

    /**
     * Met à jour la variable nameparam de $SESSION->wizard
     * @param string/array() $value nouvelle valeur
     * @param string $nameparam nom du parametre à mettre à jour
     * @param string $formparam nom du tableau intermédiaire
     */
    public function set_wizard_session($value, $nameparam, $formparam='') {
        global $SESSION;
        if ($formparam != '') {
            $SESSION->wizard[$formparam][$nameparam] = $value;
        } else {
            $SESSION->wizard[$nameparam] = $value;
        }
    }

    /**
     * Returns an object with properties derived from the forms data.
     * @return object
     */
    public function prepare_course_to_validate() {
        $this->mydata = (object) array_merge($this->formdata['form_step2'], $this->formdata['form_step3']);
        $this->setup_mydata();
        $this->mydata->course_nom_norme = '';
        $form2 = $this->formdata['form_step2'];

        // on est dans le cas 2
        if (isset($this->formdata['wizardcase']) && $this->formdata['wizardcase']=='2') {
            $rof1 = wizard_prepare_rattachement_rof_moodle($form2);
            $this->set_param_rof1($rof1);
            $this->set_rof_shortname($rof1['idnumber']);
            // metadonnee de rof1
            $this->set_metadata_rof1($rof1['tabpath']);
            // rattachement secondaire
            $this->set_metadata_rof2();

            $this->set_rof_fullname();
            $this->set_rof_nom_norm();
            $this->mydata->profile_field_up1composition = trim($form2['complement']);
            $this->mydata->profile_field_up1generateur = 'Manuel via assistant (cas n°2 ROF)';

        } else { // cas 3
            $this->mydata->course_nom_norme = $form2['fullname'];
            $this->mydata->profile_field_up1generateur = 'Manuel via assistant (cas n°3 hors ROF)';
            $this->set_categories_connection();
        }

        $this->mydata->summary = $form2['summary_editor']['text'];
        $this->mydata->summaryformat = $form2['summary_editor']['format'];

        // cours doit être validé
        $this->set_metadata_cycle_life();

        return $this->mydata;
    }

    /**
     * Création profile_field obligatoire
     */
    private function setup_mydata() {
        $this->mydata->profile_field_up1approbateureffid = '';
        $this->mydata->profile_field_up1rofname = '';
        $this->mydata->profile_field_up1niveaulmda = '';
        $this->mydata->profile_field_up1diplome = '';
        $this->mydata->profile_field_up1generateur = '';
    }

    private function set_metadata_cycle_life() {
        $this->mydata->profile_field_up1avalider = 1;
        $this->mydata->profile_field_up1datevalid = 0;
        $this->mydata->profile_field_up1datedemande = time();
        $this->mydata->profile_field_up1demandeurid = $this->user->id;
        $this->mydata->profile_field_up1approbateurpropid = wizard_get_approbateurpropid();
    }

    private function set_param_rof1($rof1) {
        if ( array_key_exists('idcat', $rof1) && $rof1['idcat'] != false) {
            $this->mydata->category = $rof1['idcat'];
            $this->set_wizard_session($rof1['idcat'], 'rattachement1', 'form_step2');
            $this->mydata->profile_field_up1niveaulmda = $rof1['up1niveaulmda'];
            $this->mydata->profile_field_up1composante = $rof1['up1composante'];
        }
        $this->mydata->idnumber = $rof1['idnumber'];
    }

    /**
     * Construit et assigne le paramètre $shortname d'un cours rattaché au ROF
     * @param string $idnumber
     */
    private function set_rof_shortname($idnumber) {
        $form2 = $this->formdata['form_step2'];
        $shortname = $idnumber;
        if (isset($form2['complement'])) {
            $complement = trim($form2['complement']);
            if ($complement != ''){
                $shortname .= ' - ' . $complement;
            }
        }
        $this->mydata->shortname = $shortname;
    }

    /**
     * Construit et assigne le paramètre $shortname d'un cours rattaché au ROF
     * @param string $idnumber
     */
    private function set_rof_fullname() {
        $form2 = $this->formdata['form_step2'];
        if ($form2['complement'] !='') {
            $this->mydata->fullname .= ' - ' . $form2['complement'];
        }
    }

    private function set_rof_nom_norm() {
        $form2 = $this->formdata['form_step2'];
        $this->mydata->course_nom_norme = $this->mydata->idnumber . ' - ' . $form2['fullname'];
        if ($form2['complement'] !='') {
            $this->mydata->course_nom_norme .= ' - ' . $form2['complement'];
        }
    }

    /**
     * assigne les informations du rattachement
     * principal ROF aux metadonnées de cours
     * @param array $tabpath
     */
    private function set_metadata_rof1($tabpath) {
        $mdrof1 = rof_get_metadata($tabpath);
        foreach ($mdrof1 as $data) {
            if (count($data)) {
                foreach($data as $label => $value) {
                    $champ = 'profile_field_'.$label;
                    $this->mydata->$champ = $value;
                }
            }
        }
    }

    /**
     * assigne les informations des rattachements
     * secondaires ROF aux metadonnées de cours
     */
    private function set_metadata_rof2() {
        $form2 = $this->formdata['form_step2'];
        $rof2 = wizard_prepare_rattachement_second($form2);
        if (count($rof2)) {
            $this->set_wizard_session($rof2['rofchemin'], 'rattachement2', 'form_step2');
            foreach($rof2['rofid'] as $rofid) {
                $this->mydata->profile_field_up1rofid .= ';' . $rofid;
            }
            foreach($rof2['rofpathid'] as $rofpath) {
                $this->mydata->profile_field_up1rofpathid .= ';' . $rofpath;
            }
            foreach($rof2['rofname'] as $rofname) {
                $this->mydata->formdata['form_step2']['rofname_second'][] = $rofname;
            }
        }
    }

    /**
     * assigne les catégories supplémentaires pour les cours hors ROF
    */
    private function set_categories_connection() {
        $form2 = $this->formdata['form_step2'];
        $tabcategories = get_list_category($form2['category']);
        if (isset($this->mydata->rattachements)) {
            $ratt = wizard_get_rattachement_fieldup1($this->mydata->rattachements, $tabcategories);
            if (count($ratt)) {
                foreach ($ratt as $fieldname => $value) {
                    $this->mydata->$fieldname = $value;
                }
            }
            if (count($this->mydata->rattachements)) {
                $first = true;
                foreach ($this->mydata->rattachements as $rattachement) {
                    $this->mydata->profile_field_up1categoriesbis .= ($first ? '' : ';') . $rattachement;
                    $first = false;
                }
            }
        }
    }

    // supprime les méthodes d'inscriptions guest et self
    private function delete_default_enrol_course($courseid) {
        global $DB;
        $DB->delete_records('enrol', array('courseid' => $courseid, 'enrol' => 'self'));
        $DB->delete_records('enrol', array('courseid' => $courseid, 'enrol' => 'guest'));
    }

    private function myenrol_clef($course, $tabClefs) {
        global $DB;
        if ($course->id == SITEID) {
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
                $startdate  = $tabClef['enrolstartdate'];
            } else {
                $startdate = 0;
            }
            if (isset($tabClef['enrolenddate'])) {
                $enddate = $tabClef['enrolenddate'];
            } else {
                $enddate = 0;
            }

            $instance = new stdClass();
            $instance->enrol = $enrol;
            $instance->status = $status;
            $instance->courseid = $course->id;
            $instance->roleid = $roleid;
            $instance->name = $name;
            $instance->password = $tabClef['password'];
            $instance->customint1 = 0; // clef d'inscription groupe ?
            $instance->customint2 = 0;
            $instance->customint3 = 0;
            $instance->customint4 = 0; // envoie d'un message

            $instance->enrolstartdate = $startdate;
            $instance->enrolenddate = $enddate;
            $instance->timemodified = $course->timecreated;
            $instance->timecreated = $course->timecreated;
            $instance->sortorder = $DB->get_field('enrol', 'COALESCE(MAX(sortorder), -1) + 1', array('courseid' => $course->id));
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

        //$wizardcase = $this->formdata['wizardcase']; // was not used

        // categorie
        $displaylist = array();
        $parentlist = array();
        make_categories_list($displaylist, $parentlist);

        $form2 = $this->formdata['form_step2'];
        $form3 = $this->formdata['form_step3'];
        //  $idcat = $form2['category'];
        $idcat = $this->mydata->category;
        $mg .= get_string('categoryblockE3', 'local_crswizard') . ' : ' . $displaylist[$idcat] . "\n";
        // cas 3
        if (isset($form3['rattachements']) && count($form3['rattachements'])) {
            $first = true;
            foreach ($form3['rattachements'] as $ratt) {
                $mg .= ($first?get_string('labelE7ratt2', 'local_crswizard') . ' : ' : ', ')
                    . $displaylist[$ratt];
                $first = false;
            }
            $mg .=  "\n";
        }
        // cas 2
        if (isset($form2['rattachement2']) && count($form2['rattachement2'])) {
            $mg .= get_string('labelE7ratt2', 'local_crswizard') . ' : ';
            $etab = $displaylist[$form2['category']];
            $first = true;
            foreach ($form2['rattachement2'] as $formsecond) {
                $mg .= ($first ? '' : ', ') . $etab . ' / ' . $formsecond;
                $first = false;
            }
            $mg .=  "\n";
        }
        $mg .= get_string('fullnamecourse', 'local_crswizard') . $this->mydata->fullname . "\n";
        $mg .= get_string('shortnamecourse', 'local_crswizard') . $this->mydata->shortname . "\n";

        $mg .= get_string('coursestartdate', 'local_crswizard') . date('d-m-Y', $form2['startdate']) . "\n";
        $mg .= get_string('up1datefermeture', 'local_crswizard') . date('d-m-Y', $form2['up1datefermeture']) . "\n";
        $mg .= 'Mode de création : ' .  $this->mydata->profile_field_up1generateur . "\n";

        // validateur si il y a lieu
        if (isset($form3['all-validators']) && !empty($form3['all-validators'])) {
            $allvalidators = $form3['all-validators'];
            $mg .= get_string('selectedvalidator', 'local_crswizard') . ' : ';
            $first = true;
            foreach ($allvalidators as $id => $validator) {
                $mg .= ($first ? '' : ', ') . fullname($validator);
                $first = false;
            }
            $mg .=  "\n";
        }

        // liste des enseignants :
        $form4 = $this->formdata['form_step4']; // ou $SESSION->wizard['form_step4']
        $mg .= get_string('teachers', 'local_crswizard') . "\n";
        if (isset($form4['all-users']) && is_array($form4['all-users'])) {
            $allusers = $form4['all-users'];
            $labels = $myconfig->role_teachers;
            foreach ($allusers as $role => $users) {
                $label = $role;
                if (isset($labels[$role])) {
                    $label = get_string($labels[$role], 'local_crswizard');
                }
                $first = true;
                 $mg .= '    ' . $label . ' : ';
                foreach ($users as $id => $user) {
                    $mg .=  ($first ? '' : ', ') . fullname($user);
                    $first = false;
                }
                $mg .=  "\n";
            }
        } else {
            $mg .= '    Aucun' . "\n";
        }

        // liste des groupes
        $form5 = $this->formdata['form_step5']; // ou $SESSION->wizard['form_step5']
        $mg .= get_string('cohorts', 'local_crswizard') . "\n";
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
                    $mg .= '    ' . ($first ? $label . ' : ' : '           ') . $group->name
                        .  ' — '  . "{$group->size} inscrits" . "\n";
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

    public function get_email_subject($idcourse, $type) {
        $subject = '';
        $sitename = format_string(get_site()->shortname);
        $subject .= "[$sitename] $type espace";
        $subject .=' n°' . $idcourse;
        $subject .= ' : ' . $this->mydata->course_nom_norme;
        return $subject;
    }

    /**
    * envoie un message de notification suite à la création du cours
    * @param int $idcourse : identifiant du cours créé
    * @param string $mgc destiné au demandeur
    * @param string $mgv destiné à l'approbateur et aux validateurs
    */
    public function send_message_notification($idcourse, $mgc, $mgv) {
        global $DB;
        $userfrom = new object();
        static $supportuser = null;
        if (!empty($supportuser)) {
            $userfrom = $supportuser;
        } else {
            $userfrom = $this->user;
        }

        //approbateur désigné ?
        $approbateur = false;
        $typeMessage = 'Assistance - Demande approbation';
        $form3 =  $this->formdata['form_step3'];
        if (isset($form3['all-validators']) && !empty($form3['all-validators'])) {
            $approbateur = true;
             $typeMessage = 'Demande approbation';
        }
        $subject = $this->get_email_subject($idcourse, $typeMessage);

        $eventdata = new object();
        $eventdata->component = 'moodle';
        $eventdata->name = 'courserequested';
        $eventdata->userfrom = $userfrom;
        $eventdata->subject = $subject; //** @todo get_string()
        $eventdata->fullmessageformat = FORMAT_PLAIN;   // text format
        $eventdata->fullmessage = $mgv;
        $eventdata->fullmessagehtml = '';   //$messagehtml;
        $eventdata->smallmessage = $mgv; // USED BY DEFAULT !
        // documentation : http://docs.moodle.org/dev/Messaging_2.0#Message_dispatching

        // envoi aux supervalidateurs
        $coursecontext = get_context_instance(CONTEXT_COURSE, $idcourse);
        $supervalidators = get_users_by_capability($coursecontext, 'local/crswizard:supervalidator');
        foreach ($supervalidators as $userto) {
            $eventdata->userto = $userto;
            $res = message_send($eventdata);
            if (!$res) {
                // @todo Handle messaging errors
            }
        }

        // envoi à l'approbateur si besoin
        if ($approbateur) {
            $allvalidators = $form3['all-validators'];
            foreach ($allvalidators as $validator) {
                $eventdata->userto = $validator;
                $res = message_send($eventdata);
            }
        }

        // envoi à helpdesk_user si définit dans crswizard.setting
        $helpuser = get_config('local_crswizard', 'helpdesk_user');
        if (isset($helpuser)) {
            $userid = $DB->get_field('user', 'id', array('username' => $helpuser));
            if ($userid) {
                $eventdata->userto = $userid;
                $res = message_send($eventdata);
            }
        }

        // copie au demandeur
        $eventdata->userto = $this->user;
        $subject = $this->get_email_subject($idcourse, 'Création');
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $mgc;
        $eventdata->smallmessage = $mgc; // USED BY DEFAULT !
        $res = message_send($eventdata);
    }

    //fonctions spécifiques update_course

    public function get_course($course) {
        $this->course = $course;
    }

    public function prepare_update_course() {
        if (isset($this->formdata['form_step3'])) {
            $this->mydata = (object) array_merge($this->formdata['form_step2'], $this->formdata['form_step3']);
        } else {
            $this->mydata = (object) $this->formdata['form_step2'];
        }
        $form2 = $this->formdata['form_step2'];

        if (isset($this->formdata['wizardcase']) && $this->formdata['wizardcase']=='2') {
            $changerof1 = $this->check_first_connection();
            $rof1 = wizard_prepare_rattachement_rof_moodle($form2, $changerof1);
            if ($changerof1 == false) {
                $rof1['idnumber'] = trim($this->formdata['init_course']['idnumber']);
            }
            $this->set_param_rof1($rof1);
            $this->set_rof_shortname($rof1['idnumber']);
            // metadonnee de rof1
            $this->set_metadata_rof1($rof1['tabpath']);
            // rattachement secondaire
            $this->set_metadata_rof2();
            $this->mydata->profile_field_up1composition = trim($form2['complement']);
            $this->set_rof_fullname();
            $this->set_rof_nom_norm();
        } else { // cas 3
            $this->mydata->course_nom_norme = $form2['fullname'];
            $this->set_categories_connection();
        }

        $this->mydata->summary = $form2['summary_editor']['text'];
        $this->mydata->summaryformat = $form2['summary_editor']['format'];


        return $this->mydata;
    }

    /**
     * Vérifie si le premier rattachement ROF à été modifié
     * @return bool $check true si modification
     */
    private function check_first_connection() {
        $check = false;
        $form2 = $this->formdata['form_step2'];
        if (isset($form2['item']) && count($form2['item'])) {
            $allrof = $form2['item'];
            if (isset($allrof['p']) && count($allrof['p'])) {
                $rofid = $allrof['p'][0];
                $apogee = rof_get_code_or_rofid($rofid);
                $up1rofid = trim($this->formdata['init_course']['profile_field_up1rofid']);
                $rofid = '';
                if (strstr($up1rofid, ';')) {
                    $tab = explode(';', $up1rofid);
                    $rofid = $tab[0];
                } else {
                    $rofid = $up1rofid;
                }

                if ($rofid != $apogee) {
                    $check = true;
                }
            }
        }
        return $check;
    }

    public function update_course() {
        $this->prepare_update_course();
        update_course($this->mydata);
        $custominfo_data = custominfo_data::type('course');
        $cleandata = $this->customfields_wash($this->mydata);
        $custominfo_data->save_data($cleandata);
        $this->update_myenrol_cohort();
        rebuild_course_cache($this->mydata->id);
    }

    public function update_myenrol_cohort()
    {
        $course = $this->formdata['init_course'];
        $oldcohorts = array();
        if (isset($course['group'])) {
            $oldcohorts = $course['group'];
        }
        $newcohorts = array();
        if (isset($this->formdata['form_step5']['group'])) {
            $newcohorts = $this->formdata['form_step5']['group'];
        }

        // ajout
        $cohortadd = array();
        foreach ($newcohorts as $role => $tabg) {
            if (array_key_exists($role, $oldcohorts) == false) {
                $cohortadd[$role] = $tabg;
            } else {
                foreach ($tabg as $g) {
                    if (! in_array($g, $oldcohorts[$role])) {
                        $cohortadd[$role][] = $g;
                    }
                }
            }
        }
        myenrol_cohort($course['id'], $cohortadd);
        // suppression
        $cohortremove = array();
        foreach ($oldcohorts as $role => $tabg) {
            if (array_key_exists($role, $newcohorts) == false) {
                $cohortremove[$role] = $tabg;
            } else {
                foreach ($tabg as $g) {
                    if (in_array($g, $newcohorts[$role]) == false) {
                        $cohortremove[$role][] = $g;
                    }
                }
            }
        }
        wizard_unenrol_cohort($course['id'], $cohortremove);
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
