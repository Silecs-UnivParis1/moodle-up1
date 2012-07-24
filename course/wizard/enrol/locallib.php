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
 * derived from core_enrol (enrol/renderer.php) by 2010 Petr Skoda {@link http://skodak.org}

 */
class core_enrol_wizard_renderer extends core_enrol_renderer {
    /**
     * Renders a user enrolment action
     * @param user_enrolment_action $icon
     * @return string
     * modified
     */
    protected function render_user_enrolment_action(user_enrolment_action $icon) {
        if (strpos($icon->get_url()->get_path(), 'unenroluser')) {
			$myurl = '/course/wizard/enrol/unenroluser.php?'.$icon->get_url()->get_query_string();
            return html_writer::link(new moodle_url($myurl), $this->output->render($icon->get_icon()), $icon->get_attributes());
	    }
    }
}
