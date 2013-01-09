<?php
/* @var $DB moodle_database */

/**
 * Stop process if current user has no "create" or "request" course capabilities.
 * @param $context (system)context
 */
function require_capabilities($context) {
    $has_right = has_capability('moodle/course:create', $context);
    if (!$has_right) {
        require_capability('moodle/course:request', $context);
    } else {
        throw new required_capability_exception($context, 'moodle/course:create', 'nopermissions', '');
    }
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

    $result = $DB->get_records('user', array('username' => 'admin')); //** @todo on envoie à qui ? plusieurs ?
    //** @todo maybe replace all this by a call to course/lib.php course_request::notify +4394
    $eventdata = new object();
    $eventdata->component = 'moodle';
    $eventdata->name = 'courserequested';
    $eventdata->userfrom = $USER;
    $eventdata->subject = '[CourseWizardRequest]'; //** @todo get_string()
    $eventdata->fullmessageformat = FORMAT_PLAIN;   // text format
    $eventdata->fullmessage = $message;
    $eventdata->fullmessagehtml = $messagehtml;
    $eventdata->smallmessage = $message; // USED BY DEFAULT !
    // documentation : http://docs.moodle.org/dev/Messaging_2.0#Message_dispatching
    foreach ($result as $userto) {
        $eventdata->userto = $userto;
        $res = message_send($eventdata);
        if (!$res) {
            /** @todo Handle messaging errors */
        }
    }
}

/**
 * Convertit les champs custom_info_field de type datetime en timestamp
 * @param object $data
 * @return object $data
 */
function customfields_wash($data) {
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

function myenrol_cohort($idcourse, $tabGroup) {
    global $DB, $CFG;
    if ($idcourse == SITEID) {
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
                if (!$DB->record_exists('enrol', array('enrol' => $enrol, 'courseid' => $idcourse, 'customint1' => $cohort->id))) {
                    $instance = new stdClass();
                    $instance->enrol = $enrol;
                    $instance->status = $status;
                    $instance->courseid = $idcourse;
                    $instance->customint1 = $cohort->id;
                    $instance->roleid = $roleid;
                    $instance->enrolstartdate = 0;
                    $instance->enrolenddate = 0;
                    $instance->timemodified = time();
                    $instance->timecreated = $instance->timemodified;
                    $instance->sortorder = $DB->get_field('enrol', 'COALESCE(MAX(sortorder), -1) + 1', array('courseid' => $idcourse));
                    $DB->insert_record('enrol', $instance);
                }
            } else {
                $error[] = 'groupe "' . $idgroup . '" n\'existe pas dans la base';
            }
        }
    }

    require_once("$CFG->dirroot/enrol/cohort/locallib.php");
    enrol_cohort_sync($idcourse);
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
        return '[]';
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

function myenrol_clef($idcourse, $tabClefs) {
    global $DB;
    if ($idcourse == SITEID) {
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
        $instance->courseid = $idcourse;
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
        $instance->sortorder = $DB->get_field('enrol', 'COALESCE(MAX(sortorder), -1) + 1', array('courseid' => $idcourse));
        $DB->insert_record('enrol', $instance);
    }
}

/**
 * Reconstruit le tableau $displaylist pour le plugin jquery select-into-subselects.js
 * @retun array() $mydisplaylist
 * */
function wizard_get_mydisplaylist() {
    $displaylist = array();
    $parentlist = array();
    make_categories_list($displaylist, $parentlist);
    $myconfig = new my_elements_config();
    $labels = $myconfig->categorie_deph;
    $label0 = implode(' * / ', $labels);
    $label0 .= ' * ';
    $mydisplaylist = array(0 => $label0);

    foreach ($displaylist as $id => $label) {
        if (array_key_exists($id, $parentlist) && count($parentlist[$id]) == 3) {
            $mydisplaylist[$id] = $label;
        }
    }
    return $mydisplaylist;
}

/**
 * Renvoie le nom du Course custom fields de nom abrégé $shortname
 * @param string $shortname nom abrégé du champ
 * @return string $name nom du champ
 */
function get_custom_info_field_label($shortname) {
    global $DB;
    $name = $DB->get_field('custom_info_field', 'name', array('objectname' => 'course', 'shortname' => $shortname));
    return $name;
}

class core_wizard {
    function create_course_to_validate() {
        global $SESSION, $DB, $CFG;
        // créer cours
        $mydata = $this->prepare_course_to_validate();
        $course = create_course($mydata);
        // fonction addhoc - on supprime les enrols par défaut
        $this->delete_default_enrol_course($course->id);
        // save custom fields data
        $mydata->id = $course->id;
        $custominfo_data = custominfo_data::type('course');

        $mydata = customfields_wash($mydata);

        $custominfo_data->save_data($mydata);
        $SESSION->wizard['idcourse'] = $course->id;
        $SESSION->wizard['idenrolment'] = 'manual';
        // tester si le cours existe bien ?
        //$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
        // inscrire des enseignants
        if (isset($SESSION->wizard['form_step4']['user']) && count($SESSION->wizard['form_step4']['user'])) {
            $tabUser = $SESSION->wizard['form_step4']['user'];
            myenrol_teacher($course->id, $tabUser);
        }

        // inscrire des cohortes
        if (isset($SESSION->wizard['form_step5']['group']) && count($SESSION->wizard['form_step5']['group'])) {
            $tabGroup = $SESSION->wizard['form_step5']['group'];
            $erreurs = myenrol_cohort($course->id, $tabGroup);
            if (count($erreurs)) {
                $SESSION->wizard['form_step5']['cohorterreur'] = $erreurs;
                return affiche_error_enrolcohort($erreurs);
            }
        } else {
            // inscrire des clefs
            $clefs = wizard_list_clef();
            if (count($clefs)) {
                myenrol_clef($course->id, $clefs);
            }
        }
        return '';
    }

    function prepare_course_to_validate() {
        global $SESSION, $USER;
        $date = $SESSION->wizard['form_step2']['startdate'];
        $startdate = mktime(0, 0, 0, $date['month'], $date['day'], $date['year']);

        $date2 = $SESSION->wizard['form_step2']['up1datefermeture'];
        $enddate = mktime(0, 0, 0, $date2['month'], $date2['day'], $date2['year']);

        $datamerge = array_merge($SESSION->wizard['form_step2'], $SESSION->wizard['form_step3']);
        $mydata = (object) $datamerge;
        $mydata->startdate = $startdate;

        //step3 custominfo_field
        // compoante
        $up1composante = trim($mydata->profile_field_up1composante);
        if ($up1composante != '' && substr($up1composante, -1) != ';') {
            $mydata->profile_field_up1composante .= ';';
        }
        $mydata->profile_field_up1composante .= $SESSION->wizard['form_step3']['composante'];

        // niveau
        $up1niveau = trim($mydata->profile_field_up1niveau);
        if ($up1niveau != '' && substr($up1niveau, -1) != ';') {
            $mydata->profile_field_up1niveau .= ';';
        }
        $mydata->profile_field_up1niveau .= $SESSION->wizard['form_step3']['niveau'];

        // cours doit être validé
        $mydata->profile_field_up1avalider = 1;
        $mydata->profile_field_up1datevalid = 0;
        $mydata->profile_field_up1datedemande = time();
        $mydata->profile_field_up1demandeur = $USER->firstname . ' ' . $USER->lastname;
        $mydata->profile_field_up1datefermeture = $enddate;

        return $mydata;
    }

    // methode ad hoc : supprime les méthodes d'inscriptions guest et self
    function delete_default_enrol_course($courseid) {
        global $DB;
        $DB->delete_records('enrol', array('courseid' => $courseid, 'enrol' => 'self'));
        $DB->delete_records('enrol', array('courseid' => $courseid, 'enrol' => 'guest'));
    }

}

class my_elements_config {
    public $categorie_cours = array(
        'Période', 'Etablissement', 'Composante', 'Niveau'
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
