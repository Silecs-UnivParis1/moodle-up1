<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Main course enrolment management UI, this is not compatible with frontpage course.
 *
 * @package    course
 * @subpackage wizard_enrol
 * @copyright  silecs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from core_enrol (enrol/users.php) by 2010 Petr Skoda {@link http://skodak.org}
 */

require_once('../../../config.php');
require_once("$CFG->dirroot/enrol/locallib.php");
require_once("$CFG->dirroot/enrol/users_forms.php");
require_once("$CFG->dirroot/enrol/renderer.php");

require_once("$CFG->dirroot/enrol/manual/lib.php");

require_once("$CFG->dirroot/course/wizard/enrol/locallib.php");

$id      = required_param('id', PARAM_INT); // course id
$action  = optional_param('action', '', PARAM_ACTION);
$filter  = optional_param('ifilter', 0, PARAM_INT);

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);

if ($course->id == SITEID) {
    redirect(new moodle_url('/'));
}

require_login();
if(!isset($SESSION->wizard['idcourse']) || $SESSION->wizard['idcourse']!=$id) {
	require_login($course);
	// ou redirect(new moodle_url('/'));
}

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);

// donner un context à $PAGE
$PAGE->set_context($systemcontext);

has_capability('moodle/course:request', $systemcontext);

//$manager = new course_enrolment_manager($PAGE, $course, $filter);
$manager = new course_enrolment_manager_wizard($PAGE, $course, $filter);

$table = new course_enrolment_users_table($manager, $PAGE);
$PAGE->set_url('/course/wizard/enrol/users.php', $manager->get_url_params()+$table->get_url_params());
navigation_node::override_active_url(new moodle_url('/course/wizard/enrol/users.php', array('id' => $id)));

$renderer = $PAGE->get_renderer('core_enrol', 'wizard');

$userdetails = array (
    'picture' => false,
    'firstname' => get_string('firstname'),
    'lastname' => get_string('lastname'),
);
$extrafields = get_extra_user_fields($context);
foreach ($extrafields as $field) {
    $userdetails[$field] = get_user_field_name($field);
}

$fields = array(
    'userdetails' => $userdetails,
    'lastseen' => get_string('lastaccess'),
    'role' => get_string('roles', 'role'),
    'enrol' => get_string('enrolmentinstances', 'enrol')
);

// Remove hidden fields if the user has no access
if (!has_capability('moodle/course:viewhiddenuserfields', $context)) {
    $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    if (isset($hiddenfields['lastaccess'])) {
        unset($fields['lastseen']);
    }
    if (isset($hiddenfields['groups'])) {
        unset($fields['group']);
    }
}

$table->set_fields($fields, $renderer);

$canassign = 0;
$users = $manager->get_users_for_display($manager, $table->sort, $table->sortdirection, $table->page, $table->perpage);
foreach ($users as $userid=>&$user) {
    $user['picture'] = $OUTPUT->render($user['picture']);
    $user['role'] = $renderer->user_roles_and_actions($userid, $user['roles'], $manager->get_assignable_roles(), 0, $PAGE->url);
    $user['enrol'] = $renderer->user_enrolments_and_actions($user['enrolments']);;
}
$table->set_total_users($manager->get_total_users());
$table->set_users($users);

$PAGE->set_title($PAGE->course->fullname.': '.get_string('totalenrolledusers', 'enrol', $manager->get_total_users()));
$PAGE->set_heading($PAGE->title);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('enrolledusers', 'enrol'));
echo $renderer->render($table);

$stepin = 5;
$stepgo = 5;
$buttonpre = '';

echo '<div align="center" style="margin:50px;"><div class="buttons">';
echo $buttonpre;
echo $OUTPUT->single_button(
    new moodle_url('/course/wizard/index.php',
        array('stepin' => $stepin, 'stepgo' => $stepgo, 'courseid' => $id, 'idenrolment' => 'cohort')),
    'Etape suivante',
    'post'
);
echo '</div></div>';

echo $OUTPUT->footer();
