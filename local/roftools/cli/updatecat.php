<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__DIR__))).'/config.php'); // global moodle config file.
require(dirname(__DIR__) . '/updatelib.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions

// now get cli options
list($options, $unrecognized) = cli_get_params(
    array('help'=>false, 'list-old'=>false, 'up-idnumber'=>false, 'year'=>'', 'etab'=>''),
    array('h'=>'help')
    );

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"Update course_categories field idnumber
Options:
--up-idnumber         action update. Mandatory.
  --year=...          year code, ex. '2012-2013'
  --etab=...          etablissement code, ex. 'UP1'
--list-old            action list old idnumbers
-h, --help            Print out this help

";

if ( ! empty($options['help']) ) {
    echo $help;
    return 0;
}


// Ensure errors are well explained
$CFG->debug = DEBUG_NORMAL;


if ( $options['up-idnumber'] ) {
    update_categories_idnumber($options['year'], $options['etab']);
    return 0;
}

if ( $options['list-old'] ) {
    list_old_idnumber();
    return 0;
}

echo "Aucune action indiquée.\n";
