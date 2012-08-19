<?php
    require_once('../../config.php');
    require_once('libbrowser.php');

    $PAGE->set_url('/local/rof_sync/rof_browser.php');
    $PAGE->set_title('Components browser');

echo $OUTPUT->header();

$components = getRofComponents();
$programs = getArrayRofFathersPrograms();
echo $OUTPUT->box_start('block_navigation  block');

echo '<ul>';
foreach ($components as $c) {
	$prog = $programs[$c->number];
	$nbProg = count($prog);
	echo '<li><span '.($nbProg?'style="cursor: pointer;"':'') . ' onclick="javascript:visibilite(\'liste_'
	    . $c->number . '\'); return false;">' . $c->name . ' ('.$nbProg.')</span>';
	if ($nbProg) {
		echo '<ul id="liste_'.$c->number.'" style="display:none;">';
		foreach ($prog as $p) {
			echo '<li>' . $p->name . '</li>';
		}
		echo '</ul>';
	}
	echo '</li>';
}
echo '</ul>';

echo $OUTPUT->box_end();
echo '<script type="text/javascript">';
echo "//<![CDATA[\n";
echo 'function visibilite(thingId)
{
var targetElement;
targetElement = document.getElementById(thingId) ;
if (targetElement.style.display == "none")
{
targetElement.style.display = "" ;
} else {
targetElement.style.display = "none" ;
}
}
';
echo "//]]>";
echo '</script>';
echo '</body></html>';
//echo $OUTPUT->footer();
