<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once($CFG->dirroot.'/report/rofstats/locallib.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();
$rofid = required_param('rofid', PARAM_ALPHANUMEXT); //

// Print the header.
$table = rofGetTable($rofid);
admin_externalpage_setup('reportrofstats', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();
echo $OUTPUT->heading('Détails de ' . $rofid ." ($table)");

$url = "$CFG->wwwroot/report/rofstats/index.php";

$browserurl = "$CFG->wwwroot/local/rof_browser/rof_browser.php";
echo '<div><a href="' . $browserurl. '">ROF browser</a></div>';


rof_view_record($rofid);
if ($table == 'rof_program' || $table == 'rof_course') {
    echo "Premier chemin : <br />\n";
    echo fmtPath(getCourseFirstPath($rofid), 'ul', true);

    echo "Tous les chemins : <br />\n";
    $allPaths = getCourseAllPaths($rofid);
    $allPathnames = getCourseAllPathnames($allPaths);
    foreach ($allPathnames as $pathname) {
        echo fmtPath($pathname, 'combined', true);
        echo "<br \>\n";
    }
}

echo '<div><a href="' . $browserurl. '">ROF browser</a></div>';
echo $OUTPUT->footer();
