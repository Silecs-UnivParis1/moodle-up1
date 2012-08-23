<?php
require_once('../../config.php');
require_once('libbrowser.php');

// limite cette page au compte admin


$niveau = optional_param('niveau', NULL,PARAM_INT);
$id =  optional_param('id',NULL,PARAM_INT);

$format = optional_param('format',NULL,PARAM_ALPHANUMEXT);
$action = optional_param('action',NULL,PARAM_ALPHANUMEXT);
$detail = optional_param('detail',NULL,PARAM_INT);

$rb = new rof_browser;

if (array_key_exists ($niveau, $rb->tabNiveau)) {
	$rb->setIdPere($id);
	$rb->setNiveau($niveau);

    $htmlblock = $rb->createBlock();

	echo $htmlblock;
} else {
	echo '<p>ERREUR</p>';
}

