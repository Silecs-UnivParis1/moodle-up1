<?php
require_once('../../config.php');
require_once('libbrowser.php');

// limite cette page au compte admin


$niveau = optional_param('niveau', NULL,PARAM_INT);
$rofid = optional_param('rofid',NULL,PARAM_ALPHANUMEXT);

$format = optional_param('format',NULL,PARAM_ALPHANUMEXT);
$action = optional_param('action',NULL,PARAM_ALPHANUMEXT);
$detail = optional_param('detail',NULL,PARAM_INT);

$selected = optional_param('selected',NULL,PARAM_ALPHANUMEXT);

$rb = new rof_browser;

if (array_key_exists ($niveau, $rb->tabNiveau)) {
	$rb->setNiveau($niveau);
	if (isset($rofid)) {
		$rb->setRofid($rofid);
	}
	if (isset($selected)) {
		$rb->setSelected($selected);
	} else {
		$rb->setSelected(0);
	}

    $htmlblock = $rb->createBlock();

	echo $htmlblock;
} else {
	echo '<p>ERREUR</p>';
}

