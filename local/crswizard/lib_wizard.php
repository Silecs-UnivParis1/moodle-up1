<?php
/* @var $DB moodle_database */

require_once("$CFG->dirroot/local/roftools/roflib.php");

/**
 * Construit la liste des cours dans lesquels $USER est inscrits avec la capacité course:update
 * @return array $course_list
 */
function wizard_get_course_list_teacher() {
    global $USER;
    $course_list = array();
    if ($courses = enrol_get_my_courses(NULL, 'shortname ASC')) {
        foreach ($courses as $course) {
            $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
            if ( has_capability('moodle/course:update', $coursecontext, $USER->id) ) {
                $course_list[$course->id] = $course->shortname;
            }
        }
    }
    return $course_list;
}

/**
 * construit la liste des cours de la catégorie désignée comme catégorie modèle.
 * Utilise le paramètre category_model des settings du plugin crswizard
 * @return array $course_list
 */
function wizard_get_course_model_list() {
    global $DB;
    $course_list = array();
    $category_model = get_config('local_crswizard','category_model');
    if ($category_model != 0) {
        $courses = $DB->get_records('course', array('category'=> $category_model), 'id, shortname');
        if (count($courses)) {
            foreach ($courses as $course) {
                $course_list[$course->id] = $course->shortname;
            }
        }
    }
    return $course_list;
}

/**
 * Fonction construsiant la liste des catégories de cours
 * @return array $wizard_make_categories_model_list
 */
function wizard_make_categories_model_list() {
    $displaylist = array();
    $parentlist = array();
    make_categories_list($displaylist, $parentlist, 'moodle/course:create');
    $wizard_make_categories_model_list = array(0 => 'Aucune');
    foreach ($displaylist as $key => $value) {
        $wizard_make_categories_model_list[$key] = $value;
    }
    return $wizard_make_categories_model_list;
}

/**
 * Fonction de redirection ad hoc avec message en dernière étape de création
 * Reprise partielle des fonctions redirect() et $OUTPUT->redirect_message()
 * @param string $url
 * @param string $message
 * @param int $delay
 */
function wizard_redirect_creation($url, $message='', $delay=5) {
    global $OUTPUT, $PAGE, $SESSION, $CFG;

    $localoutput = $OUTPUT;
    $localoutput->page = $PAGE;

    if ($url instanceof moodle_url) {
        $url = $url->out(false);
    }
    $debugdisableredirect = false;

    $url = preg_replace('/[\x00-\x1F\x7F]/', '', $url);
    $url = str_replace('"', '%22', $url);
    $encodedurl = preg_replace("/\&(?![a-zA-Z0-9#]{1,8};)/", "&amp;", $url);
    $encodedurl = preg_replace('/^.*href="([^"]*)".*$/', "\\1", clean_text('<a href="'.$encodedurl.'" />', FORMAT_HTML));
    $url = str_replace('&amp;', '&', $encodedurl);


    if (!empty($message)) {
        if ($delay === -1 || !is_numeric($delay)) {
            $delay = 3;
        }
        $message = clean_text($message);
    } else {
        $message = get_string('pageshouldredirect');
        $delay = 0;
    }

    $CFG->docroot = false;

    if (!$debugdisableredirect) {
        // Don't use exactly the same time here, it can cause problems when both redirects fire at the same time.
        $localoutput->metarefreshtag = '<meta http-equiv="refresh" content="'. $delay .'; url='. $encodedurl .'" />'."\n";
        $localoutput->page->requires->js_function_call('document.location.replace', array($url), false, ($delay + 3));
    }
    $PAGE->requires->css(new moodle_url('/local/crswizard/css/crswizard.css'));

    $site = get_site();
    $straddnewcourse = get_string("addnewcourse");
    $PAGE->navbar->add($straddnewcourse);

    $PAGE->set_title("$site->shortname: $straddnewcourse");
    $PAGE->set_heading($site->fullname);


    $output = $localoutput->header();

    $output .= $localoutput->box(get_string('wizardcourse', 'local_crswizard'), 'titlecrswizard');
    $output .= $localoutput->box(get_string('stepredirect', 'local_crswizard'), 'titlecrswizard');

    $output .= $localoutput->notification($message, 'redirectmessage');
    $output .= '<div class="continuebutton">(<a href="'. $encodedurl .'">'. get_string('continue') .'</a>)</div>';
    if ($debugdisableredirect) {
        $output .= '<p><strong>Error output, so disabling automatic redirect.</strong></p>';
    }
    $output .= $localoutput->footer();
    echo $output;
    exit;
}

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
 * Renvoie le liste de valeurs pour la métadonnée de nom $type
 * @param string $type : nom de la métaddonnée de cours
 * @return array $list
 */
function get_list_metadonnees($type) {
    $list = array();
    switch ($type) {
        case 'up1semestre':
            $list[0] = 'Aucun';
            $list['1'] = '1';
            $list['2'] = '2';
            $list['3'] = '3';
            $list['4'] = '4';
            $list['5'] = '5';
            $list['6'] = '6';
            break;
        case 'up1niveauannee':
            $list[0] = 'Aucun';
            $list['1'] = '1';
            $list['2'] = '2';
            $list['3'] = '3';
            $list['4'] = '4';
            $list['5'] = '5';
            $list['6'] = '6';
            break;
        case 'up1niveau':
            $list[0] = 'Aucun';
            $list['L1'] = 'L1';
            $list['L2'] = 'L2';
            $list['L3'] = 'L3';
            $list['M1'] = 'M1';
            $list['M2'] = 'M2';
            $list['D'] = 'D';
            $list['Autre'] = 'Autre';
            break;
    }

    return $list;
}

/**
 * renvoie le tableau des métadonnées ajouté dans le cas 3
 * @param bool $label
 * @return array $metadonnees
 */
function get_array_metadonees($label = TRUE) {
    $metadonnees = array();
    if ($label) {
        $metadonnees = array('up1niveauannee' => 'Niveau année :',
        'up1semestre' => 'Semestre :',
        'up1niveau' => 'Niveau :');
    } else {
        $metadonnees = array('up1niveauannee', 'up1semestre', 'up1niveau');
    }
    return $metadonnees;
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

/**
 * désinscrit un ensemble de groupe à un cours
 * @param int $courseid
 * @param array $tabGroup
 */
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

/**
 * supprime la cle d'inscription de type enrol du cours $course
 * @param string $enrol
 * @param course object $course
 */
function wizard_unenrol_key($enrol, $course) {
    global $DB, $CFG;
    $instance = $DB->get_record('enrol', array('courseid' => $course->id,
        'enrol' => $enrol, 'timecreated' => $course->timecreated));
    if ($instance) {
        require_once("$CFG->dirroot/enrol/".$enrol."/locallib.php");
        $plugin_enrol = enrol_get_plugin($enrol);
        $plugin_enrol->delete_instance($instance);
    }
}

/**
 * met à jour les paramètre d'une cle d'inscription existante
 * @param string $enrol nature de l'inscription (enrol.enrol)
 * @param object course $course
 * @param array $tabkey nouvelle valeur de la cle
 * @retun bool $modif
 */
function wizard_update_enrol_key($enrol, $course, $tabkey) {
    global $DB;
    $modif = false;
    $instance = $DB->get_record('enrol', array('courseid' => $course->id,
        'enrol' => $enrol, 'timecreated' => $course->timecreated));
    if ($instance) {
        if ($tabkey['password'] != $instance->password) {
            $modif = true;
        }
        if ($tabkey['enrolstartdate'] != $instance->enrolstartdate) {
            $modif = true;
        }
        if ($tabkey['enrolenddate'] != $instance->enrolenddate) {
            $modif = true;
        }
        if ($modif) {
            $DB->update_record('enrol', array('id' => $instance->id, 'password' => $tabkey['password'],
                'enrolstartdate' => $tabkey['enrolstartdate'], 'enrolenddate' => $tabkey['enrolenddate'],
                'timemodified' => time()));
        }
    }
    return $modif;
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
 * @param string $form_step
 * @return array
 */
function wizard_get_rof($form_step = 'form_step2') {
    global $DB, $SESSION;
    if (!isset($SESSION->wizard[$form_step]['item'])) {
        return false;
    }
    $list = array();
    $formRof = $SESSION->wizard[$form_step]['item'];
    foreach ($formRof as $nature => $rof) {
        foreach ($rof as $rofpath => $rofid) {
            if ($rofid === FALSE ) {
                return $list;
            }
            $list[$rofpath]['nature'] = $nature;
            $list[$rofpath]['rofid'] = $rofid;
            $list[$rofpath]['path'] = $rofpath;
            $tabrof = rof_get_combined_path(explode('_', $rofpath));
            $list[$rofpath]['chemin'] = substr(rof_format_path($tabrof, 'name', false, ' / '), 3);

            $tabSource = '';
            if (substr($rofid, 0, 5) == 'UP1-P') {
                $tabSource = 'rof_program';
            } else {
                $tabSource = 'rof_course';
            }
            $object = $DB->get_record($tabSource, array('rofid' => $rofid));
            if ($object) {
                $list[$rofpath]['object'] = $object;
            }
        }
    }
    return $list;
}

/**
 * construit le tableau $form['item'] à partir de $_POST['item']
 * @param array() $postItem : $_POST['item']
 * @return array() $form['item']
 */
function wizard_get_array_item($postItem) {
    $tabitem = array();
    foreach ($postItem as $nature => $rof) {
        foreach($rof as $path) {
            $rofid = substr(strrchr($path, '_'), 1);
            $tabitem[$nature][$path] = $rofid;
        }
    }
    return $tabitem;
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
 * @param string $form_step
 * @return string
 */
function wizard_preselected_rof($form_step = 'form_step2') {
    global $SESSION;
    if (!isset($SESSION->wizard[$form_step]['all-rof'])) {
        return '[]';
    }
    $liste = array();
    if (!empty($SESSION->wizard[$form_step]['all-rof'])) {
        foreach ($SESSION->wizard[$form_step]['all-rof'] as $rofpath => $rof) {
            $object = $rof['object'];
            $tabrof = rof_get_combined_path(explode('_', $rof['path']));
            $chemin = substr(rof_format_path($tabrof, 'name', false, ' > '), 3);
            $liste[] = array(
                    "label" => rof_combined_name($object->localname, $object->name),
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

function wizard_list_clef($form6) {
    global $SESSION;
    $list = array();
    $tabCle = array('u' => 'Etudiante', 'v' => 'Visiteur');
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
 * @param bool $change si true, on recalcule de idnumber
 * @return array() $rof1 - idcat, apogee et idnumber
 */
function wizard_prepare_rattachement_rof_moodle($form2, $change=true) {
    global $DB;
    $rof1 = array();
    if (isset($form2['item']) && count($form2['item'])) {
        $allrof = $form2['item'];
        if (isset($allrof['p']) && count($allrof['p']) == 1) {
            $rofpath = key($allrof['p']);
            $rofid = $allrof['p'][$rofpath];
            $rof1['rofid'] = $rofid;
            $tabpath = explode('_', $rofpath);
            $rof1['tabpath'] = $tabpath;
            $idcategory = rof_rofpath_to_category($tabpath);
            if ($idcategory) {
                $rof1['idcat'] = $idcategory;
                $category = $DB->get_record('course_categories', array('id' => $idcategory));
                $rof1['up1niveaulmda'] = $category->name;
                $rof1['up1composante'] = $DB->get_field('course_categories', 'name', array('id' => $category->parent));
            }
            $rof1['apogee'] = rof_get_code_or_rofid($rofid);
            if ($change == true) {
                $rof1['idnumber'] = wizard_rofid_to_idnumber($rofid);
            }
        }
    }
    return $rof1;
}

/**
 * renvoie la liste des identifiants catégorie moodle des rattachements secondaires
 * @param array() $form
 * @return string $rofidmoodle : identifiants séparés par des ;
 */
function wizard_get_idcat_rof_secondaire($form) {
    global $DB;
    $rofidmoodle = '';
    if (isset($form['item']) && count($form['item'])) {
        $allrof = $form['item'];
        if (isset($allrof['s']) && count($allrof['s'])) {
            foreach ($allrof['s'] as $rofpath => $rofid) {
                $tabpath = explode('_', $rofpath);
                $idcategory = rof_rofpath_to_category($tabpath);
                if ($idcategory) {
                    $rofidmoodle .= $idcategory . ';';
                }
            }
        }
    }
    if ($rofidmoodle != '' && substr($rofidmoodle, -1) == ';') {
        $rofidmoodle = substr($rofidmoodle, 0, -1);
    }
    return $rofidmoodle;
}

/**
 * Retourne le rofid, rofpathid et rofname des rattachements secondaires
 * @param array() $form
 * @return array() $rof2 - rofid, rofpathid, rofname et tabpath
 */
function wizard_prepare_rattachement_second($form) {
    $rof2 = array();
    if (isset($form['item']) && count($form['item'])) {
        $allrof = $form['item'];
        if (isset($allrof['s']) && count($allrof['s'])) {
            foreach($allrof['s'] as $rofpath => $rofid) {
                $rof2['rofid'][] = $rofid;
                if ($rofid !== FALSE) {
                    $path = strtr($rofpath, '_', '/');
                    $rof2['rofpathid'][] = '/' . $path;

                    $tabpath = explode('_', $rofpath);
                    // nouvelle politique de rattachement secondaire
                    $rof2['tabpath'][] = $tabpath;

                    $tabrof = rof_get_combined_path($tabpath);
                    $chemin = substr(rof_format_path($tabrof, 'name', false, ' / '), 3);
                    $rof2['rofchemin'][] = $chemin;
                }
                if ($rofid !== FALSE) {
                    $rofobjet =  $form['all-rof'][$rofpath]['object'];
                    $rof2['rofname'][] = rof_combined_name($rofobjet->localname, $rofobjet->name);
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
        $sqlcatComp = "SELECT DISTINCT name FROM {course_categories} WHERE id IN (" . $listecat . ") AND depth=3";
        $catComp = array();
        $catComp = $DB->get_fieldset_sql($sqlcatComp);

        $sqlcatDip = "SELECT * FROM {course_categories} WHERE id IN (" . $listecat . ") AND depth=4";
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
            $sqlcatComp = "SELECT DISTINCT name FROM {course_categories} WHERE id IN (" . $listenewcat . ") AND depth=3";
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

/**
 * Stocke si besoin dans $SESSION->wizard['form_step1'] les informations relavives au cours modèle
 * sélectionné et crée le backup associé
 */
function get_selected_model() {
    global $SESSION,$USER, $DB;
    $form_model = $SESSION->wizard['form_step1'];
    $coursemodelid = 0;
    $go = true;

    if ($form_model['modeletype'] != '0') {
        if (array_key_exists($form_model['modeletype'], $form_model)) {
            $coursemodelid = $form_model[$form_model['modeletype']];
            if (isset($SESSION->wizard['form_step1']['coursedmodelid']) &&
                $SESSION->wizard['form_step1']['coursedmodelid'] == $coursemodelid) {
                    $go = false;
            }
            if ($go) {
                $coursemodel = $DB->get_record('course', array('id' => $coursemodelid), '*', MUST_EXIST);
                if ($coursemodel) {
                    $SESSION->wizard['form_step1']['coursedmodelid'] = $coursemodelid;
                    $SESSION->wizard['form_step1']['coursemodelfullname'] = $coursemodel->fullname;
                    $SESSION->wizard['form_step1']['coursemodelshortname'] = $coursemodel->shortname;
                }
            }
        }
    } else {
        $SESSION->wizard['form_step1']['mybackup'] = array();
        $SESSION->wizard['form_step1']['coursedmodelid'] = 0;
    }
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
        // ajout commentaire de creation
        $mydata->profile_field_up1commentairecreation = strip_tags($this->formdata['form_step7']['remarques']);

        if (isset($this->formdata['form_step1']['coursedmodelid']) && $this->formdata['form_step1']['coursedmodelid'] != '0') {
            $options = array();
            $options[] = array('name' => 'users', 'value' => 0);
            $duplicate = new wizard_modele_duplicate($this->formdata['form_step1']['coursedmodelid'], $mydata, $options);
            $duplicate->create_backup();
            $course = $duplicate->retore_backup();
            $mydata->profile_field_up1modele = '[' . $this->formdata['form_step1']['coursedmodelid'] . ']'
                . $this->formdata['form_step1']['coursemodelshortname'];
        } else {
            $course = create_course($mydata);
        }

        add_to_log($course->id, 'crswizard', 'create', 'view.php?id='.$course->id, 'previous (ID '.$course->id.')');
        $this->course = $course;

        // on supprime les enrols par défaut
        $this->delete_default_enrol_course($course->id);
        // save custom fields data
        $mydata->id = $course->id;
        $custominfo_data = custominfo_data::type('course');

        // metadata supp.
        if ($this->formdata['wizardcase'] == 3) {
            $metadonnees = get_array_metadonees(FALSE);
            foreach ($metadonnees as $md) {
                if (!empty($this->formdata['form_step3'][$md])) {
                    $donnees = '';
                    foreach ($this->formdata['form_step3'][$md] as $elem) {
                        $donnees = $donnees . $elem . ';';
                    }
                    $donnees = substr($donnees, 0, -1);
                    $name = 'profile_field_' . $md;
                    if (isset($this->mydata->$name) && $this->mydata->$name != '') {
                        $this->mydata->$name .= ';' . $donnees;
                    } else {
                        $this->mydata->$name = $donnees;
                    }
                }
            }
        }

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
        if (isset($this->formdata['form_step6'])) {
            $form6 = $this->formdata['form_step6'];
            $clefs = wizard_list_clef($form6);
            if (count($clefs)) {
                $this->myenrol_clef($course, $clefs);
            }
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
        $mgv .= '3. Cliquez sur l\'icône "coche verte" située dans la colonne "Actions" ;' . "\n";
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

            // rattachement secondaire
            $this->set_metadata_rof2();
            // metadonnee de rof1 et 2
            $tabrofpath = $this->get_tabrofpath($rof1['tabpath'], $this->formdata['rof2_tabpath']);
            $this->set_metadata_rof($tabrofpath);

            // id cacegory moodle rattachements secondaires
            $this->mydata->profile_field_up1categoriesbisrof = wizard_get_idcat_rof_secondaire($form2);

            $this->set_rof_shortname($rof1['idnumber']);
            $this->set_rof_fullname();
            $this->set_rof_nom_norm();
            $this->mydata->profile_field_up1complement = trim($form2['complement']);
            $this->mydata->profile_field_up1generateur = 'Manuel via assistant (cas n°2 ROF)';

        } else { // cas 3
            $this->mydata->course_nom_norme = $form2['fullname'];
            $this->mydata->profile_field_up1generateur = 'Manuel via assistant (cas n°3 hors ROF)';
            //rattachement hybride
            $this->set_metadata_rof2('form_step3');
            // id cacegory moodle rattachements secondaires
            $this->mydata->profile_field_up1categoriesbisrof = wizard_get_idcat_rof_secondaire($this->formdata['form_step3']);

            if (count($this->formdata['rof2_tabpath'])) {
                $this->set_metadata_rof($this->formdata['rof2_tabpath']);
            }
            $this->set_categories_connection();
        }

        $this->mydata->profile_field_up1datefermeture = $form2['up1datefermeture'];
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
        $this->mydata->profile_field_up1categoriesbis = '';
        $this->mydata->profile_field_up1composante = '';
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
            //$this->mydata->profile_field_up1niveaulmda = $rof1['up1niveaulmda'];
            //$this->mydata->profile_field_up1composante = $rof1['up1composante'];
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
    private function set_metadata_rof($tabpath) {
        $mdrof1 = rof_get_metadata_concat($tabpath);
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
     * @param string $form_step
     */
    private function set_metadata_rof2($form_step = 'form_step2') {
        $form2 = $this->formdata[$form_step];
        $rof2 = wizard_prepare_rattachement_second($form2);
        if (count($rof2)) {
            if (isset($rof2['rofchemin'])) {
                $this->set_wizard_session($rof2['rofchemin'], 'rattachement2', 'form_step2');
            }
            if (isset($rof2['rofid'])) {
                foreach($rof2['rofid'] as $rofid) {
                    $this->mydata->profile_field_up1rofid .= ';' . $rofid;
                }
            }
            if (isset($rof2['rofpathid'])) {
                foreach($rof2['rofpathid'] as $rofpath) {
                    $this->mydata->profile_field_up1rofpathid .= ';' . $rofpath;
                }
            }
            if (isset($rof2['rofname'])) {
                foreach($rof2['rofname'] as $rofname) {
                    $this->mydata->formdata['form_step2']['rofname_second'][] = $rofname;
                }
            }
            $this->formdata['rof2_tabpath'] = (isset($rof2['tabpath']) ? $rof2['tabpath'] : array());
        } else {
            $this->formdata['rof2_tabpath'] = array();
        }
    }

    /**
     * Construit le tabeau tabpath pour la fonction set_metadata_rof
     * (cf fonction rof_get_metadata_concat)
     * @param array $rof1tabpath
     * @param array $rof2tabpath (tableau de tableau)
     * @return array $tabpath (tableau de tableau)
     */
    private function get_tabrofpath($rof1tabpath, $rof2tabpath) {
        $tabpath[] = $rof1tabpath;
        if (count($rof2tabpath)) {
            foreach ($rof2tabpath as $rof2_tabpath) {
                $tabpath[] = $rof2_tabpath;
            }
        }
        return $tabpath;
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
                    if (isset($this->mydata->$fieldname) && trim($this->mydata->$fieldname) != '') {
                        $this->mydata->$fieldname .= ';';
                    }
                    $this->mydata->$fieldname .= $value;
                }
            }
            if (count($this->mydata->rattachements)) {
                $first = true;
                foreach ($this->mydata->rattachements as $rattachement) {
                    if (isset($this->mydata->profile_field_up1categoriesbis) && trim($this->mydata->profile_field_up1categoriesbis) != '') {
                        $this->mydata->profile_field_up1categoriesbis .= ';';
                    }
                    $this->mydata->profile_field_up1categoriesbis .= $rattachement;
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
        // rattachements secondaires
        if (isset($form2['rattachement2']) && count($form2['rattachement2'])) {
            $mg .= get_string('labelE7ratt2', 'local_crswizard') . ' : ';
            $racine = '';
            if ($this->formdata['wizardcase'] == 2) {
                $racine = $displaylist[$form2['category']];
            } elseif ($this->formdata['wizardcase'] == 3) {
                $tabcategories = get_list_category($form2['category']);
                $racine = $tabcategories[0] . ' / ' . $tabcategories[1];
            }
            $first = true;
            foreach ($form2['rattachement2'] as $formsecond) {
                $mg .= ($first ? '' : ', ') . $racine . ' / ' . $formsecond;
                $first = false;
            }
            $mg .=  "\n";
        }

        //cas3 - métadonnées supplémentaires
        if ($this->formdata['wizardcase'] == 3) {
            $metadonnees = get_array_metadonees();
            foreach ($metadonnees as $key => $label) {
                if (!empty($form3[$key])) {
                    $donnees = '';
                    foreach ($form3[$key] as $elem) {
                        $donnees = $donnees . $elem . ';';
                    }
                    $donnees = substr($donnees, 0, -1);
                    $mg .= $label . $donnees . "\n";
                }
            }
        }

        $mg .= get_string('fullnamecourse', 'local_crswizard') . $this->mydata->fullname . "\n";
        $mg .= get_string('shortnamecourse', 'local_crswizard') . $this->mydata->shortname . "\n";

        $mg .= get_string('coursestartdate', 'local_crswizard') . date('d-m-Y', $form2['startdate']) . "\n";
        $mg .= get_string('up1datefermeture', 'local_crswizard') . date('d-m-Y', $form2['up1datefermeture']) . "\n";

        if (!empty($this->formdata['form_step1']['coursedmodelid']) && $this->formdata['form_step1']['coursedmodelid'] != '0') {
            $mg .= get_string('coursemodel', 'local_crswizard') . '[' . $this->formdata['form_step1']['coursemodelshortname']
            . ']' . $this->formdata['form_step1']['coursemodelfullname'] . "\n";
        }

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
        if (isset($this->formdata['form_step6'])) {
            $form6 = $this->formdata['form_step6'];
            $clefs = wizard_list_clef($form6);
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

    /**
     * prépare les données du cours en vue de sa mise à jour
    */
    public function prepare_update_course() {
        if (isset($this->formdata['form_step3'])) {
            $this->mydata = (object) array_merge($this->formdata['form_step2'], $this->formdata['form_step3']);
        } else {
            $this->mydata = (object) $this->formdata['form_step2'];
        }
        $this->formdata['modif'] = array('identification' => false, 'attach' => false);
        $form2 = $this->formdata['form_step2'];
        $initc = $this->formdata['init_course'];

        if (isset($this->formdata['wizardcase']) && $this->formdata['wizardcase']=='2') {
            $changerof1 = $this->check_first_connection();
            $rof1 = wizard_prepare_rattachement_rof_moodle($form2, $changerof1);
            if ($changerof1 == false) {
                $rof1['idnumber'] = trim($this->formdata['init_course']['idnumber']);
            } else {
                $this->formdata['modif']['identification'] = true;
            }
            $this->set_param_rof1($rof1);
            // rattachement secondaire
            $this->set_metadata_rof2();
            $tabrofpath = $this->get_tabrofpath($rof1['tabpath'], $this->formdata['rof2_tabpath']);
            // metadonnee de rof1 et rof2
            $this->set_metadata_rof($tabrofpath);

            $this->set_rof_shortname($rof1['idnumber']);
            $this->mydata->profile_field_up1complement = trim($form2['complement']);
            $this->set_rof_fullname();
            $this->set_rof_nom_norm();

            // id cacegory moodle rattachements secondaires
            $this->mydata->profile_field_up1categoriesbisrof = wizard_get_idcat_rof_secondaire($form2);

            // log update attach
            $new = array();
            if (isset($this->formdata['form_step2']['item']['s'])) {
                $new = $this->formdata['form_step2']['item']['s'];
            }
            $old = array();
            if (isset($this->formdata['init_course']['form_step2']['item']['s'])) {
                $old = $this->formdata['init_course']['form_step2']['item']['s'];
            }
            if (count(array_diff($old, $new)) || count(array_diff($new, $old))) {
                $this->formdata['modif']['attach'] = true;
            }

        } else { // cas 3
            $this->mydata->course_nom_norme = $form2['fullname'];
            //rattachement hybride
            $this->set_metadata_rof2('form_step3');
            if (count($this->formdata['rof2_tabpath'])) {
                $this->set_metadata_rof($this->formdata['rof2_tabpath']);
            }
            $this->set_categories_connection();

            // log update rattach orthodoxe
            $old = array();
            if (isset($initc['profile_field_up1categoriesbis']) && $initc['profile_field_up1categoriesbis'] != '') {
                $old = explode(';', $initc['profile_field_up1categoriesbis']);
            }
            $new = array();
            if (isset($this->formdata['form_step3']['rattachements'])) {
                $new = $this->formdata['form_step3']['rattachements'];
            }
            if (count(array_diff($old, $new)) || count(array_diff($new, $old))) {
                $this->formdata['modif']['attach'] = true;
                if (count($new) == 0) {
                    $this->mydata->profile_field_up1categoriesbis = '';
                }
            }

            // id cacegory moodle rattachements secondaires
            $this->mydata->profile_field_up1categoriesbisrof = wizard_get_idcat_rof_secondaire($this->formdata['form_step3']);

            // log update rattach hybride
            $oldhyb = array();
            if (isset($initc['profile_field_up1rofid']) && $initc['profile_field_up1rofid'] != '') {
                $oldhyb = explode(';', $initc['profile_field_up1rofid']);
            }
            $newhyb = array();
            if (isset($this->formdata['form_step3']['item']['s'])) {
                $newhyb = $this->formdata['form_step3']['item']['s'];
            }
            if (count(array_diff($oldhyb, $newhyb)) || count(array_diff($newhyb, $oldhyb))) {
                $this->formdata['modif']['attach'] = true;
                if (count($newhyb) == 0) {
                    $this->formdata['modif']['attach2null'] = 1;
                }
            }

            //log
            if ($form2['fullname'] != $initc['fullname'] || $form2['shortname'] != $initc['shortname'] ) {
                $this->formdata['modif']['identification'] = true;
            }
            if ($form2['category'] != $initc['category']) {
                $this->formdata['modif']['attach'] = true;
            }
        }

        $this->mydata->profile_field_up1datefermeture = $form2['up1datefermeture'];
        $this->mydata->summary = $form2['summary_editor']['text'];
        $this->mydata->summaryformat = $form2['summary_editor']['format'];

        return $this->mydata;
    }

    /**
     * Vérifie si le premier rattachement ROF à été modifié
     * @return bool $check true si modification du rattachement principal
     */
    private function check_first_connection() {
        $check = false;
        $form2 = $this->formdata['form_step2'];
        if (isset($form2['item']) && count($form2['item']) == 1) {
            $allrof = $form2['item'];
            if (isset($allrof['p']) && count($allrof['p'])) {
                $rofpath = key($allrof['p']);
                $rofid = $allrof['p'][$rofpath];
                $apogee = rof_get_code_or_rofid($rofid);
                $up1rofid = trim($this->formdata['init_course']['profile_field_up1rofid']);
                $rofid = '';
                if (strstr($up1rofid, ';')) {
                    $tab = explode(';', $up1rofid);
                    $rofid = $tab[0];
                } else {
                    $rofid = $up1rofid;
                }
                $newapogee = rof_get_code_or_rofid($rofid);
                if ($newapogee != $apogee) {
                    $check = true;
                }
            }
        }
        return $check;
    }

    public function update_course() {
        $this->prepare_update_course();

        if ($this->formdata['modif']['identification']) {
            add_to_log($this->mydata->id, 'crswizard', 'update',
                'update/index.php?id=' . $this->mydata->id, 'Update Identification (course ' . $this->mydata->id . ' )');
        }
        if ($this->formdata['modif']['attach']) {
            add_to_log($this->mydata->id, 'crswizard', 'update',
                'update/index.php?id=' . $this->mydata->id, 'Update Rattachement (course ' . $this->mydata->id . ' )');
        }
        update_course($this->mydata);
        $custominfo_data = custominfo_data::type('course');
        $cleandata = $this->customfields_wash($this->mydata);

        // suppression total rattachement hybride
        if (isset($this->formdata['modif']['attach2null']) && $this->formdata['modif']['attach2null'] == 1) {
            $catsuppr = array('Identification', 'Diplome', 'Indexation');
            $custominfo_data->setCategoriesByNames($catsuppr);
            $fields = $custominfo_data->getFields(true);
            foreach ($fields as $tabfield) {
                foreach ($tabfield as $f) {
                    $name = 'profile_field_'.$f->shortname;
                    if (isset($cleandata->$name) == FALSE) {
                        $cleandata->$name = '';
                    }
                }
            }
        }

        $custominfo_data->save_data($cleandata);
        $modif = $this->update_myenrol_cohort();
        if ($modif) {
            add_to_log($this->mydata->id, 'crswizard', 'update',
                'update/index.php?id=' . $this->mydata->id, 'Update Cohorts (course ' . $this->mydata->id . ' )');
        }
        $modif = $this->update_myenrol_key();
        if ($modif) {
            add_to_log($this->mydata->id, 'crswizard', 'update',
                'update/index.php?id=' . $this->mydata->id, 'Update keys (course ' . $this->mydata->id . ' )');
        }
        rebuild_course_cache($this->mydata->id);
    }

    /**
     * met à jour (suppression/ajout), si besoin, la liste des inscriptions de cohortes
     * @return bool $modif
    */
    public function update_myenrol_cohort()
    {
        $modif = false;
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
        if (count($cohortadd)) {
            $modif = true;
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
        if (count($cohortremove)) {
            $modif = true;
        }
        wizard_unenrol_cohort($course['id'], $cohortremove);
        return $modif;
    }

    /**
     * met à jour (suppression/ajout), si besoin, la liste des clefs
     * @return bool $modif
    */
    function update_myenrol_key() {
        global $DB;
        $modif = false;
        $tabenrol = array('Etudiante' => 'self', 'Visiteur' => 'guest');

        if (isset($this->formdata['form_step6'])) {
            $form6 = $this->formdata['form_step6'];
            $newkey = wizard_list_clef($form6);
        }
        $initcourse = $this->formdata['init_course'];
        $course = $DB->get_record('course', array('id' => $initcourse['id']));
        $oldkey = array();
        if (isset($initcourse['key'])) {
            $oldkey = wizard_list_clef($initcourse['key']);

        }

        $nbdiffk = count($newkey) - count($oldkey);
        switch ($nbdiffk) {
            case -2:
                // supprimer toutes les clefs
                foreach ($oldkey as $role => $key) {
                    $enrol = $tabenrol[$role];
                    wizard_unenrol_key ($enrol, $course);
                }
                $modif = true;
                break;
             case 2:
                $this->myenrol_clef($course, $newkey);
                $modif = true;
                break;
            default:
                foreach ($newkey as $role => $key) {
                    $enrol = $tabenrol[$role];
                    if (array_key_exists($role, $oldkey)) {
                        //update
                        if (wizard_update_enrol_key($enrol, $course, $key)) {
                            $modif = true;
                        }
                    } else {
                        $this->myenrol_clef($course, array($role => $key));
                        $modif = true;
                    }
                }
                // suppression
                foreach ($oldkey as $role => $key) {
                if (array_key_exists($role, $newkey) == false) {
                    // suppression
                    $enrol = $tabenrol[$role];
                    wizard_unenrol_key ($enrol, $course);
                    $modif = true;
                }
            }
        }
        return $modif;
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

/**
 * cette classe utilise le compte "administrateur principal" (renvoyé par
 * get_admin()) pour créer le backup et effectuer la restauration. Besoin des
 * permissions moodle/course:create, moodle/restore:restorecourse et
 * moodle/backup:backupcourse au niveau de la plateforme
 */
class wizard_modele_duplicate {

    protected $adminuser;
    public $courseid;
    public $newcoursedata;
    public $backupid;
    public $backupbasepath;
    public $file;
    public $backupsettings = array();

    public $backupdefaults = array(
        'activities' => 1,
        'blocks' => 1,
        'filters' => 1,
        'users' => 0,
        'role_assignments' => 0,
        'comments' => 0,
        'userscompletion' => 0,
        'logs' => 0,
        'grade_histories' => 0
    );

    public function __construct($courseid, $mydata, $options) {
        $this->courseid = $courseid;
        $this->mydata = $mydata;
        $this->options = $options;
        $this->adminuser = get_admin();
    }

    public function create_backup() {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Check for backup and restore options.
        if (!empty($this->options)) {
            foreach ($this->options as $option) {

                // Strict check for a correct value (allways 1 or 0, true or false).
                $value = clean_param($option['value'], PARAM_INT);

                if ($value !== 0 and $value !== 1) {
                    throw new moodle_exception('invalidextparam', 'webservice', '', $option['name']);
                }

                if (!isset($this->backupdefaults[$option['name']])) {
                    throw new moodle_exception('invalidextparam', 'webservice', '', $option['name']);
                }

                $this->backupsettings[$option['name']] = $value;
            }
        }

        $bc = new backup_controller(backup::TYPE_1COURSE, $this->courseid, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $this->adminuser->id);

        foreach ($this->backupsettings as $name => $value) {
            $bc->get_plan()->get_setting($name)->set_value($value);
        }

        $this->backupid       = $bc->get_backupid();
        $this->backupbasepath = $bc->get_plan()->get_basepath();

        $bc->execute_plan();
        $results = $bc->get_results();
        $this->file = $results['backup_destination'];

        $bc->destroy();

    }


    public function retore_backup() {
        global $CFG,$DB;
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        // Check if we need to unzip the file because the backup temp dir does not contains backup files.
        if (!file_exists($this->backupbasepath . "/moodle_backup.xml")) {
            $this->file->extract_to_pathname(get_file_packer(), $this->backupbasepath);
        }

         // Create new course.

        $newcourseid = restore_dbops::create_new_course($this->mydata->fullname,
            $this->mydata->shortname, $this->mydata->category);

        $rc = new restore_controller($this->backupid, $newcourseid,
                backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $this->adminuser->id, backup::TARGET_NEW_COURSE);

        foreach ($this->backupsettings as $name => $value) {
            $setting = $rc->get_plan()->get_setting($name);
            if ($setting->get_status() == backup_setting::NOT_LOCKED) {
                $setting->set_value($value);
            }
        }

        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($backupbasepath);
                }

                $errorinfo = '';

                foreach ($precheckresults['errors'] as $error) {
                    $errorinfo .= $error;
                }

                if (array_key_exists('warnings', $precheckresults)) {
                    foreach ($precheckresults['warnings'] as $warning) {
                        $errorinfo .= $warning;
                    }
                }

                throw new moodle_exception('backupprecheckerrors', 'webservice', '', $errorinfo);
            }
        }

        $rc->execute_plan();
        $rc->destroy();

        $course = $DB->get_record('course', array('id' => $newcourseid), '*', MUST_EXIST);

        $course->fullname = $this->mydata->fullname;
        $course->shortname = $this->mydata->shortname;
        $course->visible = $this->mydata->visible;
        $course->startdate = $this->mydata->startdate;
        $course->summary       = $this->mydata->summary_editor['text'];
        $course->summaryformat = $this->mydata->summary_editor['format'];



        // Set shortname and fullname back.
        $DB->update_record('course', $course);

        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($this->backupbasepath);
        }

        // Delete the course backup file created by this WebService. Originally located in the course backups area.
        $this->file->delete();

        return $course;

    }
}
