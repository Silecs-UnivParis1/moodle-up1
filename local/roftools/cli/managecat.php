<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__DIR__))).'/config.php'); // global moodle config file.
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require(__DIR__ . '/../locallib.php');

// now get cli options
list($options, $unrecognized) = cli_get_params(array(
        'help'=>false, 'verb'=>1,
        'create'=>false, 'delete'=>false, 'list'=>false, 'status'=>false,
    ),
    array('h'=>'help', 'i'=>'init'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"Create or delete course categories extracted from ROF cache (levels 3=components and 4=diplomaTypes)

Options:
-h, --help            Print out this help

--verb=N              Verbosity (0 to 3), 1 by default

--create              Creates the ROF categories for the current year.
--delete              Delete the ROF categories for the current year.
--list                List the ROF categories already present in the data for the current year.
--status              Displays the default settings.

";

if ( ! empty($options['help']) ) {
    echo $help;
    return 0;
}


// Ensure errors are well explained
$CFG->debug = DEBUG_NORMAL;


if ( $options['status'] ) {
    var_dump(high_level_categories());
    return 0;
}

if ( $options['list'] ) {
    echo "Listing categories... \n";
    list_rof_categories($options['verb']);
    echo "OK.\n";
    return 0;
}

if ( $options['create'] ) {
    echo "Creating categories... \n";
    create_rof_categories($options['verb']);
    echo "OK.\n";
    return 0;
}

if ( $options['delete'] ) {
    echo "Deleting categories... \n";
    delete_rof_categories();
    echo "OK.\n";
    return 0;
}

