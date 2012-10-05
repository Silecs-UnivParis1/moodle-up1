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

    /**
     * Gets an array of the users that can be enrolled in this course.
     * uniquement users ayant au moint un rôle global Teacher
     *
     * @global moodle_database $DB
     * @param int $enrolid
     * @param string $search
     * @param bool $searchanywhere
     * @param int $page Defaults to 0
     * @param int $perpage Defaults to 25
     * @return array Array(totalusers => int, users => array)
     */
    public function get_potential_users($enrolid, $search='', $searchanywhere=false, $page=0, $perpage=25) {
        global $DB, $CFG;

        // Add some additional sensible conditions
       // $tests = array("id <> :guestid", 'u.deleted = 0', 'u.confirmed = 1');
        $tests = array( 'u.deleted = 0', 'u.confirmed = 1',  'ra.contextid = 1', 'ra.roleid < 5');
        $params = array('guestid' => $CFG->siteguest);
        if (!empty($search)) {
            $conditions = get_extra_user_fields($this->get_context());
            $conditions[] = $DB->sql_concat('u.firstname', "' '", 'u.lastname');
            if ($searchanywhere) {
                $searchparam = '%' . $search . '%';
            } else {
                $searchparam = $search . '%';
            }
            $i = 0;
            foreach ($conditions as $key=>$condition) {
                $conditions[$key] = $DB->sql_like($condition,":con{$i}00", false);
                $params["con{$i}00"] = $searchparam;
                $i++;
            }
            $tests[] = '(' . implode(' OR ', $conditions) . ')';
        }
        $wherecondition = implode(' AND ', $tests);

        $extrafields = get_extra_user_fields($this->get_context(), array('username', 'lastaccess'));
        $extrafields[] = 'username';
        $extrafields[] = 'lastaccess';
        $ufields = user_picture::fields('u', $extrafields);

        $fields      = 'SELECT '.$ufields;
        $countfields = 'SELECT COUNT(1)';
        $sql = " FROM {user} u JOIN {role_assignments} ra ON (u.id = ra.userid) "
                . "WHERE $wherecondition "
                . "AND u.id NOT IN (SELECT ue.userid "
                . "FROM {user_enrolments} ue "
                .   " JOIN {enrol} e ON (e.id = ue.enrolid AND e.id = :enrolid))";

        $order = ' ORDER BY u.lastname ASC, u.firstname ASC';
        $params['enrolid'] = $enrolid;
        $totalusers = $DB->count_records_sql($countfields . $sql, $params);
        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params, $page*$perpage, $perpage);
        return array('totalusers'=>$totalusers, 'users'=>$availableusers);
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

    /**
     * Returns a button to manually enrol users through the manual enrolment plugin.
     *
     * By default the first manual enrolment plugin instance available in the course is used.
     * If no manual enrolment instances exist within the course then false is returned.
     *
     * This function also adds a quickenrolment JS ui to the page so that users can be enrolled
     * via AJAX.
     *
     * @param course_enrolment_manager $manager
     * @return enrol_user_button
     */
    public function get_manual_enrol_button(course_enrolment_manager $manager) {
        global $CFG;

        $instance = null;
        $instances = array();
        foreach ($manager->get_enrolment_instances() as $tempinstance) {
            if ($tempinstance->enrol == 'manual') {
                if ($instance === null) {
                    $instance = $tempinstance;
                }
                $instances[] = array('id' => $tempinstance->id, 'name' => $this->get_instance_name($tempinstance));
            }
        }
        if (empty($instance)) {
            return false;
        }

        if (!$manuallink = $this->get_manual_enrol_link($instance)) {
            return false;
        }

        $button = new enrol_user_button($manuallink, get_string('enrolusers', 'enrol_manual'), 'get');
        $button->class .= ' enrol_manual_plugin';

        $startdate = $manager->get_course()->startdate;
        $startdateoptions = array();
        $timeformat = get_string('strftimedatefullshort');
        if ($startdate > 0) {
            $startdateoptions[2] = get_string('coursestart') . ' (' . userdate($startdate, $timeformat) . ')';
        }
        $today = time();
        $today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);
        $startdateoptions[3] = get_string('today') . ' (' . userdate($today, $timeformat) . ')' ;

        $modules = array('moodle-enrol_manual-quickenrolment', 'moodle-enrol_manual-quickenrolment-skin');
        $arguments = array(
            'instances'           => $instances,
            'courseid'            => $instance->courseid,
           // 'ajaxurl'             => '/enrol/manual/ajax.php',
           'ajaxurl'             => '/course/wizard/enrol/manual/ajax.php',
            'url'                 => $manager->get_moodlepage()->url->out(false),
            'optionsStartDate'    => $startdateoptions,
            'defaultRole'         => $instance->roleid,
            'disableGradeHistory' => $CFG->disablegradehistory,
            'recoverGradesDefault'=> ''
        );

        if ($CFG->recovergradesdefault) {
            $arguments['recoverGradesDefault'] = ' checked="checked"';
        }

        $function = 'M.enrol_manual.quickenrolment.init';
        $button->require_yui_module($modules, $function, array($arguments));
        $button->strings_for_js(array(
            'ajaxoneuserfound',
            'ajaxxusersfound',
            'ajaxnext25',
            'enrol',
            'enrolmentoptions',
            'enrolusers',
            'errajaxfailedenrol',
            'errajaxsearch',
            'none',
            'usersearch',
            'unlimitedduration',
            'startdatetoday',
            'durationdays',
            'enrolperiod',
            'finishenrollingusers',
            'recovergrades'), 'enrol');
        $button->strings_for_js('assignroles', 'role');
        $button->strings_for_js('startingfrom', 'moodle');

        return $button;
    }

}
