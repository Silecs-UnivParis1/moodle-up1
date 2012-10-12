<?php

define('NO_OUTPUT_BUFFERING', false);
global $DB;
require('../../config.php');
require_once($CFG->dirroot.'/report/rofstats/locallib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/course/report/synopsis/locallib.php');

require_login();
// $idnumber = required_param('idnumber', PARAM_ALPHANUMEXT); //code Apogée
$id = required_param('id', PARAM_INT);  // course id

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$context_sys = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/site:approvecourse', $context_sys); //** @todo trouver une meilleure capacité

$PAGE->set_context($context_sys);
$PAGE->set_url('/local/courseboard/view.php', array('id' => $id));
$PAGE->set_pagelayout('course'); //** @todo nécessaire ici ?
$PAGE->set_title($course->shortname .': '. "tableau de bord");
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading('Tableau de bord ' . $course->shortname );

// $url = "$CFG->wwwroot/local/courseboard/view.php";
// $browserurl = "$CFG->wwwroot/local/rof_browser/rof_browser.php";

echo "<h2>Description</h2>\n";
echo "<ul>\n";
echo "<li>Abrégé : ". $course->shortname ."</li>\n";
echo "<li>Code Apogée : ". ( ! empty($course->idnumber) ? $course->idnumber : '<b>NON renseigné</b>') ."</li>\n";
echo "</ul>\n";

// custom info data
html_custom_data($course);

// ROF data
    if ( ! empty($course->idnumber) ) {
        $rofcourse = $DB->get_record('rof_course', array('code' => $course->idnumber));
    } else {
        $rofcourse = FALSE;
    }

    if ( $rofcourse ) {
        $rofid = $rofcourse->rofid;
        echo "<h3>Métadonnées ROF</h3>\n";
        echo fmt_rof_metadata(rof_get_metadata($rofid));

        echo "<h3>Tous les chemins : </h3>\n";
        $allPaths = getCourseAllPaths($rofid);
        $allPathnames = getCourseAllPathnames($allPaths);
        echo '<ol>';
        foreach ($allPathnames as $pathname) {
            echo '<li>' . fmtPath($pathname, 'combined', true) . '</li>';
        }
        echo '</ol>';
    } else {
        echo "<h3>Pas de ROF</h3>\n";
        echo "<p>Aucun cours correspondant n'existe dans le ROF avec ce code <b>$course->idnumber</b>.</p>\n";
    }


// echo '<p><a href="' . $browserurl. '">Navigateur ROF</a></p>';
echo $OUTPUT->footer();
