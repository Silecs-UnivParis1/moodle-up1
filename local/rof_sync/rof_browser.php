<?php
require_once('../../config.php');
require_once('libbrowser.php');

// limite cette page au compte admin
require_login();
$systemcontext   = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($systemcontext);
has_capability('enrol/cohort:unenrol', $systemcontext);

$PAGE->set_url('/local/rof_sync/rof_browser.php');
$PAGE->set_title('Components browser');

echo $OUTPUT->header();

$components = getRofComponents();
$programs = getArrayRofFathersPrograms();
echo $OUTPUT->box_start('block_navigation  block');

echo '<ul>';
foreach ($components as $c) {
	if (array_key_exists ($c->number, $programs)) {
		$prog = $programs[$c->number];
		$nbProg = count($prog);
		echo '<li><span style="cursor: pointer;" onclick="javascript:visibilite(\'liste_'
	    . $c->number . '\'); return false;">' . htmlspecialchars($c->name) . ' (' . $nbProg . ')</span>';
		if ($nbProg) {
			echo '<ul id="liste_'.$c->number.'" style="display:none;">';
			foreach ($prog as $p) {
				echo '<li>' . htmlspecialchars($p->name) . '</li>';
			}
			echo '</ul>';
		}
		echo '</li>';

	} else {
		echo '<li><span>' . htmlspecialchars($c->name) . '</span></li>';
	}
}
echo '</ul>';

echo $OUTPUT->box_end();
echo '<script type="text/javascript">';
echo "//<![CDATA[\n";

echo 'function visibilite(thingId)'
	. '{'
	. 'var targetElement;'
	. 'targetElement = document.getElementById(thingId);'
	. 'if (targetElement.style.display == "none")'
	. '{'
	. 'targetElement.style.display = "" ;'
	. '} else {'
	. 'targetElement.style.display = "none" ;'
	. '}'
	. '}'
	. ';';

echo "//]]>\n";
echo '</script>';
echo $OUTPUT->footer();
