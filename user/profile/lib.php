<?php
global $CFG;

require_once($CFG->libdir . '/custominfo/lib.php');


/***** General purpose functions for customisable user profiles *****/

function profile_load_data($user) {
    return custominfo_data::type('user')->load_data($user);
}

/**
 * Print out the customisable categories and fields for a users profile
 * @param  object   instance of the moodleform class
 * @param int $userid id of user whose profile is being edited.
 */
function profile_definition($mform, $userid = 0) {
    $custominfo = new custominfo_form_extension('user');

    // if user is "admin" fields are displayed regardless
    $update = has_capability('moodle/user:update', get_context_instance(CONTEXT_SYSTEM));

    $custominfo->definition($mform, $canviewall, $userid);
}

function profile_definition_after_data($mform, $userid) {
    $custominfo = new custominfo_form_extension('user');
    $custominfo->definition_after_data($mform, $userid);
}

function profile_validation($usernew, $files) {
    $custominfo = new custominfo_form_extension('user');
    return $custominfo->validation($usernew, $files);
}

function profile_save_data($usernew) {
    return custominfo_data::type('user')->save_data($usernew);
}

function profile_display_fields($userid) {
    return custominfo_data::type('user')->display_fields($userid);
}

/**
 * Adds code snippet to a moodle form object for custom profile fields that
 * should appear on the signup page
 * @param  object  moodle form object
 */
function profile_signup_fields($mform) {
    global $CFG, $DB;

     //only retrieve required custom fields (with category information)
    //results are sort by categories, then by fields
    $sql = "SELECT f.id as fieldid, c.id as categoryid, c.name as categoryname, f.datatype
                FROM {custom_info_field} f
                JOIN {custom_info_category} c
                ON f.categoryid = c.id
                WHERE ( c.objectname = 'user' AND f.signup = 1 AND f.visible<>0 )
                ORDER BY c.sortorder ASC, f.sortorder ASC";
    $fields = $DB->get_records_sql($sql);
    if ($fields) {
        $currentcat = null;
        foreach ($fields as $field) {
            //check if we change the categories
            if (!isset($currentcat) || $currentcat != $field->categoryid) {
                 $currentcat = $field->categoryid;
                 $mform->addElement('header', 'category_'.$field->categoryid, format_string($field->categoryname));
            }
            $formfield = custominfo_field_factory("user", $field->datatype, $field->fieldid);
            $formfield->edit_field($mform);
        }
    }
}

/**
 * Returns an object with the custom profile fields set for the given user
 * @param  integer  userid
 * @return  object
 */
function profile_user_record($userid) {
    return custominfo_data::type('user')->get_record($userid);
}

/**
 * Load custom profile fields into user object
 *
 * Please note originally in 1.9 we were using the custom field names directly,
 * but it was causing unexpected collisions when adding new fields to user table,
 * so instead we now use 'profile_' prefix.
 *
 * @param object $user user object
 * @return void $user object is modified
 */
function profile_load_custom_fields($user) {
    $user->profile = (array)custominfo_data::type('user')->get_record($user->id);
}
