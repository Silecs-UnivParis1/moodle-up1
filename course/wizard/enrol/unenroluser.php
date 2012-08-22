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
 * Completely unenrol a user from a course.
 *
 * Please note when unenrolling a user all of their grades are removed as well,
 * most ppl actually expect enrolments to be suspended only...
 *
 * @package    course
 * @subpackage wizard_enrol
 * @copyright  silecs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from core_enrol (enrol/unenroluser.php) by 2010 Petr Skoda {@link http://skodak.org}
 */

require_once('../../../config.php');
require_once("$CFG->dirroot/enrol/locallib.php");
require_once("$CFG->dirroot/enrol/renderer.php");

$ueid    = required_param('ue', PARAM_INT); // user enrolment id
$confirm = optional_param('confirm', false, PARAM_BOOL);
$filter  = optional_param('ifilter', 0, PARAM_INT);

$ue = $DB->get_record('user_enrolments', array('id' => $ueid), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id'=>$ue->userid), '*', MUST_EXIST);
$instance = $DB->get_record('enrol', array('id'=>$ue->enrolid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$instance->courseid), '*', MUST_EXIST);

$context = context_course::instance($course->id);

// set up PAGE url first!
$PAGE->set_url('/course/wizard/enrol/unenroluser.php', array('ue'=>$ueid, 'ifilter'=>$filter));

require_login();
if(!isset($SESSION->wizard['idcourse']) || $SESSION->wizard['idcourse']!=$id) {
	require_login($course);
	// ou redirect(new moodle_url('/'));
}

$systemcontext   = get_context_instance(CONTEXT_SYSTEM);

// donner un context à $PAGE
$PAGE->set_context($systemcontext);

has_capability('moodle/course:request', $systemcontext);

$plugin = enrol_get_plugin($instance->enrol);

$manager = new course_enrolment_manager($PAGE, $course, $filter);
$table = new course_enrolment_users_table($manager, $PAGE);

$returnurl = new moodle_url('/course/wizard/enrol/users.php', array('id' => $course->id)+$manager->get_url_params()+$table->get_url_params());
$usersurl = new moodle_url('/course/wizard/enrol/users.php', array('id' => $course->id));

navigation_node::override_active_url($usersurl);

// If the unenrolment has been confirmed and the sesskey is valid unenrol the user.
if ($confirm && confirm_sesskey()) {
    $plugin->unenrol_user($instance, $ue->userid);
    redirect($returnurl);
}

$yesurl = new moodle_url($PAGE->url, array('confirm'=>1, 'sesskey'=>sesskey()));
$message = get_string('unenrolconfirm', 'core_enrol', array('user'=>fullname($user, true), 'course'=>format_string($course->fullname)));
$fullname = fullname($user);
$title = get_string('unenrol', 'core_enrol');

$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);
$PAGE->navbar->add($fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($fullname);
echo $OUTPUT->confirm($message, $yesurl, $returnurl);
echo $OUTPUT->footer();
