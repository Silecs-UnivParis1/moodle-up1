<?php
require_once __DIR__ . '/../../config.php';

redirect_if_major_upgrade_required();

//HTTPS is required in this page when $CFG->loginhttps enabled
$PAGE->https_required();

$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_url("$CFG->httpswwwroot/local/login-multi/index.php");
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

$site = get_site();
$loginsite = get_string("loginsite");

$PAGE->navbar->add($loginsite);

// make sure we really are on the https page when https login required
$PAGE->verify_https_required();

$PAGE->set_title("$site->fullname: $loginsite");
$PAGE->set_heading("$site->fullname");

echo $OUTPUT->header();


echo $OUTPUT->box_start();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();

