<?php

define('NO_OUTPUT_BUFFERING', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/report/rofstats/locallib.php');
require_once($CFG->dirroot.'/local/roftools/rofcourselib.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();
$rofid = required_param('rofid', PARAM_ALPHANUMEXT); //
$path = optional_param('path', null, PARAM_ALPHANUMEXT); //

// Print the header.
$table = rof_get_table($rofid);
admin_externalpage_setup('reportrofstats', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();
echo $OUTPUT->heading('Détails de ' . $rofid ." ($table)");

$url = "$CFG->wwwroot/report/rofstats/index.php";

$browserurl = "$CFG->wwwroot/local/rof_browser/rof_browser.php";


list($record, $top) = rof_get_record($rofid);
if ( ! $record ) {
    echo "Mauvais identifiant (rofid) : $rofid.";

} else {

    echo '<div>';
    echo '<div><span><b>Nom effectif</b> ' . rof_get_combined_name($rofid) . '</span><br />';
    echo '<span>S\'il est défini, le nom local supplante le nom officiel de l\'élément ROF lors d\'une création par l\'assistant.</span></div>';
    /**/
    echo '<form action="update_localname.php" method="POST">';
    echo '<label for="localname">Nom local </label>';
    echo '<input type="text" value="' . $record->localname . '" name="localname" id="localname" size="80" />';
    echo '<input type="hidden" name="rofid" value="' . $rofid . '"/>';
    echo '</form>';
    echo '</div>';
    echo '<p></p>';

    rof_view_record($rofid);
    if ($table == 'rof_program' || $table == 'rof_course') {

        /******************/
        echo "<h3>Chemins</h3>\n";
        if (isset($path)) {
            echo "Chemin d'accès : <br />\n";
            $accPath = explode('_', $path);
            echo rof_format_path(rof_get_combined_path($accPath), 'combined', true);
            echo "<br />\n<br />\n";
        }

        echo "Tous les chemins : <br />\n";
        $allPaths = rof_get_course_all_paths($rofid);
        $allPathnames = rof_get_course_all_pathnames($allPaths);
        echo '<ol>';
        foreach ($allPathnames as $pathname) {
            echo '<li>' . rof_format_path($pathname, 'combined', true) . '</li>';
        }
        echo '</ol>';

        /******************/
        if ($table == 'rof_course') {
            echo "<h3>Métadonnées</h3>\n";
            echo rof_format_metadata(rof_get_metadata($rofid));
        }

        /******************/
        echo "<h3>Cours référençant cet objet</h3>";
        $referencingcourses = rof_object_is_referenced_by($rofid);
        echo "\n<ul>\n";
        foreach ($referencingcourses as $crsid => $name) {
            $url = new moodle_url('/course/view.php', array('id' => $crsid));
            $link = html_writer::link($url, $name);
            echo "<li>$link</li>\n";
        }
        echo "</ul>\n";
    }
}

echo '<p><a href="' . $browserurl. '">Navigateur ROF</a></p>';
echo $OUTPUT->footer();
