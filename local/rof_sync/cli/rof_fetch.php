<?php

/**
 * @package    local
 * @subpackage rof_sync
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require('../locallib.php');

// now get cli options
list($options, $unrecognized) = cli_get_params(array('component'=>false, 'program'=>false, 'help'=>false),
                                               array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"ROF fetch clients and outputs a raw XML file from SOAP webservice

Options:
--component=<NN>      01 à 37
--program=<rofid>     ex. UP1-PROG28336 program ; UP1-PROG28337 subprogram
-h, --help            Print out this help

";

if ( ! empty($options['help']) ) {
    echo $help;
    return 0;
}


if ($options['component']) {
    $reqParams = array(
        '_cmd' => 'getAllFormations',
        '_lang' => 'fr-FR',
        '__composante' => $options['component'],  // 01 à 37
        '__1' => '__composante',
    );
    $xml = doSoapRequest($reqParams);
    $fp = fopen("comp".$options['component'] .".xml", "w");
    fwrite($fp, $xml);
    fclose ($fp);
    return 0;
}


if ($options['program']) {
    $reqParams = array(
        '_cmd' => 'getFormation',
        '_lang' => 'fr-FR',
        '_oid' => $options['program'],
    );
    $xml = doSoapRequest($reqParams);
    $fp = fopen("prog" . substr(strtolower($options['program']), 8) .".xml", "w");
    fwrite($fp, $xml);
    fclose ($fp);
    return 0;
}