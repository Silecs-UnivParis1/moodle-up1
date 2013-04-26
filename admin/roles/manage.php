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
 * Lets the user define and edit roles.
 *
 * Responds to actions:
 *   [blank]   - list roles.
 *   delete    - delete a role (with are-you-sure)
 *   moveup    - change the sort order
 *   movedown  - change the sort order
 *   reset     - set a role's permissions back to the default for that legacy role type.
 *
 * For all but the first two of those, you also need a roleid parameter, and
 * possibly some other data.
 *
 * @package    core
 * @subpackage role
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

    require_once(dirname(__FILE__) . '/../../config.php');
    require_once($CFG->dirroot . '/' . $CFG->admin . '/roles/lib.php');

    $action = optional_param('action', '', PARAM_ALPHA);
    if ($action) {
        $roleid = required_param('roleid', PARAM_INT);
    }

/// Get the base URL for this and related pages into a convenient variable.
    $baseurl = $CFG->wwwroot . '/' . $CFG->admin . '/roles/manage.php';
    $defineurl = $CFG->wwwroot . '/' . $CFG->admin . '/roles/define.php';

/// Check access permissions.
    $systemcontext = context_system::instance();
    require_login();
    require_capability('moodle/role:manage', $systemcontext);
    admin_externalpage_setup('defineroles');

/// Get some basic data we are going to need.
    $roles = role_fix_names(get_all_roles(), $systemcontext, ROLENAME_ORIGINAL);

    $undeletableroles = array();
    $undeletableroles[$CFG->notloggedinroleid] = 1;
    $undeletableroles[$CFG->guestroleid] = 1;
    $undeletableroles[$CFG->defaultuserroleid] = 1;

///.Process submitted data.
    $confirmed = optional_param('confirm', false, PARAM_BOOL) && data_submitted() && confirm_sesskey();
    switch ($action) {
        case 'delete':
            if (isset($undeletableroles[$roleid])) {
                print_error('cannotdeletethisrole', '', $baseurl);
            }
            if (!$confirmed) {
                // show confirmation
                echo $OUTPUT->header();
                $optionsyes = array('action'=>'delete', 'roleid'=>$roleid, 'sesskey'=>sesskey(), 'confirm'=>1);
                $a = new stdClass();
                $a->id = $roleid;
                $a->name = $roles[$roleid]->name;
                $a->shortname = $roles[$roleid]->shortname;
                $a->count = $DB->count_records('role_assignments', array('roleid'=>$roleid));

                $formcontinue = new single_button(new moodle_url($baseurl, $optionsyes), get_string('yes'));
                $formcancel = new single_button(new moodle_url($baseurl), get_string('no'), 'get');
                echo $OUTPUT->confirm(get_string('deleterolesure', 'role', $a), $formcontinue, $formcancel);
                echo $OUTPUT->footer();
                die;
            }
            if (!delete_role($roleid)) {
                // The delete failed, but mark the context dirty in case.
                mark_context_dirty($systemcontext->path);
                print_error('cannotdeleterolewithid', 'error', $baseurl, $roleid);
            }
            // Deleted a role sitewide...
            mark_context_dirty($systemcontext->path);
            add_to_log(SITEID, 'role', 'delete', 'admin/roles/manage.php', $roles[$roleid]->localname, '', $USER->id);
            redirect($baseurl);
            break;

        case 'moveup':
            if (confirm_sesskey()) {
                $prevrole = null;
                $thisrole = null;
                foreach ($roles as $role) {
                    if ($role->id == $roleid) {
                        $thisrole = $role;
                        break;
                    } else {
                        $prevrole = $role;
                    }
                }
                if (is_null($thisrole) || is_null($prevrole)) {
                    print_error('cannotmoverolewithid', 'error', '', $roleid);
                }
                if (!switch_roles($thisrole, $prevrole)) {
                    print_error('cannotmoverolewithid', 'error', '', $roleid);
                }
            }

            redirect($baseurl);
            break;

        case 'movedown':
            if (confirm_sesskey()) {
                $thisrole = null;
                $nextrole = null;
                foreach ($roles as $role) {
                    if ($role->id == $roleid) {
                        $thisrole = $role;
                    } else if (!is_null($thisrole)) {
                        $nextrole = $role;
                        break;
                    }
                }
                if (is_null($nextrole)) {
                    print_error('cannotmoverolewithid', 'error', '', $roleid);
                }
                if (!switch_roles($thisrole, $nextrole)) {
                    print_error('cannotmoverolewithid', 'error', '', $roleid);
                }
            }

            redirect($baseurl);
            break;

        case 'reset':
            if (!$confirmed) {
                // show confirmation
                echo $OUTPUT->header();
                $optionsyes = array('action'=>'reset', 'roleid'=>$roleid, 'sesskey'=>sesskey(), 'confirm'=>1);
                $optionsno  = array('action'=>'view', 'roleid'=>$roleid);
                $a = new stdClass();
                $a->id = $roleid;
                $a->name = $roles[$roleid]->name;
                $a->shortname = $roles[$roleid]->shortname;
                $a->legacytype = $roles[$roleid]->archetype;
                if (empty($a->legacytype)) {
                    $warning = get_string('resetrolesurenolegacy', 'role', $a);
                } else {
                    $warning = get_string('resetrolesure', 'role', $a);
                }
                $formcontinue = new single_button(new moodle_url('manage.php', $optionsyes), get_string('yes'));
                $formcancel = new single_button(new moodle_url('manage.php', $optionsno), get_string('no'), 'get');
                echo $OUTPUT->confirm($warning, $formcontinue, $formcancel);
                echo $OUTPUT->footer();
                die;
            }

            // Reset context levels for standard archetypes
            if ($roles[$roleid]->archetype) {
                set_role_contextlevels($roleid, get_default_contextlevels($roles[$roleid]->archetype));
            }

            //reset or delete the capabilities
            reset_role_capabilities($roleid);

            // Mark context dirty, log and redirect.
            mark_context_dirty($systemcontext->path);
            add_to_log(SITEID, 'role', 'reset', 'admin/roles/manage.php?action=reset&roleid=' . $roleid, $roles[$roleid]->localname, '', $USER->id);
            redirect($defineurl . '?action=view&roleid=' . $roleid);
            break;
    }

/// Print the page header and tabs.
    echo $OUTPUT->header();

    $currenttab = 'manage';
    include_once('managetabs.php');

/// Initialise table.
    $table = new html_table();
    $table->tablealign = 'center';
    $table->align = array('left', 'left', 'left', 'left');
    $table->wrap = array('nowrap', '', 'nowrap','nowrap');
    $table->width = '90%';
    $table->head = array(
        get_string('role') . ' ' . $OUTPUT->help_icon('roles', 'role'),
        get_string('description'),
        get_string('roleshortname', 'role'),
        get_string('edit')
    );

/// Get some strings outside the loop.
    $stredit = get_string('edit');
    $strduplicate = get_string('duplicate');
    $strdelete = get_string('delete');
    $strmoveup = get_string('moveup');
    $strmovedown = get_string('movedown');

/// Print a list of roles with edit/copy/delete/reorder icons.
    $table->data = array();
    $firstrole = reset($roles);
    $lastrole = end($roles);
    foreach ($roles as $role) {

    /// Basic data.
        $row = array(
            '<a href="' . $defineurl . '?action=view&amp;roleid=' . $role->id . '">' . $role->localname . '</a>',
            role_get_description($role),
            s($role->shortname),
            '',
        );

    /// Icons:
        // move up
        if ($role->sortorder != $firstrole->sortorder) {
            $row[3] .= get_action_icon($baseurl . '?action=moveup&amp;roleid=' . $role->id . '&amp;sesskey=' . sesskey(), 'up', $strmoveup, $strmoveup);
        } else {
            $row[3] .= get_spacer();
        }
        // move down
        if ($role->sortorder != $lastrole->sortorder) {
            $row[3] .= get_action_icon($baseurl . '?action=movedown&amp;roleid=' . $role->id . '&amp;sesskey=' . sesskey(), 'down', $strmovedown, $strmovedown);
        } else {
            $row[3] .= get_spacer();
        }
        // edit
        $row[3] .= get_action_icon($defineurl . '?action=edit&amp;roleid=' . $role->id,
                'edit', $stredit, get_string('editxrole', 'role', $role->localname));
        // duplicate
        $row[3] .= get_action_icon($defineurl . '?action=duplicate&amp;roleid=' . $role->id,
                'copy', $strduplicate, get_string('createrolebycopying', 'role', $role->localname));
        // delete
        if (isset($undeletableroles[$role->id])) {
            $row[3] .= get_spacer();
        } else {
            $row[3] .= get_action_icon($baseurl . '?action=delete&amp;roleid=' . $role->id,
                  'delete', $strdelete, get_string('deletexrole', 'role', $role->localname));
        }

        $table->data[] = $row;
    }
    echo html_writer::table($table);

    echo $OUTPUT->container_start('buttons');
    echo $OUTPUT->single_button(new moodle_url($defineurl, array('action' => 'add')), get_string('addrole', 'role'), 'get');
    echo $OUTPUT->container_end();

    echo $OUTPUT->footer();
    die;

function get_action_icon($url, $icon, $alt, $tooltip) {
    global $OUTPUT;
    return '<a title="' . $tooltip . '" href="'. $url . '">' .
            '<img src="' . $OUTPUT->pix_url('t/' . $icon) . '" class="iconsmall" alt="' . $alt . '" /></a> ';
}
function get_spacer() {
    global $OUTPUT;
    return '<img src="' . $OUTPUT->pix_url('spacer') . '" class="iconsmall" alt="" /> ';
}
