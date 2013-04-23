<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once($CFG->dirroot.'/report/rofstats/locallib.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();
$constant = optional_param('constant', null, PARAM_ALPHANUMEXT); //

// Print the header.
admin_externalpage_setup('reportrofstats', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();
echo $OUTPUT->heading("Table rof_constant - élément " . $constant);

echo '<p>' . rof_links_constants('/report/rofstats/constant.php') . '</p>';

if ( ! empty($constant) ) {
    rof_table_constants($constant);
}
