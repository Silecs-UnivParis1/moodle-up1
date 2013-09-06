<?php

require_once("$CFG->dirroot/local/roftools/roflib.php");

function wizard_get_course($id) {
    global $DB, $SESSION, $CFG;
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
        $summary = array('text' => $course->summary, 'format' => $course->summaryformat);
        $SESSION->wizard['form_step2']['summary_editor'] = $summary;

        $case = wizard_get_generateur($course);
        if ($case == 0) {
             throw new moodle_exception('Vous n\'avez pas la permission d\'accéder à cette page.');
        } else {
            $SESSION->wizard['wizardcase'] = $case;
        }

        if ($SESSION->wizard['wizardcase'] == 2) {
            $idcategory = $SESSION->wizard['form_step2']['category'];
            $tabpath = wizard_get_categorypath($idcategory);
            $SESSION->wizard['form_step2']['category'] = $tabpath[2];
            $SESSION->wizard['form_step2']['rofestablishment'] = wizard_get_wizard_get_categoryname($tabpath[2]);
            $SESSION->wizard['form_step2']['rofyear'] = wizard_get_wizard_get_categoryname($tabpath[1]);
            $SESSION->wizard['form_step2']['complement'] = $course->profile_field_up1complement;
            $SESSION->wizard['form_step2']['fullname'] = $course->profile_field_up1rofname;
            if (strpos($course->profile_field_up1rofid, ';') && strpos($course->profile_field_up1rofname, ';')) {
                $SESSION->wizard['form_step2']['fullname'] = substr($course->profile_field_up1rofname, 0, strpos($course->profile_field_up1rofname, ';'));
            }

            // on peut vérifier si le premier rattachement est cohérent avec le reste des données
            wizard_rof_connection($course->profile_field_up1rofpathid);
            $SESSION->wizard['form_step2']['all-rof'] = wizard_get_rof();
            $SESSION->wizard['init_course']['form_step2']['item'] = $SESSION->wizard['form_step2']['item'];

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

            wizard_rof_connection($course->profile_field_up1rofpathid, false, 'form_step3');

            $SESSION->wizard['form_step3']['all-rof'] = wizard_get_rof('form_step3');
            $SESSION->wizard['init_course']['form_step3']['item'] = $SESSION->wizard['form_step3']['item'];
        }

        //inscription cohortes
        $SESSION->wizard['form_step5']['group'] = wizard_get_cohorts($course->id);
        $SESSION->wizard['init_course']['group'] = wizard_get_cohorts($course->id);
        $SESSION->wizard['form_step5']['all-cohorts'] = wizard_get_enrolement_cohorts();

        // clefs
        $SESSION->wizard['form_step6'] = wizard_get_keys($course->id, $course->timecreated);
        $SESSION->wizard['init_course']['key'] = $SESSION->wizard['form_step6'];
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

/**
 * construit
 * @param array $up1rofpathid
 * @param bool $case2
 * @param string $form_step
 */
function wizard_rof_connection($up1rofpathid, $case2=TRUE, $form_step = 'form_step2') {
    global $SESSION;
    $up1rofpathid = trim($up1rofpathid);
    $tabpath = explode(';', $up1rofpathid);
    foreach ($tabpath as $pos => $path) {
        $rofid = substr(strrchr($path, '/'), 1);
        $newpath = strtr($path, '/', '_');
        if ($case2 && $pos == 0) {
            $SESSION->wizard[$form_step]['item']['p'][substr($newpath, 1)] = $rofid;
        } else {
            $idpath = $newpath;
            if (substr($newpath, 0, 1) == '_') {
                 $idpath = substr($newpath, 1);
            }
            if ($idpath == '') {
                $SESSION->wizard[$form_step]['item'] = array();
            } else {
                $SESSION->wizard[$form_step]['item']['s'][$idpath] = $rofid;
            }
            if ($case2 == FALSE && $rofid !== FALSE) {
                $SESSION->wizard['init_course']['form_step3']['rattachement2'][] = $idpath;
            }
        }
    }
}

/**
 * determine
 * @param object course $course
 * @return int
 */
function wizard_get_generateur($course) {
    global $DB;
    $case = 0;
    if (isset($course->profile_field_up1generateur) && trim($course->profile_field_up1generateur) != '') {
            $case = wizard_get_up1generateur(trim($course->profile_field_up1generateur));
            if ($case == 2) {
                $trofid = explode(';', $course->profile_field_up1rofid);
                $r1 = trim($trofid[0]);
                $tablerof = (substr($r1, 0, 5) == 'UP1-P' ? 'rof_program' : 'rof_course');
                $rof = $DB->get_record($tablerof,  array('rofid' => $r1));
                if (!$rof) {
                    return 0;
                } else {
                    return $case;
                }
                /**
                $posname = strripos(trim($course->profile_field_up1rofname), trim($rof->name));
                $poslocalname = strripos(trim($course->profile_field_up1rofname), trim($rof->localname));
                if ($posname ===0 || $lengthlocalname == $poslocalname) {
                    if (substr($r1, 0, 5) == 'UP1-P') {
                        return $case;
                    } elseif (substr($r1, 0, 5) == 'UP1-C') {
                        $poscode = strripos(trim($course->profile_field_up1code), trim($rof->code));
                        if ($poscode====0) {
                            return $case;
                        } else {
                            return 0;
                        }
                    } else {
                        return 0;
                    }
                } else {
                   return 0;
                }
                **/
            } elseif($case == 3) {
                //verifier si categorie est une feuille
                $nbsel = $DB->count_records('course_categories', array('id' => $course->category));
                if ($nbsel != 1) {
                    return 0;
                }
                $cat = $DB->get_record('course_categories',  array('id' => $course->category));
                if ($cat && ($cat->depth > 2)) {
                    $enf = $DB->get_field('course_categories', 'id', array('parent' => $cat->id));
                    if ($enf) {
                        return 0;
                    }
                } else {
                    return 0;
                }
            }
        } else {
            return 0;
        }
    return $case;
}
?>
