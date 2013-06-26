<?php

/**
 * @package    local
 * @subpackage courseboard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('NO_OUTPUT_BUFFERING', false);

global $CFG, $DB, $PAGE, $OUTPUT;

require('../../config.php');
require_once($CFG->dirroot . '/local/roftools/roflib.php'); // to get ROF data
require_once($CFG->dirroot . '/local/up1_metadata/lib.php'); // to get prepared metadata
require_once($CFG->libdir . '/adminlib.php');
require_once('locallib.php');

require_login();
$crsid = required_param('id', PARAM_INT);  // course id

$course = $DB->get_record('course', array('id' => $crsid), '*', MUST_EXIST);
$context_sys = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/site:approvecourse', $context_sys); //** @todo trouver une meilleure capacité

$PAGE->set_context($context_sys);
$PAGE->set_url('/local/courseboard/view.php', array('id' => $crsid));
$PAGE->set_pagelayout('course'); //** @todo nécessaire ici ?
$PAGE->set_title($course->shortname . ': ' . "tableau de bord");
$PAGE->set_heading($course->fullname . ' : ' . "tableau de bord");
$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js(new moodle_url('/local/courseboard/js/cell_update.js'), true);

echo $OUTPUT->header();
echo $OUTPUT->heading('Tableau de bord ' . $course->shortname);

// $url = "$CFG->wwwroot/local/courseboard/view.php";
$browserurl = "$CFG->wwwroot/local/rof_browser/rof_browser.php";

echo "<h2>Description</h2>\n";
echo "<ul>\n";
echo "<li>Nom complet : " . $course->fullname . "</li>\n";
echo "<li>Nom abrégé : " . $course->shortname . "</li>\n";
echo "<li>No identification : " . (!empty($course->idnumber) ? $course->idnumber : '<b>NON renseigné</b>') . "</li>\n";
echo "</ul>\n";

// ROF data
$rofpathids = up1_meta_get_text($crsid, 'up1rofpathid');
$rofdata = array();
if ($rofpathids) {
    /// Vérification de l'existence dans le cache ROF : probablement inutile
    // $insql = "('" . join("', '", explode(';', $rofids)) . "')";
    // $sql = "SELECT rofid FROM {rof_course} rc WHERE rofid IN " . $insql;
    // $rofcourses = $DB->get_fieldset_sql($sql);
    $rofpaths = explode(';', $rofpathids);
    $rattachements = array();
    foreach ($rofpaths as $rofpath) {
        $rattachements[] = array_filter(explode('/', $rofpath));
    }
    $rofdata = array_map('rof_get_metadata', $rattachements);

} else {
    $rattachements = FALSE;
    $rofpathids = FALSE;
    echo "<h3>Pas de ROF</h3>\n";
    echo "<p>Aucun cours correspondant n'existe dans le ROF avec ce code <b>$course->idnumber</b>.</p>\n";
}


print_table_course_vs_rof($crsid, $rofdata);

if ($rattachements) {
    echo "<h3>Tous les chemins du rattachement principal : </h3>\n";
    $rattachementleaf = array_pop($rattachements[0]);
    $allPaths = rof_get_course_all_paths($rattachementleaf);
    $allPathnames = rof_get_course_all_pathnames($allPaths);
    echo '<ol>';
    foreach ($allPathnames as $pathname) {
        echo '<li>' . rof_format_path($pathname, 'combined', true) . '</li>';
    }
    echo '</ol>';
} else {

}
// echo '<p><a href="' . $browserurl . '">Navigateur ROF</a></p>';

echo '<h2 id="course-log">Journal du cours</h2>' . "\n";

print_admin_log($crsid);

echo '<form action="add_memo.php" method="POST">';
echo '<label for="memo">Nouveau mémo </label>';
echo '<input type="text" name="memo" id="memo" required="required" size="100" />';
echo '<input type="hidden" name="crsid" value="' . $crsid . '"/>';
echo '</form>';


echo $OUTPUT->footer();
