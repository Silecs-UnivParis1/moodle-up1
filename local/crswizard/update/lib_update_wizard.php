<?php

require_once("$CFG->dirroot/local/roftools/roflib.php");

function wizard_get_course($id) {
    global $DB, $SESSION;
    $error = '';
    if ($id == SITEID){
        // don't allow editing of  'site course' using this from
        $error = 'cannoteditsiteform';
    }
    $course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
    if ($course) {
        $SESSION->wizard['form_step2'] = (array) $course;
         //Load custom fields data
        $custominfo_data = custominfo_data::type('course');
        $custominfo_data->load_data($course);
        $SESSION->wizard['init_course'] = (array) $course;

        $SESSION->wizard['form_step2']['up1datefermeture'] = $course->profile_field_up1datefermeture;

        // determiner le cas sur profile_field_up1generateur (faute de mieux)
        if (isset($course->profile_field_up1generateur)) {
            $SESSION->wizard['wizardcase'] = wizard_get_up1generateur($course->profile_field_up1generateur);
        }
        if ($SESSION->wizard['wizardcase'] == 2) {
            $summary = array('text' => $course->summary, 'format' => $course->summaryformat);
            $SESSION->wizard['form_step2']['summary_editor'] = $summary;
            $idcategory = $SESSION->wizard['form_step2']['category'];
            $tabpath = wizard_get_categorypath($idcategory);
            $SESSION->wizard['form_step2']['category'] = $tabpath[2];
            $SESSION->wizard['form_step2']['rofestablishment'] = wizard_get_wizard_get_categoryname($tabpath[2]);
            $SESSION->wizard['form_step2']['rofyear'] = wizard_get_wizard_get_categoryname($tabpath[1]);
            $SESSION->wizard['form_step2']['complement'] = $course->profile_field_up1composition;
            $SESSION->wizard['form_step2']['fullname'] = $course->profile_field_up1rofname;

            // on peut vérifier si le premier rattachement est cohérent avec le reste des données
            wizard_rof_connection($course->profile_field_up1rofpathid);
            $SESSION->wizard['form_step2']['all-rof'] = wizard_get_rof();

            $SESSION->wizard['init_course']['form_step2']['item'] = $SESSION->wizard['form_step2']['item'];
            $SESSION->wizard['init_course']['form_step2']['path'] = $SESSION->wizard['form_step2']['path'];



        } elseif($SESSION->wizard['wizardcase'] == 3) {
            if (isset($course->profile_field_up1categoriesbis)) {
                $SESSION->wizard['form_step3']['rattachements'] = explode(';', $course->profile_field_up1categoriesbis);
            }
            // identité du demandeur
            $userid = (int) $course->profile_field_up1demandeurid;
            $user = $DB->get_record('user', array('id'=>$userid));
            $SESSION->wizard['form_step3']['user_name'] = fullname($user);
            $SESSION->wizard['form_step3']['user_login'] = $user->username;
            $SESSION->wizard['form_step3']['requestdate'] = $course->timecreated;
        }

        //inscription cohortes
        $SESSION->wizard['form_step5']['group'] = wizard_get_cohorts($course->id);
        $SESSION->wizard['init_course']['group'] = wizard_get_cohorts($course->id);
        $SESSION->wizard['form_step5']['all-cohorts'] = wizard_get_enrolement_cohorts();

        // clefs
        $SESSION->wizard['form_step6'] = wizard_get_keys($course->id, $course->timecreated);
        $SESSION->wizard['init_course']['key'] = wizard_get_keys($course->id, $course->timecreated);
    }

}

/**
 * détermine si le cours est rattaché au ROF
 * @param string $generator valeur du champ profile_field_up1generateur
 * @return int (0, 2 ou 3)
 */
function wizard_get_up1generateur($generator) {
    $up1generateur = 0;
    if ($generator != '') {
        if (stristr($generator, '2 ROF')) {
            $up1generateur = 2;
        } elseif (stristr($generator, '3 hors ROF')) {
            $up1generateur = 3;
        }
    }
    return $up1generateur;
}

/**
 * renvoie le champ path de la catégorie sous la forme d'un tableau
 * @param int $id idendentifiant de la catégorie
 * @return array
 */
function wizard_get_categorypath($id) {
    global $DB;
    $path = $DB->get_field('course_categories', 'path', array('id' => $id));
    $tpath = explode('/', $path);
    return $tpath;
}

function wizard_get_wizard_get_categoryname($id) {
    global $DB;
    $name = $DB->get_field('course_categories', 'name', array('id' => $id));
    return $name;
}

function wizard_get_cohorts($courseid) {
    global $DB;
    $list = array();
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_cohort;
    $roles = wizard_role($labels);
    $roleint = array();
    foreach ($roles as $role) {
        $roleint[$role['id']] = $role['shortname'];
    }
    $enrols = $DB->get_records('enrol', array('courseid' => $courseid, 'enrol' => 'cohort'));
    foreach ($enrols as $enrol) {
        $cohortname = $DB->get_field('cohort', 'idnumber', array('id' => $enrol->customint1));
        if ($cohortname) {
            $list[$roleint[$enrol->roleid]][] = $cohortname;
        }
    }
    return $list;
}

function wizard_get_keys($courseid, $coursetimecreated) {
    global $DB;
    $list = array();
    $tabkeys = array('u' => 'self', 'v' => 'guest');
    foreach ($tabkeys as $k => $role) {
        $key = $DB->get_record('enrol', array('courseid' => $courseid,
            'timecreated' => $coursetimecreated,
            'enrol' => $role));
        if ($key) {
            $list['password'.$k] = $key->password;
            $list['enrolenddate'.$k] = $key->enrolenddate;
            $list['enrolstartdate'.$k] = $key->enrolstartdate;
            $list['idenrol'][$role] = $key->id;
        }
    }
    return $list;
}

function wizard_rof_connection($up1rofpathid) {
    global $SESSION;
    $tabpath = explode(';', trim($up1rofpathid));
    foreach ($tabpath as $pos => $path) {
        $rofid = substr(strrchr($path, '/'), 1);
        $newpath = strtr($path, '/', '_');
        if ($pos == 0 ) {
            $SESSION->wizard['form_step2']['item']['p'][] = $rofid;
             $SESSION->wizard['form_step2']['path'][$rofid] = substr($newpath, 1);
        } else {
            $SESSION->wizard['form_step2']['item']['s'][] = $rofid;
            $SESSION->wizard['form_step2']['path'][$rofid] = $newpath;
        }
    }
}

?>
