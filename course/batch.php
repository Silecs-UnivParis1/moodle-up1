<?php

require_once(dirname(dirname(__FILE__)) . "/config.php");
require_once($CFG->dirroot . '/course/lib.php');
require_once(dirname(__FILE__) . '/batch_form.php');

$action = optional_param('action', '', PARAM_ALPHA);

require_login(get_site());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/course/batch.php');
$PAGE->set_title(get_string("coursebatchactions", 'admin'));
$PAGE->set_heading(get_string("coursebatchactions", 'admin'));

$form = new course_batch_search_form();
$data = $form->get_data();
if ($data) {

}

require_once($CFG->libdir . '/adminlib.php');
admin_externalpage_setup('coursebatchactions', '', array(), $CFG->wwwroot . '/course/batch.php');

$settingsnode = $PAGE->settingsnav->find_active_node();
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("coursebatchactions", 'admin'));
$form->display();
echo $OUTPUT->footer();
