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
list($options, $unrecognized) = cli_get_params(array('help'=>false, 'dryrun'=>false, 'cleanall'=>false, 'verb'=>1),
                                               array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"Synchronize ROF cache from CDM-FR webservice

Options:
--verb=N              Verbosity (0 to 3), 1 by default
--cleanall            Do not sync, but clean all 5 rof_ tables
--dryrun              Simulation mode: do not change anything in the database
-h, --help            Print out this help

";

if ( ! empty($options['help']) ) {
    echo $help;
    return 0;
}



if ($options['cleanall']) {
    rofCleanAll();
    return 0;
}
else {
    rofGlobalSync($options['verb'], $options['dryrun']);
    echo "\n\n";
    return 0;
}