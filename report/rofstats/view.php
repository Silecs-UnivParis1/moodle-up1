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

if (rof_view_record($rofid)) {
    if ($table == 'rof_program' || $table == 'rof_course') {
        echo "Premier chemin : <br />\n";
        echo fmtPath(getCourseFirstPath($rofid), 'ul', true);

        echo "Tous les chemins : <br />\n";
        $allPaths = getCourseAllPaths($rofid);
        $allPathnames = getCourseAllPathnames($allPaths);
        echo '<ol>';
        foreach ($allPathnames as $pathname) {
            echo '<li>' . fmtPath($pathname, 'combined', true) . '</li>';
        }
        echo '</ol>';

        if ($table == 'rof_course') {
            echo "Métadonnées : <br />\n";
            echo fmt_rof_metadata(rof_get_metadata($rofid));
        }
    }
}

echo '<p><a href="' . $browserurl. '">Navigateur ROF</a></p>';
echo $OUTPUT->footer();
