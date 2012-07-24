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

    /**
    * Renders a course enrolment table
    *
    * @param course_enrolment_table $table
    * @return string
    * modified
    */
    protected function render_course_enrolment_users_table(course_enrolment_users_table $table) {

        global $SESSION;

        $table->initialise_javascript();

        $idenrolment = 'aucun';
        if (isset($SESSION->wizard['idenrolment'])) {
			$idenrolment = $SESSION->wizard['idenrolment'];
		}

        $buttons = $table->get_manual_enrol_buttons();
        $buttonhtml = '';
        if (count($buttons) > 0) {
            $buttonhtml .= html_writer::start_tag('div', array('class' => 'enrol_user_buttons'));
            foreach ($buttons as $button) {
                // modification ici
			    if (strpos($button->url->get_path(), $idenrolment)) {
                    $buttonhtml .= $this->render($button);
			    }
            }
            $buttonhtml .= html_writer::end_tag('div');
        }

        $content = '';
        if (!empty($buttonhtml)) {
            $content .= $buttonhtml;
        }
       // $content .= $this->output->render($table->get_enrolment_type_filter());
        $content .= $this->output->render($table->get_paging_bar());

        $content .= html_writer::table($table);

        $content .= $this->output->render($table->get_paging_bar());
        if (!empty($buttonhtml)) {
            $content .= $buttonhtml;
        }
        return $content;
    }
}

class course_enrolment_manager_wizard extends course_enrolment_manager {
// je garde cette fonction
	 public function get_manual_enrol_buttons() {
		 global $SESSION, $CFG;

        $idenrolment = 'aucun';
        if (isset($SESSION->wizard['idenrolment'])) {
			$idenrolment = $SESSION->wizard['idenrolment'];
		}

        $myplugin = array();
        $class = "enrol_{$idenrolment}_plugin_wizard";
        if (class_exists($class)) {
            $myplugin[$idenrolment] = new $class();
        }
        $buttons = array();

		foreach ($myplugin as $key => $plugin) {

			if ( $key == $idenrolment) {
                $newbutton = $plugin->get_manual_enrol_button($this);
                if (is_array($newbutton)) {
                    $buttons += $newbutton;
                } else if ($newbutton instanceof enrol_user_button) {
                    $buttons[] = $newbutton;
                }
		    }
        }
        return $buttons;
    }

}

class enrol_manual_plugin_wizard extends enrol_manual_plugin {

    public function get_manual_enrol_link($instance) {
        $name = $this->get_name();
        if ($instance->enrol !== $name) {
            throw new coding_exception('invalid enrol instance!');
        }

        if (!enrol_is_enabled($name)) {
            return NULL;
        }

        $context = get_context_instance(CONTEXT_COURSE, $instance->courseid, MUST_EXIST);

        if (!has_capability('enrol/manual:manage', $context) or !has_capability('enrol/manual:enrol', $context) or !has_capability('enrol/manual:unenrol', $context)) {
            return NULL;
        }

        return new moodle_url('/course/wizard/enrol/manual/manage.php', array('enrolid'=>$instance->id, 'id'=>$instance->courseid));
    }
}

class enrol_cohort_plugin_wizard extends enrol_cohort_plugin {
	//
}


