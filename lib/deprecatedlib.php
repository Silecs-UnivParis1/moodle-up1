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
 * deprecatedlib.php - Old functions retained only for backward compatibility
 *
 * Old functions retained only for backward compatibility.  New code should not
 * use any of these functions.
 *
 * @package    core
 * @subpackage deprecated
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @deprecated
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unsupported session id rewriting.
 * @deprecated
 * @param string $buffer
 */
function sid_ob_rewrite($buffer) {
    throw new coding_exception('$CFG->usesid support was removed completely and can not be used.');
}

/**
 * Insert or update log display entry. Entry may already exist.
 * $module, $action must be unique
 * @deprecated
 *
 * @param string $module
 * @param string $action
 * @param string $mtable
 * @param string $field
 * @return void
 *
 */
function update_log_display_entry($module, $action, $mtable, $field) {
    global $DB;

    debugging('The update_log_display_entry() is deprecated, please use db/log.php description file instead.');
}

/**
 * Given some text in HTML format, this function will pass it
 * through any filters that have been configured for this context.
 *
 * @deprecated use the text formatting in a standard way instead,
 *             this was abused mostly for embedding of attachments
 *
 * @param string $text The text to be passed through format filters
 * @param int $courseid The current course.
 * @return string the filtered string.
 */
function filter_text($text, $courseid = NULL) {
    global $CFG, $COURSE;

    if (!$courseid) {
        $courseid = $COURSE->id;
    }

    if (!$context = context_course::instance($courseid, IGNORE_MISSING)) {
        return $text;
    }

    return filter_manager::instance()->filter_text($text, $context);
}

/**
 * This function indicates that current page requires the https
 * when $CFG->loginhttps enabled.
 *
 * By using this function properly, we can ensure 100% https-ized pages
 * at our entire discretion (login, forgot_password, change_password)
 * @deprecated use $PAGE->https_required() instead
 */
function httpsrequired() {
    global $PAGE;
    $PAGE->https_required();
}

/**
 * Given a physical path to a file, returns the URL through which it can be reached in Moodle.
 *
 * @deprecated use moodle_url factory methods instead
 *
 * @param string $path Physical path to a file
 * @param array $options associative array of GET variables to append to the URL
 * @param string $type (questionfile|rssfile|httpscoursefile|coursefile)
 * @return string URL to file
 */
function get_file_url($path, $options=null, $type='coursefile') {
    global $CFG;

    $path = str_replace('//', '/', $path);
    $path = trim($path, '/'); // no leading and trailing slashes

    // type of file
    switch ($type) {
       case 'questionfile':
            $url = $CFG->wwwroot."/question/exportfile.php";
            break;
       case 'rssfile':
            $url = $CFG->wwwroot."/rss/file.php";
            break;
        case 'httpscoursefile':
            $url = $CFG->httpswwwroot."/file.php";
            break;
         case 'coursefile':
        default:
            $url = $CFG->wwwroot."/file.php";
    }

    if ($CFG->slasharguments) {
        $parts = explode('/', $path);
        foreach ($parts as $key => $part) {
        /// anchor dash character should not be encoded
            $subparts = explode('#', $part);
            $subparts = array_map('rawurlencode', $subparts);
            $parts[$key] = implode('#', $subparts);
        }
        $path  = implode('/', $parts);
        $ffurl = $url.'/'.$path;
        $separator = '?';
    } else {
        $path = rawurlencode('/'.$path);
        $ffurl = $url.'?file='.$path;
        $separator = '&amp;';
    }

    if ($options) {
        foreach ($options as $name=>$value) {
            $ffurl = $ffurl.$separator.$name.'='.$value;
            $separator = '&amp;';
        }
    }

    return $ffurl;
}

/**
 * If there has been an error uploading a file, print the appropriate error message
 * Numerical constants used as constant definitions not added until PHP version 4.2.0
 * @deprecated removed - use new file api
 */
function print_file_upload_error($filearray = '', $returnerror = false) {
    throw new coding_exception('print_file_upload_error() can not be used any more, please use new file API');
}

/**
 * Handy function for resolving file conflicts
 * @deprecated removed - use new file api
 */

function resolve_filename_collisions($destination,$files,$format='%s_%d.%s') {
    throw new coding_exception('resolve_filename_collisions() can not be used any more, please use new file API');
}

/**
 * Checks a file name for any conflicts
 * @deprecated removed - use new file api
 */
function check_potential_filename($destination,$filename,$files) {
    throw new coding_exception('check_potential_filename() can not be used any more, please use new file API');
}

/**
 * This function prints out a number of upload form elements.
 * @deprecated removed - use new file api
 */
function upload_print_form_fragment($numfiles=1, $names=null, $descriptions=null, $uselabels=false, $labelnames=null, $coursebytes=0, $modbytes=0, $return=false) {
    throw new coding_exception('upload_print_form_fragment() can not be used any more, please use new file API');
}

/**
 * Return the authentication plugin title
 *
 * @param string $authtype plugin type
 * @return string
 */
function auth_get_plugin_title($authtype) {
    debugging('Function auth_get_plugin_title() is deprecated, please use standard get_string("pluginname", "auth_'.$authtype.'")!');
    return get_string('pluginname', "auth_{$authtype}");
}



/**
 * Enrol someone without using the default role in a course
 * @deprecated
 */
function enrol_into_course($course, $user, $enrol) {
    error('Function enrol_into_course() was removed, please use new enrol plugins instead!');
}

/**
 * Returns a role object that is the default role for new enrolments in a given course
 *
 * @deprecated
 * @param object $course
 * @return object returns a role or NULL if none set
 */
function get_default_course_role($course) {
    debugging('Function get_default_course_role() is deprecated, please use individual enrol plugin settings instead!');

    $student = get_archetype_roles('student');
    $student = reset($student);

    return $student;
}

/**
 * Extremely slow enrolled courses query.
 * @deprecated
 */
function get_my_courses($userid, $sort='visible DESC,sortorder ASC', $fields=NULL, $doanything=false,$limit=0) {
    error('Function get_my_courses() was removed, please use new enrol_get_my_courses() or enrol_get_users_courses()!');
}

/**
 * Was returning list of translations, use new string_manager instead
 *
 * @deprecated
 * @param bool $refreshcache force refreshing of lang cache
 * @param bool $returnall ignore langlist, return all languages available
 * @return array An associative array with contents in the form of LanguageCode => LanguageName
 */
function get_list_of_languages($refreshcache=false, $returnall=false) {
    debugging('get_list_of_languages() is deprecated, please use get_string_manager()->get_list_of_translations() instead.');
    if ($refreshcache) {
        get_string_manager()->reset_caches();
    }
    return get_string_manager()->get_list_of_translations($returnall);
}

/**
 * Returns a list of currencies in the current language
 * @deprecated
 * @return array
 */
function get_list_of_currencies() {
    debugging('get_list_of_currencies() is deprecated, please use get_string_manager()->get_list_of_currencies() instead.');
    return get_string_manager()->get_list_of_currencies();
}

/**
 * Returns a list of all enabled country names in the current translation
 * @deprecated
 * @return array two-letter country code => translated name.
 */
function get_list_of_countries() {
    debugging('get_list_of_countries() is deprecated, please use get_string_manager()->get_list_of_countries() instead.');
    return get_string_manager()->get_list_of_countries(false);
}

/**
 * @deprecated
 */
function isteacher() {
    error('Function isteacher() was removed, please use capabilities instead!');
}

/**
 * @deprecated
 */
function isteacherinanycourse() {
    throw new coding_Exception('Function isteacherinanycourse() was removed, please use capabilities instead!');
}

/**
 * @deprecated
 */
function get_guest() {
    throw new coding_Exception('Function get_guest() was removed, please use capabilities instead!');
}

/**
 * @deprecated
 */
function isguest() {
    throw new coding_Exception('Function isguest() was removed, please use capabilities instead!');
}

/**
 * @deprecated
 */
function get_teacher() {
    throw new coding_Exception('Function get_teacher() was removed, please use capabilities instead!');
}

/**
 * Return all course participant for a given course
 *
 * @deprecated
 * @param integer $courseid
 * @return array of user
 */
function get_course_participants($courseid) {
    return get_enrolled_users(context_course::instance($courseid));
}

/**
 * Return true if the user is a participant for a given course
 *
 * @deprecated
 * @param integer $userid
 * @param integer $courseid
 * @return boolean
 */
function is_course_participant($userid, $courseid) {
    return is_enrolled(context_course::instance($courseid), $userid);
}

/**
 * Searches logs to find all enrolments since a certain date
 *
 * used to print recent activity
 *
 * @global object
 * @uses CONTEXT_COURSE
 * @param int $courseid The course in question.
 * @param int $timestart The date to check forward of
 * @return object|false  {@link $USER} records or false if error.
 */
function get_recent_enrolments($courseid, $timestart) {
    global $DB;

    $context = context_course::instance($courseid);

    $sql = "SELECT u.id, u.firstname, u.lastname, MAX(l.time)
              FROM {user} u, {role_assignments} ra, {log} l
             WHERE l.time > ?
                   AND l.course = ?
                   AND l.module = 'course'
                   AND l.action = 'enrol'
                   AND ".$DB->sql_cast_char2int('l.info')." = u.id
                   AND u.id = ra.userid
                   AND ra.contextid ".get_related_contexts_string($context)."
          GROUP BY u.id, u.firstname, u.lastname
          ORDER BY MAX(l.time) ASC";
    $params = array($timestart, $courseid);
    return $DB->get_records_sql($sql, $params);
}


/**
 * Turn the ctx* fields in an objectlike record into a context subobject
 * This allows us to SELECT from major tables JOINing with
 * context at no cost, saving a ton of context lookups...
 *
 * Use context_instance_preload() instead.
 *
 * @deprecated since 2.0
 * @param object $rec
 * @return object
 */
function make_context_subobj($rec) {
    throw new coding_Exception('make_context_subobj() was removed, use new context preloading');
}

/**
 * Do some basic, quick checks to see whether $rec->context looks like a valid context object.
 *
 * Use context_instance_preload() instead.
 *
 * @deprecated since 2.0
 * @param object $rec a think that has a context, for example a course,
 *      course category, course modules, etc.
 * @param int $contextlevel the type of thing $rec is, one of the CONTEXT_... constants.
 * @return bool whether $rec->context looks like the correct context object
 *      for this thing.
 */
function is_context_subobj_valid($rec, $contextlevel) {
    throw new coding_Exception('is_context_subobj_valid() was removed, use new context preloading');
}

/**
 * Ensure that $rec->context is present and correct before you continue
 *
 * When you have a record (for example a $category, $course, $user or $cm that may,
 * or may not, have come from a place that does make_context_subobj, you can use
 * this method to ensure that $rec->context is present and correct before you continue.
 *
 * Use context_instance_preload() instead.
 *
 * @deprecated since 2.0
 * @param object $rec a thing that has an associated context.
 * @param integer $contextlevel the type of thing $rec is, one of the CONTEXT_... constants.
 */
function ensure_context_subobj_present(&$rec, $contextlevel) {
    throw new coding_Exception('ensure_context_subobj_present() was removed, use new context preloading');
}

########### FROM weblib.php ##########################################################################


/**
 * Print a message in a standard themed box.
 * This old function used to implement boxes using tables.  Now it uses a DIV, but the old
 * parameters remain.  If possible, $align, $width and $color should not be defined at all.
 * Preferably just use print_box() in weblib.php
 *
 * @deprecated
 * @param string $message The message to display
 * @param string $align alignment of the box, not the text (default center, left, right).
 * @param string $width width of the box, including units %, for example '100%'.
 * @param string $color background colour of the box, for example '#eee'.
 * @param int $padding padding in pixels, specified without units.
 * @param string $class space-separated class names.
 * @param string $id space-separated id names.
 * @param boolean $return return as string or just print it
 * @return string|void Depending on $return
 */
function print_simple_box($message, $align='', $width='', $color='', $padding=5, $class='generalbox', $id='', $return=false) {
    $output = '';
    $output .= print_simple_box_start($align, $width, $color, $padding, $class, $id, true);
    $output .= $message;
    $output .= print_simple_box_end(true);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}



/**
 * This old function used to implement boxes using tables.  Now it uses a DIV, but the old
 * parameters remain.  If possible, $align, $width and $color should not be defined at all.
 * Even better, please use print_box_start() in weblib.php
 *
 * @param string $align alignment of the box, not the text (default center, left, right).   DEPRECATED
 * @param string $width width of the box, including % units, for example '100%'.            DEPRECATED
 * @param string $color background colour of the box, for example '#eee'.                   DEPRECATED
 * @param int $padding padding in pixels, specified without units.                          OBSOLETE
 * @param string $class space-separated class names.
 * @param string $id space-separated id names.
 * @param boolean $return return as string or just print it
 * @return string|void Depending on $return
 */
function print_simple_box_start($align='', $width='', $color='', $padding=5, $class='generalbox', $id='', $return=false) {
    debugging('print_simple_box(_start/_end) is deprecated. Please use $OUTPUT->box(_start/_end) instead', DEBUG_DEVELOPER);

    $output = '';

    $divclasses = 'box '.$class.' '.$class.'content';
    $divstyles  = '';

    if ($align) {
        $divclasses .= ' boxalign'.$align;    // Implement alignment using a class
    }
    if ($width) {    // Hopefully we can eliminate these in calls to this function (inline styles are bad)
        if (substr($width, -1, 1) == '%') {    // Width is a % value
            $width = (int) substr($width, 0, -1);    // Extract just the number
            if ($width < 40) {
                $divclasses .= ' boxwidthnarrow';    // Approx 30% depending on theme
            } else if ($width > 60) {
                $divclasses .= ' boxwidthwide';      // Approx 80% depending on theme
            } else {
                $divclasses .= ' boxwidthnormal';    // Approx 50% depending on theme
            }
        } else {
            $divstyles  .= ' width:'.$width.';';     // Last resort
        }
    }
    if ($color) {    // Hopefully we can eliminate these in calls to this function (inline styles are bad)
        $divstyles  .= ' background:'.$color.';';
    }
    if ($divstyles) {
        $divstyles = ' style="'.$divstyles.'"';
    }

    if ($id) {
        $id = ' id="'.$id.'"';
    }

    $output .= '<div'.$id.$divstyles.' class="'.$divclasses.'">';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Print the end portion of a standard themed box.
 * Preferably just use print_box_end() in weblib.php
 *
 * @param boolean $return return as string or just print it
 * @return string|void Depending on $return
 */
function print_simple_box_end($return=false) {
    $output = '</div>';
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Given some text this function converted any URLs it found into HTML links
 *
 * This core function has been replaced with filter_urltolink since Moodle 2.0
 *
 * @param string $text Passed in by reference. The string to be searched for urls.
 */
function convert_urls_into_links($text) {
    debugging('convert_urls_into_links() has been deprecated and replaced by a new filter');
}

/**
 * Used to be called from help.php to inject a list of smilies into the
 * emoticons help file.
 *
 * @return string HTML
 */
function get_emoticons_list_for_help_file() {
    debugging('get_emoticons_list_for_help_file() has been deprecated, see the new emoticon_manager API');
    return '';
}

/**
 * Was used to replace all known smileys in the text with image equivalents
 *
 * This core function has been replaced with filter_emoticon since Moodle 2.0
 */
function replace_smilies(&$text) {
    debugging('replace_smilies() has been deprecated and replaced with the new filter_emoticon');
}

/**
 * deprecated - use clean_param($string, PARAM_FILE); instead
 * Check for bad characters ?
 *
 * @todo Finish documenting this function - more detail needed in description as well as details on arguments
 *
 * @param string $string ?
 * @param int $allowdots ?
 * @return bool
 */
function detect_munged_arguments($string, $allowdots=1) {
    if (substr_count($string, '..') > $allowdots) {   // Sometimes we allow dots in references
        return true;
    }
    if (preg_match('/[\|\`]/', $string)) {  // check for other bad characters
        return true;
    }
    if (empty($string) or $string == '/') {
        return true;
    }

    return false;
}


/**
 * Unzip one zip file to a destination dir
 * Both parameters must be FULL paths
 * If destination isn't specified, it will be the
 * SAME directory where the zip file resides.
 *
 * @global object
 * @param string $zipfile The zip file to unzip
 * @param string $destination The location to unzip to
 * @param bool $showstatus_ignored Unused
 */
function unzip_file($zipfile, $destination = '', $showstatus_ignored = true) {
    global $CFG;

    //Extract everything from zipfile
    $path_parts = pathinfo(cleardoubleslashes($zipfile));
    $zippath = $path_parts["dirname"];       //The path of the zip file
    $zipfilename = $path_parts["basename"];  //The name of the zip file
    $extension = $path_parts["extension"];    //The extension of the file

    //If no file, error
    if (empty($zipfilename)) {
        return false;
    }

    //If no extension, error
    if (empty($extension)) {
        return false;
    }

    //Clear $zipfile
    $zipfile = cleardoubleslashes($zipfile);

    //Check zipfile exists
    if (!file_exists($zipfile)) {
        return false;
    }

    //If no destination, passed let's go with the same directory
    if (empty($destination)) {
        $destination = $zippath;
    }

    //Clear $destination
    $destpath = rtrim(cleardoubleslashes($destination), "/");

    //Check destination path exists
    if (!is_dir($destpath)) {
        return false;
    }

    $packer = get_file_packer('application/zip');

    $result = $packer->extract_to_pathname($zipfile, $destpath);

    if ($result === false) {
        return false;
    }

    foreach ($result as $status) {
        if ($status !== true) {
            return false;
        }
    }

    return true;
}

/**
 * Zip an array of files/dirs to a destination zip file
 * Both parameters must be FULL paths to the files/dirs
 *
 * @global object
 * @param array $originalfiles Files to zip
 * @param string $destination The destination path
 * @return bool Outcome
 */
function zip_files ($originalfiles, $destination) {
    global $CFG;

    //Extract everything from destination
    $path_parts = pathinfo(cleardoubleslashes($destination));
    $destpath = $path_parts["dirname"];       //The path of the zip file
    $destfilename = $path_parts["basename"];  //The name of the zip file
    $extension = $path_parts["extension"];    //The extension of the file

    //If no file, error
    if (empty($destfilename)) {
        return false;
    }

    //If no extension, add it
    if (empty($extension)) {
        $extension = 'zip';
        $destfilename = $destfilename.'.'.$extension;
    }

    //Check destination path exists
    if (!is_dir($destpath)) {
        return false;
    }

    //Check destination path is writable. TODO!!

    //Clean destination filename
    $destfilename = clean_filename($destfilename);

    //Now check and prepare every file
    $files = array();
    $origpath = NULL;

    foreach ($originalfiles as $file) {  //Iterate over each file
        //Check for every file
        $tempfile = cleardoubleslashes($file); // no doubleslashes!
        //Calculate the base path for all files if it isn't set
        if ($origpath === NULL) {
            $origpath = rtrim(cleardoubleslashes(dirname($tempfile)), "/");
        }
        //See if the file is readable
        if (!is_readable($tempfile)) {  //Is readable
            continue;
        }
        //See if the file/dir is in the same directory than the rest
        if (rtrim(cleardoubleslashes(dirname($tempfile)), "/") != $origpath) {
            continue;
        }
        //Add the file to the array
        $files[] = $tempfile;
    }

    $zipfiles = array();
    $start = strlen($origpath)+1;
    foreach($files as $file) {
        $zipfiles[substr($file, $start)] = $file;
    }

    $packer = get_file_packer('application/zip');

    return $packer->archive_to_pathname($zipfiles, $destpath . '/' . $destfilename);
}

/////////////////////////////////////////////////////////////
/// Old functions not used anymore - candidates for removal
/////////////////////////////////////////////////////////////


/** various deprecated groups function **/


/**
 * Get the IDs for the user's groups in the given course.
 *
 * @global object
 * @param int $courseid The course being examined - the 'course' table id field.
 * @return array|bool An _array_ of groupids, or false
 * (Was return $groupids[0] - consequences!)
 */
function mygroupid($courseid) {
    global $USER;
    if ($groups = groups_get_all_groups($courseid, $USER->id)) {
        return array_keys($groups);
    } else {
        return false;
    }
}


/**
 * Returns the current group mode for a given course or activity module
 *
 * Could be false, SEPARATEGROUPS or VISIBLEGROUPS    (<-- Martin)
 *
 * @param object $course Course Object
 * @param object $cm Course Manager Object
 * @return mixed $course->groupmode
 */
function groupmode($course, $cm=null) {

    if (isset($cm->groupmode) && empty($course->groupmodeforce)) {
        return $cm->groupmode;
    }
    return $course->groupmode;
}

/**
 * Sets the current group in the session variable
 * When $SESSION->currentgroup[$courseid] is set to 0 it means, show all groups.
 * Sets currentgroup[$courseid] in the session variable appropriately.
 * Does not do any permission checking.
 *
 * @global object
 * @param int $courseid The course being examined - relates to id field in
 * 'course' table.
 * @param int $groupid The group being examined.
 * @return int Current group id which was set by this function
 */
function set_current_group($courseid, $groupid) {
    global $SESSION;
    return $SESSION->currentgroup[$courseid] = $groupid;
}


/**
 * Gets the current group - either from the session variable or from the database.
 *
 * @global object
 * @param int $courseid The course being examined - relates to id field in
 * 'course' table.
 * @param bool $full If true, the return value is a full record object.
 * If false, just the id of the record.
 * @return int|bool
 */
function get_current_group($courseid, $full = false) {
    global $SESSION;

    if (isset($SESSION->currentgroup[$courseid])) {
        if ($full) {
            return groups_get_group($SESSION->currentgroup[$courseid]);
        } else {
            return $SESSION->currentgroup[$courseid];
        }
    }

    $mygroupid = mygroupid($courseid);
    if (is_array($mygroupid)) {
        $mygroupid = array_shift($mygroupid);
        set_current_group($courseid, $mygroupid);
        if ($full) {
            return groups_get_group($mygroupid);
        } else {
            return $mygroupid;
        }
    }

    if ($full) {
        return false;
    } else {
        return 0;
    }
}


/**
 * Inndicates fatal error. This function was originally printing the
 * error message directly, since 2.0 it is throwing exception instead.
 * The error printing is handled in default exception handler.
 *
 * Old method, don't call directly in new code - use print_error instead.
 *
 * @param string $message The message to display to the user about the error.
 * @param string $link The url where the user will be prompted to continue. If no url is provided the user will be directed to the site index page.
 * @return void, always throws moodle_exception
 */
function error($message, $link='') {
    throw new moodle_exception('notlocalisederrormessage', 'error', $link, $message, 'error() is a deprecated function, please call print_error() instead of error()');
}


//////////////////////////
/// removed functions ////
//////////////////////////

/**
 * @deprecated
 * @param mixed $name
 * @param mixed $editorhidebuttons
 * @param mixed $id
 * @return void Throws an error and does nothing
 */
function use_html_editor($name='', $editorhidebuttons='', $id='') {
    error('use_html_editor() not available anymore');
}

/**
 * The old method that was used to include JavaScript libraries.
 * Please use $PAGE->requires->js_module() instead.
 *
 * @param mixed $lib The library or libraries to load (a string or array of strings)
 *      There are three way to specify the library:
 *      1. a shorname like 'yui_yahoo'. This translates into a call to $PAGE->requires->yui2_lib('yahoo');
 *      2. the path to the library relative to wwwroot, for example 'lib/javascript-static.js'
 *      3. (legacy) a full URL like $CFG->wwwroot . '/lib/javascript-static.js'.
 *      2. and 3. lead to a call $PAGE->requires->js('/lib/javascript-static.js').
 */
function require_js($lib) {
    throw new coding_exception('require_js() was removed, use new JS api');
}

/**
 * Makes an upload directory for a particular module.
 *
 * This function has been deprecated by the file API changes in Moodle 2.0.
 *
 * @deprecated
 * @param int $courseid The id of the course in question - maps to id field of 'course' table.
 * @return string|false Returns full path to directory if successful, false if not
 */
function make_mod_upload_directory($courseid) {
    throw new coding_exception('make_mod_upload_directory has been deprecated by the file API changes in Moodle 2.0.');
}

/**
 * Used to be used for setting up the theme. No longer used by core code, and
 * should not have been used elsewhere.
 *
 * The theme is now automatically initialised before it is first used. If you really need
 * to force this to happen, just reference $PAGE->theme.
 *
 * To force a particular theme on a particular page, you can use $PAGE->force_theme(...).
 * However, I can't think of any valid reason to do that outside the theme selector UI.
 *
 * @deprecated
 * @param string $theme The theme to use defaults to current theme
 * @param array $params An array of parameters to use
 */
function theme_setup($theme = '', $params=NULL) {
    throw new coding_exception('The function theme_setup is no longer required, and should no longer be used. ' .
            'The current theme gets initialised automatically before it is first used.');
}

/**
 * @deprecated use $PAGE->theme->name instead.
 * @return string the name of the current theme.
 */
function current_theme() {
    global $PAGE;
    // TODO, uncomment this once we have eliminated all references to current_theme in core code.
    // debugging('current_theme is deprecated, use $PAGE->theme->name instead', DEBUG_DEVELOPER);
    return $PAGE->theme->name;
}

/**
 * Prints some red text using echo
 *
 * @deprecated
 * @param string $error The text to be displayed in red
 */
function formerr($error) {
    debugging('formerr() has been deprecated. Please change your code to use $OUTPUT->error_text($string).');
    global $OUTPUT;
    echo $OUTPUT->error_text($error);
}

/**
 * Return the markup for the destination of the 'Skip to main content' links.
 * Accessibility improvement for keyboard-only users.
 *
 * Used in course formats, /index.php and /course/index.php
 *
 * @deprecated use $OUTPUT->skip_link_target() in instead.
 * @return string HTML element.
 */
function skip_main_destination() {
    global $OUTPUT;
    return $OUTPUT->skip_link_target();
}

/**
 * Prints a string in a specified size  (retained for backward compatibility)
 *
 * @deprecated
 * @param string $text The text to be displayed
 * @param int $size The size to set the font for text display.
 * @param bool $return If set to true output is returned rather than echoed Default false
 * @return string|void String if return is true
 */
function print_headline($text, $size=2, $return=false) {
    global $OUTPUT;
    debugging('print_headline() has been deprecated. Please change your code to use $OUTPUT->heading().');
    $output = $OUTPUT->heading($text, $size);
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Prints text in a format for use in headings.
 *
 * @deprecated
 * @param string $text The text to be displayed
 * @param string $deprecated No longer used. (Use to do alignment.)
 * @param int $size The size to set the font for text display.
 * @param string $class
 * @param bool $return If set to true output is returned rather than echoed, default false
 * @param string $id The id to use in the element
 * @return string|void String if return=true nothing otherwise
 */
function print_heading($text, $deprecated = '', $size = 2, $class = 'main', $return = false, $id = '') {
    global $OUTPUT;
    debugging('print_heading() has been deprecated. Please change your code to use $OUTPUT->heading().');
    if (!empty($deprecated)) {
        debugging('Use of deprecated align attribute of print_heading. ' .
                'Please do not specify styling in PHP code like that.', DEBUG_DEVELOPER);
    }
    $output = $OUTPUT->heading($text, $size, $class, $id);
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Output a standard heading block
 *
 * @deprecated
 * @param string $heading The text to write into the heading
 * @param string $class An additional Class Attr to use for the heading
 * @param bool $return If set to true output is returned rather than echoed, default false
 * @return string|void HTML String if return=true nothing otherwise
 */
function print_heading_block($heading, $class='', $return=false) {
    global $OUTPUT;
    debugging('print_heading_with_block() has been deprecated. Please change your code to use $OUTPUT->heading().');
    $output = $OUTPUT->heading($heading, 2, 'headingblock header ' . renderer_base::prepare_classes($class));
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print a message in a standard themed box.
 * Replaces print_simple_box (see deprecatedlib.php)
 *
 * @deprecated
 * @param string $message, the content of the box
 * @param string $classes, space-separated class names.
 * @param string $ids
 * @param boolean $return, return as string or just print it
 * @return string|void mixed string or void
 */
function print_box($message, $classes='generalbox', $ids='', $return=false) {
    global $OUTPUT;
    debugging('print_box() has been deprecated. Please change your code to use $OUTPUT->box().');
    $output = $OUTPUT->box($message, $classes, $ids);
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Starts a box using divs
 * Replaces print_simple_box_start (see deprecatedlib.php)
 *
 * @deprecated
 * @param string $classes, space-separated class names.
 * @param string $ids
 * @param boolean $return, return as string or just print it
 * @return string|void  string or void
 */
function print_box_start($classes='generalbox', $ids='', $return=false) {
    global $OUTPUT;
    debugging('print_box_start() has been deprecated. Please change your code to use $OUTPUT->box_start().');
    $output = $OUTPUT->box_start($classes, $ids);
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Simple function to end a box (see above)
 * Replaces print_simple_box_end (see deprecatedlib.php)
 *
 * @deprecated
 * @param boolean $return, return as string or just print it
 * @return string|void Depending on value of return
 */
function print_box_end($return=false) {
    global $OUTPUT;
    debugging('print_box_end() has been deprecated. Please change your code to use $OUTPUT->box_end().');
    $output = $OUTPUT->box_end();
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print a message in a standard themed container.
 *
 * @deprecated
 * @param string $message, the content of the container
 * @param boolean $clearfix clear both sides
 * @param string $classes, space-separated class names.
 * @param string $idbase
 * @param boolean $return, return as string or just print it
 * @return string|void Depending on value of $return
 */
function print_container($message, $clearfix=false, $classes='', $idbase='', $return=false) {
    global $OUTPUT;
    if ($clearfix) {
        $classes .= ' clearfix';
    }
    $output = $OUTPUT->container($message, $classes, $idbase);
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Starts a container using divs
 *
 * @deprecated
 * @param boolean $clearfix clear both sides
 * @param string $classes, space-separated class names.
 * @param string $idbase
 * @param boolean $return, return as string or just print it
 * @return string|void Based on value of $return
 */
function print_container_start($clearfix=false, $classes='', $idbase='', $return=false) {
    global $OUTPUT;
    if ($clearfix) {
        $classes .= ' clearfix';
    }
    $output = $OUTPUT->container_start($classes, $idbase);
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Deprecated, now handled automatically in themes
 */
function check_theme_arrows() {
    debugging('check_theme_arrows() has been deprecated, do not use it anymore, it is now automatic.');
}

/**
 * Simple function to end a container (see above)
 *
 * @deprecated
 * @param boolean $return, return as string or just print it
 * @return string|void Based on $return
 */
function print_container_end($return=false) {
    global $OUTPUT;
    $output = $OUTPUT->container_end();
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print a bold message in an optional color.
 *
 * @deprecated use $OUTPUT->notification instead.
 * @param string $message The message to print out
 * @param string $style Optional style to display message text in
 * @param string $align Alignment option
 * @param bool $return whether to return an output string or echo now
 * @return string|bool Depending on $result
 */
function notify($message, $classes = 'notifyproblem', $align = 'center', $return = false) {
    global $OUTPUT;

    if ($classes == 'green') {
        debugging('Use of deprecated class name "green" in notify. Please change to "notifysuccess".', DEBUG_DEVELOPER);
        $classes = 'notifysuccess'; // Backward compatible with old color system
    }

    $output = $OUTPUT->notification($message, $classes);
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print a continue button that goes to a particular URL.
 *
 * @deprecated since Moodle 2.0
 *
 * @param string $link The url to create a link to.
 * @param bool $return If set to true output is returned rather than echoed, default false
 * @return string|void HTML String if return=true nothing otherwise
 */
function print_continue($link, $return = false) {
    global $CFG, $OUTPUT;

    if ($link == '') {
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $link = $_SERVER['HTTP_REFERER'];
            $link = str_replace('&', '&amp;', $link); // make it valid XHTML
        } else {
            $link = $CFG->wwwroot .'/';
        }
    }

    $output = $OUTPUT->continue_button($link);
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print a standard header
 *
 * @param string  $title Appears at the top of the window
 * @param string  $heading Appears at the top of the page
 * @param string  $navigation Array of $navlinks arrays (keys: name, link, type) for use as breadcrumbs links
 * @param string  $focus Indicates form element to get cursor focus on load eg  inputform.password
 * @param string  $meta Meta tags to be added to the header
 * @param boolean $cache Should this page be cacheable?
 * @param string  $button HTML code for a button (usually for module editing)
 * @param string  $menu HTML code for a popup menu
 * @param boolean $usexml use XML for this page
 * @param string  $bodytags This text will be included verbatim in the <body> tag (useful for onload() etc)
 * @param bool    $return If true, return the visible elements of the header instead of echoing them.
 * @return string|void If return=true then string else void
 */
function print_header($title='', $heading='', $navigation='', $focus='',
                      $meta='', $cache=true, $button='&nbsp;', $menu=null,
                      $usexml=false, $bodytags='', $return=false) {
    global $PAGE, $OUTPUT;

    $PAGE->set_title($title);
    $PAGE->set_heading($heading);
    $PAGE->set_cacheable($cache);
    if ($button == '') {
        $button = '&nbsp;';
    }
    $PAGE->set_button($button);
    $PAGE->set_headingmenu($menu);

    // TODO $menu

    if ($meta) {
        throw new coding_exception('The $meta parameter to print_header is no longer supported. '.
                'You should be able to do everything you want with $PAGE->requires and other such mechanisms.');
    }
    if ($usexml) {
        throw new coding_exception('The $usexml parameter to print_header is no longer supported.');
    }
    if ($bodytags) {
        throw new coding_exception('The $bodytags parameter to print_header is no longer supported.');
    }

    $output = $OUTPUT->header();

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * This version of print_header is simpler because the course name does not have to be
 * provided explicitly in the strings. It can be used on the site page as in courses
 * Eventually all print_header could be replaced by print_header_simple
 *
 * @deprecated since Moodle 2.0
 * @param string $title Appears at the top of the window
 * @param string $heading Appears at the top of the page
 * @param string $navigation Premade navigation string (for use as breadcrumbs links)
 * @param string $focus Indicates form element to get cursor focus on load eg  inputform.password
 * @param string $meta Meta tags to be added to the header
 * @param boolean $cache Should this page be cacheable?
 * @param string $button HTML code for a button (usually for module editing)
 * @param string $menu HTML code for a popup menu
 * @param boolean $usexml use XML for this page
 * @param string $bodytags This text will be included verbatim in the <body> tag (useful for onload() etc)
 * @param bool   $return If true, return the visible elements of the header instead of echoing them.
 * @return string|void If $return=true the return string else nothing
 */
function print_header_simple($title='', $heading='', $navigation='', $focus='', $meta='',
                       $cache=true, $button='&nbsp;', $menu='', $usexml=false, $bodytags='', $return=false) {

    global $COURSE, $CFG, $PAGE, $OUTPUT;

    if ($meta) {
        throw new coding_exception('The $meta parameter to print_header is no longer supported. '.
                'You should be able to do everything you want with $PAGE->requires and other such mechanisms.');
    }
    if ($usexml) {
        throw new coding_exception('The $usexml parameter to print_header is no longer supported.');
    }
    if ($bodytags) {
        throw new coding_exception('The $bodytags parameter to print_header is no longer supported.');
    }

    $PAGE->set_title($title);
    $PAGE->set_heading($heading);
    $PAGE->set_cacheable(true);
    $PAGE->set_button($button);

    $output = $OUTPUT->header();

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

function print_footer($course = NULL, $usercourse = NULL, $return = false) {
    global $PAGE, $OUTPUT;
    debugging('print_footer() has been deprecated. Please change your code to use $OUTPUT->footer().');
    // TODO check arguments.
    if (is_string($course)) {
        debugging("Magic values like 'home', 'empty' passed to print_footer no longer have any effect. " .
                'To achieve a similar effect, call $PAGE->set_pagelayout before you call print_header.', DEBUG_DEVELOPER);
    } else if (!empty($course->id) && $course->id != $PAGE->course->id) {
        throw new coding_exception('The $course object you passed to print_footer does not match $PAGE->course.');
    }
    if (!is_null($usercourse)) {
        debugging('The second parameter ($usercourse) to print_footer is no longer supported. ' .
                '(I did not think it was being used anywhere.)', DEBUG_DEVELOPER);
    }
    $output = $OUTPUT->footer();
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Returns text to be displayed to the user which reflects their login status
 *
 * @global object
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_COURSE
 * @param course $course {@link $COURSE} object containing course information
 * @param user $user {@link $USER} object containing user information
 * @return string HTML
 */
function user_login_string($course='ignored', $user='ignored') {
    debugging('user_login_info() has been deprecated. User login info is now handled via themes layouts.');
    return '';
}

/**
 * Prints a nice side block with an optional header.  The content can either
 * be a block of HTML or a list of text with optional icons.
 *
 * @todo Finish documenting this function. Show example of various attributes, etc.
 *
 * @static int $block_id Increments for each call to the function
 * @param string $heading HTML for the heading. Can include full HTML or just
 *   plain text - plain text will automatically be enclosed in the appropriate
 *   heading tags.
 * @param string $content HTML for the content
 * @param array $list an alternative to $content, it you want a list of things with optional icons.
 * @param array $icons optional icons for the things in $list.
 * @param string $footer Extra HTML content that gets output at the end, inside a &lt;div class="footer">
 * @param array $attributes an array of attribute => value pairs that are put on the
 * outer div of this block. If there is a class attribute ' block' gets appended to it. If there isn't
 * already a class, class='block' is used.
 * @param string $title Plain text title, as embedded in the $heading.
 * @deprecated
 */
function print_side_block($heading='', $content='', $list=NULL, $icons=NULL, $footer='', $attributes = array(), $title='') {
    global $OUTPUT;

    // We don't use $heading, becuse it often contains HTML that we don't want.
    // However, sometimes $title is not set, but $heading is.
    if (empty($title)) {
        $title = strip_tags($heading);
    }

    // Render list contents to HTML if required.
    if (empty($content) && $list) {
        $content = $OUTPUT->list_block_contents($icons, $list);
    }

    $bc = new block_contents();
    $bc->content = $content;
    $bc->footer = $footer;
    $bc->title = $title;

    if (isset($attributes['id'])) {
        $bc->id = $attributes['id'];
        unset($attributes['id']);
    }
    $bc->attributes = $attributes;

    echo $OUTPUT->block($bc, BLOCK_POS_LEFT); // POS LEFT may be wrong, but no way to get a better guess here.
}

/**
 * Starts a nice side block with an optional header.
 *
 * @todo Finish documenting this function
 *
 * @global object
 * @global object
 * @param string $heading HTML for the heading. Can include full HTML or just
 *   plain text - plain text will automatically be enclosed in the appropriate
 *   heading tags.
 * @param array $attributes HTML attributes to apply if possible
 * @deprecated
 */
function print_side_block_start($heading='', $attributes = array()) {
    throw new coding_exception('print_side_block_start has been deprecated. Please change your code to use $OUTPUT->block().');
}

/**
 * Print table ending tags for a side block box.
 *
 * @global object
 * @global object
 * @param array $attributes HTML attributes to apply if possible [id]
 * @param string $title
 * @deprecated
 */
function print_side_block_end($attributes = array(), $title='') {
    throw new coding_exception('print_side_block_end has been deprecated. Please change your code to use $OUTPUT->block().');
}

/**
 * This was used by old code to see whether a block region had anything in it,
 * and hence wether that region should be printed.
 *
 * We don't ever want old code to print blocks, so we now always return false.
 * The function only exists to avoid fatal errors in old code.
 *
 * @deprecated since Moodle 2.0. always returns false.
 *
 * @param object $blockmanager
 * @param string $region
 * @return bool
 */
function blocks_have_content(&$blockmanager, $region) {
    debugging('The function blocks_have_content should no longer be used. Blocks are now printed by the theme.');
    return false;
}

/**
 * This was used by old code to print the blocks in a region.
 *
 * We don't ever want old code to print blocks, so this is now a no-op.
 * The function only exists to avoid fatal errors in old code.
 *
 * @deprecated since Moodle 2.0. does nothing.
 *
 * @param object $page
 * @param object $blockmanager
 * @param string $region
 */
function blocks_print_group($page, $blockmanager, $region) {
    debugging('The function blocks_print_group should no longer be used. Blocks are now printed by the theme.');
}

/**
 * This used to be the old entry point for anyone that wants to use blocks.
 * Since we don't want people people dealing with blocks this way any more,
 * just return a suitable empty array.
 *
 * @deprecated since Moodle 2.0.
 *
 * @param object $page
 * @return array
 */
function blocks_setup(&$page, $pinned = BLOCKS_PINNED_FALSE) {
    debugging('The function blocks_print_group should no longer be used. Blocks are now printed by the theme.');
    return array(BLOCK_POS_LEFT => array(), BLOCK_POS_RIGHT => array());
}

/**
 * This iterates over an array of blocks and calculates the preferred width
 * Parameter passed by reference for speed; it's not modified.
 *
 * @deprecated since Moodle 2.0. Layout is now controlled by the theme.
 *
 * @param mixed $instances
 */
function blocks_preferred_width($instances) {
    debugging('The function blocks_print_group should no longer be used. Blocks are now printed by the theme.');
    $width = 210;
}

/**
 * @deprecated since Moodle 2.0. See the replacements in blocklib.php.
 *
 * @param object $page The page object
 * @param object $blockmanager The block manager object
 * @param string $blockaction One of [config, add, delete]
 * @param int|object $instanceorid The instance id or a block_instance object
 * @param bool $pinned
 * @param bool $redirect To redirect or not to that is the question but you should stick with true
 */
function blocks_execute_action($page, &$blockmanager, $blockaction, $instanceorid, $pinned=false, $redirect=true) {
    throw new coding_exception('blocks_execute_action is no longer used. The way blocks work has been changed. See the new code in blocklib.php.');
}

/**
 * You can use this to get the blocks to respond to URL actions without much hassle
 *
 * @deprecated since Moodle 2.0. Blocks have been changed. {@link block_manager::process_url_actions} is the closest replacement.
 *
 * @param object $PAGE
 * @param object $blockmanager
 * @param bool $pinned
 */
function blocks_execute_url_action(&$PAGE, &$blockmanager,$pinned=false) {
    throw new coding_exception('blocks_execute_url_action is no longer used. It has been replaced by methods of block_manager.');
}

/**
 * This shouldn't be used externally at all, it's here for use by blocks_execute_action()
 * in order to reduce code repetition.
 *
 * @deprecated since Moodle 2.0. See the replacements in blocklib.php.
 *
 * @param $instance
 * @param $newpos
 * @param string|int $newweight
 * @param bool $pinned
 */
function blocks_execute_repositioning(&$instance, $newpos, $newweight, $pinned=false) {
    throw new coding_exception('blocks_execute_repositioning is no longer used. The way blocks work has been changed. See the new code in blocklib.php.');
}


/**
 * Moves a block to the new position (column) and weight (sort order).
 *
 * @deprecated since Moodle 2.0. See the replacements in blocklib.php.
 *
 * @param object $instance The block instance to be moved.
 * @param string $destpos BLOCK_POS_LEFT or BLOCK_POS_RIGHT. The destination column.
 * @param string $destweight The destination sort order. If NULL, we add to the end
 *                    of the destination column.
 * @param bool $pinned Are we moving pinned blocks? We can only move pinned blocks
 *                to a new position withing the pinned list. Likewise, we
 *                can only moved non-pinned blocks to a new position within
 *                the non-pinned list.
 * @return boolean success or failure
 */
function blocks_move_block($page, &$instance, $destpos, $destweight=NULL, $pinned=false) {
    throw new coding_exception('blocks_move_block is no longer used. The way blocks work has been changed. See the new code in blocklib.php.');
}

/**
 * Print a nicely formatted table.
 *
 * @deprecated since Moodle 2.0
 *
 * @param array $table is an object with several properties.
 */
function print_table($table, $return=false) {
    global $OUTPUT;
    // TODO MDL-19755 turn debugging on once we migrate the current core code to use the new API
    debugging('print_table() has been deprecated. Please change your code to use html_writer::table().');
    $newtable = new html_table();
    foreach ($table as $property => $value) {
        if (property_exists($newtable, $property)) {
            $newtable->{$property} = $value;
        }
    }
    if (isset($table->class)) {
        $newtable->attributes['class'] = $table->class;
    }
    if (isset($table->rowclass) && is_array($table->rowclass)) {
        debugging('rowclass[] has been deprecated for html_table and should be replaced by rowclasses[]. please fix the code.');
        $newtable->rowclasses = $table->rowclass;
    }
    $output = html_writer::table($newtable);
    if ($return) {
        return $output;
    } else {
        echo $output;
        return true;
    }
}

/**
 * Creates and displays (or returns) a link to a popup window
 *
 * @deprecated since Moodle 2.0
 *
 * @param string $url Web link. Either relative to $CFG->wwwroot, or a full URL.
 * @param string $name Name to be assigned to the popup window (this is used by
 *   client-side scripts to "talk" to the popup window)
 * @param string $linkname Text to be displayed as web link
 * @param int $height Height to assign to popup window
 * @param int $width Height to assign to popup window
 * @param string $title Text to be displayed as popup page title
 * @param string $options List of additional options for popup window
 * @param bool $return If true, return as a string, otherwise print
 * @param string $id id added to the element
 * @param string $class class added to the element
 * @return string html code to display a link to a popup window.
 */
function link_to_popup_window ($url, $name=null, $linkname=null, $height=400, $width=500, $title=null, $options=null, $return=false) {
    debugging('link_to_popup_window() has been removed. Please change your code to use $OUTPUT->action_link(). Please note popups are discouraged for accessibility reasons');

    return html_writer::link($url, $name);
}

/**
 * Creates and displays (or returns) a buttons to a popup window.
 *
 * @deprecated since Moodle 2.0
 *
 * @param string $url Web link. Either relative to $CFG->wwwroot, or a full URL.
 * @param string $name Name to be assigned to the popup window (this is used by
 *   client-side scripts to "talk" to the popup window)
 * @param string $linkname Text to be displayed as web link
 * @param int $height Height to assign to popup window
 * @param int $width Height to assign to popup window
 * @param string $title Text to be displayed as popup page title
 * @param string $options List of additional options for popup window
 * @param bool $return If true, return as a string, otherwise print
 * @param string $id id added to the element
 * @param string $class class added to the element
 * @return string html code to display a link to a popup window.
 */
function button_to_popup_window ($url, $name=null, $linkname=null,
                                 $height=400, $width=500, $title=null, $options=null, $return=false,
                                 $id=null, $class=null) {
    global $OUTPUT;

    debugging('button_to_popup_window() has been deprecated. Please change your code to use $OUTPUT->single_button().');

    if ($options == 'none') {
        $options = null;
    }

    if (empty($linkname)) {
        throw new coding_exception('A link must have a descriptive text value! See $OUTPUT->action_link() for usage.');
    }

    // Create a single_button object
    $form = new single_button($url, $linkname, 'post');
    $form->button->title = $title;
    $form->button->id = $id;

    // Parse the $options string
    $popupparams = array();
    if (!empty($options)) {
        $optionsarray = explode(',', $options);
        foreach ($optionsarray as $option) {
            if (strstr($option, '=')) {
                $parts = explode('=', $option);
                if ($parts[1] == '0') {
                    $popupparams[$parts[0]] = false;
                } else {
                    $popupparams[$parts[0]] = $parts[1];
                }
            } else {
                $popupparams[$option] = true;
            }
        }
    }

    if (!empty($height)) {
        $popupparams['height'] = $height;
    }
    if (!empty($width)) {
        $popupparams['width'] = $width;
    }

    $form->button->add_action(new popup_action('click', $url, $name, $popupparams));
    $output = $OUTPUT->render($form);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print a self contained form with a single submit button.
 *
 * @deprecated since Moodle 2.0
 *
 * @param string $link used as the action attribute on the form, so the URL that will be hit if the button is clicked.
 * @param array $options these become hidden form fields, so these options get passed to the script at $link.
 * @param string $label the caption that appears on the button.
 * @param string $method HTTP method used on the request of the button is clicked. 'get' or 'post'.
 * @param string $notusedanymore no longer used.
 * @param boolean $return if false, output the form directly, otherwise return the HTML as a string.
 * @param string $tooltip a tooltip to add to the button as a title attribute.
 * @param boolean $disabled if true, the button will be disabled.
 * @param string $jsconfirmmessage if not empty then display a confirm dialogue with this string as the question.
 * @param string $formid The id attribute to use for the form
 * @return string|void Depending on the $return paramter.
 */
function print_single_button($link, $options, $label='OK', $method='get', $notusedanymore='',
        $return=false, $tooltip='', $disabled = false, $jsconfirmmessage='', $formid = '') {
    global $OUTPUT;

    debugging('print_single_button() has been deprecated. Please change your code to use $OUTPUT->single_button().');

    // Cast $options to array
    $options = (array) $options;

    $button = new single_button(new moodle_url($link, $options), $label, $method, array('disabled'=>$disabled, 'title'=>$tooltip, 'id'=>$formid));

    if ($jsconfirmmessage) {
        $button->button->add_confirm_action($jsconfirmmessage);
    }

    $output = $OUTPUT->render($button);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print a spacer image with the option of including a line break.
 *
 * @deprecated since Moodle 2.0
 *
 * @global object
 * @param int $height The height in pixels to make the spacer
 * @param int $width The width in pixels to make the spacer
 * @param boolean $br If set to true a BR is written after the spacer
 */
function print_spacer($height=1, $width=1, $br=true, $return=false) {
    global $CFG, $OUTPUT;

    debugging('print_spacer() has been deprecated. Please change your code to use $OUTPUT->spacer().');

    $output = $OUTPUT->spacer(array('height'=>$height, 'width'=>$width, 'br'=>$br));

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Given the path to a picture file in a course, or a URL,
 * this function includes the picture in the page.
 *
 * @deprecated since Moodle 2.0
 */
function print_file_picture($path, $courseid=0, $height='', $width='', $link='', $return=false) {
    throw new coding_exception('print_file_picture() has been deprecated since Moodle 2.0. Please use $OUTPUT->action_icon() instead.');
}

/**
 * Print the specified user's avatar.
 *
 * @deprecated since Moodle 2.0
 *
 * @global object
 * @global object
 * @param mixed $user Should be a $user object with at least fields id, picture, imagealt, firstname, lastname, email
 *      If any of these are missing, or if a userid is passed, the the database is queried. Avoid this
 *      if at all possible, particularly for reports. It is very bad for performance.
 * @param int $courseid The course id. Used when constructing the link to the user's profile.
 * @param boolean $picture The picture to print. By default (or if NULL is passed) $user->picture is used.
 * @param int $size Size in pixels. Special values are (true/1 = 100px) and (false/0 = 35px) for backward compatibility
 * @param boolean $return If false print picture to current page, otherwise return the output as string
 * @param boolean $link enclose printed image in a link the user's profile (default true).
 * @param string $target link target attribute. Makes the profile open in a popup window.
 * @param boolean $alttext add non-blank alt-text to the image. (Default true, set to false for purely
 *      decorative images, or where the username will be printed anyway.)
 * @return string|void String or nothing, depending on $return.
 */
function print_user_picture($user, $courseid, $picture=NULL, $size=0, $return=false, $link=true, $target='', $alttext=true) {
    global $OUTPUT;

    debugging('print_user_picture() has been deprecated. Please change your code to use $OUTPUT->user_picture($user, array(\'courseid\'=>$courseid).');

    if (!is_object($user)) {
        $userid = $user;
        $user = new stdClass();
        $user->id = $userid;
    }

    if (empty($user->picture) and $picture) {
        $user->picture = $picture;
    }

    $options = array('size'=>$size, 'link'=>$link, 'alttext'=>$alttext, 'courseid'=>$courseid, 'popup'=>!empty($target));

    $output = $OUTPUT->user_picture($user, $options);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print a png image.
 *
 * @deprecated since Moodle 2.0: no replacement
 *
 */
function print_png() {
    throw new coding_exception('print_png() has been deprecated since Moodle 2.0. Please use $OUTPUT->pix_icon() instead.');
}


/**
 * Prints a basic textarea field.
 *
 * @deprecated since Moodle 2.0
 *
 * When using this function, you should
 *
 * @global object
 * @param bool $usehtmleditor Enables the use of the htmleditor for this field.
 * @param int $rows Number of rows to display  (minimum of 10 when $height is non-null)
 * @param int $cols Number of columns to display (minimum of 65 when $width is non-null)
 * @param null $width (Deprecated) Width of the element; if a value is passed, the minimum value for $cols will be 65. Value is otherwise ignored.
 * @param null $height (Deprecated) Height of the element; if a value is passe, the minimum value for $rows will be 10. Value is otherwise ignored.
 * @param string $name Name to use for the textarea element.
 * @param string $value Initial content to display in the textarea.
 * @param int $obsolete deprecated
 * @param bool $return If false, will output string. If true, will return string value.
 * @param string $id CSS ID to add to the textarea element.
 * @return string|void depending on the value of $return
 */
function print_textarea($usehtmleditor, $rows, $cols, $width, $height, $name, $value='', $obsolete=0, $return=false, $id='') {
    /// $width and height are legacy fields and no longer used as pixels like they used to be.
    /// However, you can set them to zero to override the mincols and minrows values below.

    // Disabling because there is not yet a viable $OUTPUT option for cases when mforms can't be used
    // debugging('print_textarea() has been deprecated. You should be using mforms and the editor element.');

    global $CFG;

    $mincols = 65;
    $minrows = 10;
    $str = '';

    if ($id === '') {
        $id = 'edit-'.$name;
    }

    if ($usehtmleditor) {
        if ($height && ($rows < $minrows)) {
            $rows = $minrows;
        }
        if ($width && ($cols < $mincols)) {
            $cols = $mincols;
        }
    }

    if ($usehtmleditor) {
        editors_head_setup();
        $editor = editors_get_preferred_editor(FORMAT_HTML);
        $editor->use_editor($id, array('legacy'=>true));
    } else {
        $editorclass = '';
    }

    $str .= "\n".'<textarea class="form-textarea" id="'. $id .'" name="'. $name .'" rows="'. $rows .'" cols="'. $cols .'">'."\n";
    if ($usehtmleditor) {
        $str .= htmlspecialchars($value); // needed for editing of cleaned text!
    } else {
        $str .= s($value);
    }
    $str .= '</textarea>'."\n";

    if ($return) {
        return $str;
    }
    echo $str;
}


/**
 * Print a help button.
 *
 * @deprecated since Moodle 2.0
 *
 * @param string $page  The keyword that defines a help page
 * @param string $title The title of links, rollover tips, alt tags etc
 *           'Help with' (or the language equivalent) will be prefixed and '...' will be stripped.
 * @param string $module Which module is the page defined in
 * @param mixed $image Use a help image for the link?  (true/false/"both")
 * @param boolean $linktext If true, display the title next to the help icon.
 * @param string $text If defined then this text is used in the page, and
 *           the $page variable is ignored. DEPRECATED!
 * @param boolean $return If true then the output is returned as a string, if false it is printed to the current page.
 * @param string $imagetext The full text for the helpbutton icon. If empty use default help.gif
 * @return string|void Depending on value of $return
 */
function helpbutton($page, $title, $module='moodle', $image=true, $linktext=false, $text='', $return=false, $imagetext='') {
    debugging('helpbutton() has been deprecated. Please change your code to use $OUTPUT->help_icon().');

    global $OUTPUT;

    $output = $OUTPUT->old_help_icon($page, $title, $module, $linktext);

    // hide image with CSS if needed

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print a help button.
 *
 * Prints a special help button that is a link to the "live" emoticon popup
 *
 * @todo Finish documenting this function
 *
 * @global object
 * @global object
 * @param string $form ?
 * @param string $field ?
 * @param boolean $return If true then the output is returned as a string, if false it is printed to the current page.
 * @return string|void Depending on value of $return
 */
function emoticonhelpbutton($form, $field, $return = false) {
    /// TODO: MDL-21215

    debugging('emoticonhelpbutton() was removed, new text editors will implement this feature');
}

/**
 * Returns a string of html with an image of a help icon linked to a help page on a number of help topics.
 * Should be used only with htmleditor or textarea.
 *
 * @global object
 * @global object
 * @param mixed $helptopics variable amount of params accepted. Each param may be a string or an array of arguments for
 *                  helpbutton.
 * @return string Link to help button
 */
function editorhelpbutton(){
    return '';

    /// TODO: MDL-21215
}

/**
 * Print a help button.
 *
 * Prints a special help button for html editors (htmlarea in this case)
 *
 * @todo Write code into this function! detect current editor and print correct info
 * @global object
 * @return string Only returns an empty string at the moment
 */
function editorshortcutshelpbutton() {
    /// TODO: MDL-21215

    global $CFG;
    //TODO: detect current editor and print correct info
/*    $imagetext = '<img src="' . $CFG->httpswwwroot . '/lib/editor/htmlarea/images/kbhelp.gif" alt="'.
        get_string('editorshortcutkeys').'" class="iconkbhelp" />';

    return helpbutton('editorshortcuts', get_string('editorshortcutkeys'), 'moodle', true, false, '', true, $imagetext);*/
    return '';
}


/**
 * Returns an image of an up or down arrow, used for column sorting. To avoid unnecessary DB accesses, please
 * provide this function with the language strings for sortasc and sortdesc.
 *
 * @deprecated since Moodle 2.0
 *
 * TODO migrate to outputlib
 * If no sort string is associated with the direction, an arrow with no alt text will be printed/returned.
 *
 * @global object
 * @param string $direction 'up' or 'down'
 * @param string $strsort The language string used for the alt attribute of this image
 * @param bool $return Whether to print directly or return the html string
 * @return string|void depending on $return
 *
 */
function print_arrow($direction='up', $strsort=null, $return=false) {
    // debugging('print_arrow() has been deprecated. Please change your code to use $OUTPUT->arrow().');

    global $OUTPUT;

    if (!in_array($direction, array('up', 'down', 'right', 'left', 'move'))) {
        return null;
    }

    $return = null;

    switch ($direction) {
        case 'up':
            $sortdir = 'asc';
            break;
        case 'down':
            $sortdir = 'desc';
            break;
        case 'move':
            $sortdir = 'asc';
            break;
        default:
            $sortdir = null;
            break;
    }

    // Prepare language string
    $strsort = '';
    if (empty($strsort) && !empty($sortdir)) {
        $strsort  = get_string('sort' . $sortdir, 'grades');
    }

    $return = ' <img src="'.$OUTPUT->pix_url('t/' . $direction) . '" alt="'.$strsort.'" /> ';

    if ($return) {
        return $return;
    } else {
        echo $return;
    }
}

/**
 * Returns a string containing a link to the user documentation.
 * Also contains an icon by default. Shown to teachers and admin only.
 *
 * @deprecated since Moodle 2.0
 *
 * @global object
 * @param string $path The page link after doc root and language, no leading slash.
 * @param string $text The text to be displayed for the link
 * @param string $iconpath The path to the icon to be displayed
 * @return string Either the link or an empty string
 */
function doc_link($path='', $text='', $iconpath='ignored') {
    global $CFG, $OUTPUT;

    debugging('doc_link() has been deprecated. Please change your code to use $OUTPUT->doc_link().');

    if (empty($CFG->docroot)) {
        return '';
    }

    return $OUTPUT->doc_link($path, $text);
}

/**
 * Prints a single paging bar to provide access to other pages  (usually in a search)
 *
 * @deprecated since Moodle 2.0
 *
 * @param int $totalcount Thetotal number of entries available to be paged through
 * @param int $page The page you are currently viewing
 * @param int $perpage The number of entries that should be shown per page
 * @param mixed $baseurl If this  is a string then it is the url which will be appended with $pagevar, an equals sign and the page number.
 *                          If this is a moodle_url object then the pagevar param will be replaced by the page no, for each page.
 * @param string $pagevar This is the variable name that you use for the page number in your code (ie. 'tablepage', 'blogpage', etc)
 * @param bool $nocurr do not display the current page as a link (dropped, link is never displayed for the current page)
 * @param bool $return whether to return an output string or echo now
 * @return bool|string depending on $result
 */
function print_paging_bar($totalcount, $page, $perpage, $baseurl, $pagevar='page',$nocurr=false, $return=false) {
    global $OUTPUT;

    debugging('print_paging_bar() has been deprecated. Please change your code to use $OUTPUT->render($pagingbar).');

    if (empty($nocurr)) {
        debugging('the feature of parameter $nocurr has been removed from the paging_bar');
    }

    $pagingbar = new paging_bar($totalcount, $page, $perpage, $baseurl);
    $pagingbar->pagevar = $pagevar;
    $output = $OUTPUT->render($pagingbar);

    if ($return) {
        return $output;
    }

    echo $output;
    return true;
}

/**
 * Print a message along with "Yes" and "No" links for the user to continue.
 *
 * @deprecated since Moodle 2.0
 *
 * @global object
 * @param string $message The text to display
 * @param string $linkyes The link to take the user to if they choose "Yes"
 * @param string $linkno The link to take the user to if they choose "No"
 * @param string $optionyes The yes option to show on the notice
 * @param string $optionsno The no option to show
 * @param string $methodyes Form action method to use if yes [post, get]
 * @param string $methodno Form action method to use if no [post, get]
 * @return void Output is echo'd
 */
function notice_yesno($message, $linkyes, $linkno, $optionsyes=NULL, $optionsno=NULL, $methodyes='post', $methodno='post') {

    debugging('notice_yesno() has been deprecated. Please change your code to use $OUTPUT->confirm($message, $buttoncontinue, $buttoncancel).');

    global $OUTPUT;

    $buttoncontinue = new single_button(new moodle_url($linkyes, $optionsyes), get_string('yes'), $methodyes);
    $buttoncancel   = new single_button(new moodle_url($linkno, $optionsno), get_string('no'), $methodno);

    echo $OUTPUT->confirm($message, $buttoncontinue, $buttoncancel);
}

/**
 * Prints a scale menu (as part of an existing form) including help button
 * @deprecated since Moodle 2.0
 */
function print_scale_menu() {
    throw new coding_exception('print_scale_menu() has been deprecated since the Jurassic period. Get with the times!.');
}

/**
 * Given an array of values, output the HTML for a select element with those options.
 *
 * @deprecated since Moodle 2.0
 *
 * Normally, you only need to use the first few parameters.
 *
 * @param array $options The options to offer. An array of the form
 *      $options[{value}] = {text displayed for that option};
 * @param string $name the name of this form control, as in &lt;select name="..." ...
 * @param string $selected the option to select initially, default none.
 * @param string $nothing The label for the 'nothing is selected' option. Defaults to get_string('choose').
 *      Set this to '' if you don't want a 'nothing is selected' option.
 * @param string $script if not '', then this is added to the &lt;select> element as an onchange handler.
 * @param string $nothingvalue The value corresponding to the $nothing option. Defaults to 0.
 * @param boolean $return if false (the default) the the output is printed directly, If true, the
 *      generated HTML is returned as a string.
 * @param boolean $disabled if true, the select is generated in a disabled state. Default, false.
 * @param int $tabindex if give, sets the tabindex attribute on the &lt;select> element. Default none.
 * @param string $id value to use for the id attribute of the &lt;select> element. If none is given,
 *      then a suitable one is constructed.
 * @param mixed $listbox if false, display as a dropdown menu. If true, display as a list box.
 *      By default, the list box will have a number of rows equal to min(10, count($options)), but if
 *      $listbox is an integer, that number is used for size instead.
 * @param boolean $multiple if true, enable multiple selections, else only 1 item can be selected. Used
 *      when $listbox display is enabled
 * @param string $class value to use for the class attribute of the &lt;select> element. If none is given,
 *      then a suitable one is constructed.
 * @return string|void If $return=true returns string, else echo's and returns void
 */
function choose_from_menu ($options, $name, $selected='', $nothing='choose', $script='',
                           $nothingvalue='0', $return=false, $disabled=false, $tabindex=0,
                           $id='', $listbox=false, $multiple=false, $class='') {

    global $OUTPUT;
    debugging('choose_from_menu() has been deprecated. Please change your code to use html_writer::select().');

    if ($script) {
        debugging('The $script parameter has been deprecated. You must use component_actions instead', DEBUG_DEVELOPER);
    }
    $attributes = array();
    $attributes['disabled'] = $disabled ? 'disabled' : null;
    $attributes['tabindex'] = $tabindex ? $tabindex : null;
    $attributes['multiple'] = $multiple ? $multiple : null;
    $attributes['class'] = $class ? $class : null;
    $attributes['id'] = $id ? $id : null;

    $output = html_writer::select($options, $name, $selected, array($nothingvalue=>$nothing), $attributes);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Choose value 0 or 1 from a menu with options 'No' and 'Yes'.
 * Other options like choose_from_menu.
 *
 * @deprecated since Moodle 2.0
 *
 * Calls {@link choose_from_menu()} with preset arguments
 * @see choose_from_menu()
 *
 * @param string $name the name of this form control, as in &lt;select name="..." ...
 * @param string $selected the option to select initially, default none.
 * @param string $script if not '', then this is added to the &lt;select> element as an onchange handler.
 * @param boolean $return Whether this function should return a string or output it (defaults to false)
 * @param boolean $disabled (defaults to false)
 * @param int $tabindex
 * @return string|void If $return=true returns string, else echo's and returns void
 */
function choose_from_menu_yesno($name, $selected, $script = '', $return = false, $disabled = false, $tabindex = 0) {
    debugging('choose_from_menu_yesno() has been deprecated. Please change your code to use html_writer.');
    global $OUTPUT;

    if ($script) {
        debugging('The $script parameter has been deprecated. You must use component_actions instead', DEBUG_DEVELOPER);
    }

    $output = html_writer::select_yes_no($name, $selected, array('disabled'=>($disabled ? 'disabled' : null), 'tabindex'=>$tabindex));

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Just like choose_from_menu, but takes a nested array (2 levels) and makes a dropdown menu
 * including option headings with the first level.
 *
 * @deprecated since Moodle 2.0
 *
 * This function is very similar to {@link choose_from_menu_yesno()}
 * and {@link choose_from_menu()}
 *
 * @todo Add datatype handling to make sure $options is an array
 *
 * @param array $options An array of objects to choose from
 * @param string $name The XHTML field name
 * @param string $selected The value to select by default
 * @param string $nothing The label for the 'nothing is selected' option.
 *                        Defaults to get_string('choose').
 * @param string $script If not '', then this is added to the &lt;select> element
 *                       as an onchange handler.
 * @param string $nothingvalue The value for the first `nothing` option if $nothing is set
 * @param bool $return Whether this function should return a string or output
 *                     it (defaults to false)
 * @param bool $disabled Is the field disabled by default
 * @param int|string $tabindex Override the tabindex attribute [numeric]
 * @return string|void If $return=true returns string, else echo's and returns void
 */
function choose_from_menu_nested($options,$name,$selected='',$nothing='choose',$script = '',
                                 $nothingvalue=0,$return=false,$disabled=false,$tabindex=0) {

    debugging('choose_from_menu_nested() has been removed. Please change your code to use html_writer::select().');
    global $OUTPUT;
}

/**
 * Prints a help button about a scale
 *
 * @deprecated since Moodle 2.0
 *
 * @global object
 * @param id $courseid
 * @param object $scale
 * @param boolean $return If set to true returns rather than echo's
 * @return string|bool Depending on value of $return
 */
function print_scale_menu_helpbutton($courseid, $scale, $return=false) {
    // debugging('print_scale_menu_helpbutton() has been deprecated. Please change your code to use $OUTPUT->help_scale($courseid, $scale).');
    global $OUTPUT;

    $output = $OUTPUT->help_icon_scale($courseid, $scale);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Prints time limit value selector
 *
 * @deprecated since Moodle 2.0
 *
 * Uses {@link choose_from_menu()} to generate HTML
 * @see choose_from_menu()
 *
 * @global object
 * @param int $timelimit default
 * @param string $unit
 * @param string $name
 * @param boolean $return If set to true returns rather than echo's
 * @return string|bool Depending on value of $return
 */
function print_timer_selector($timelimit = 0, $unit = '', $name = 'timelimit', $return=false) {
    throw new coding_exception('print_timer_selector is completely removed. Please use html_writer instead');
}

/**
 * Prints form items with the names $hour and $minute
 *
 * @deprecated since Moodle 2.0
 *
 * @param string $hour  fieldname
 * @param string $minute  fieldname
 * @param int $currenttime A default timestamp in GMT
 * @param int $step minute spacing
 * @param boolean $return If set to true returns rather than echo's
 * @return string|bool Depending on value of $return
 */
function print_time_selector($hour, $minute, $currenttime=0, $step=5, $return=false) {
    debugging('print_time_selector() has been deprecated. Please change your code to use html_writer.');

    $hourselector = html_writer::select_time('hours', $hour, $currenttime);
    $minuteselector = html_writer::select_time('minutes', $minute, $currenttime, $step);

    $output = $hourselector . $$minuteselector;

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Prints form items with the names $day, $month and $year
 *
 * @deprecated since Moodle 2.0
 *
 * @param string $day   fieldname
 * @param string $month  fieldname
 * @param string $year  fieldname
 * @param int $currenttime A default timestamp in GMT
 * @param boolean $return If set to true returns rather than echo's
 * @return string|bool Depending on value of $return
 */
function print_date_selector($day, $month, $year, $currenttime=0, $return=false) {
    debugging('print_date_selector() has been deprecated. Please change your code to use html_writer.');

    $dayselector = html_writer::select_time('days', $day, $currenttime);
    $monthselector = html_writer::select_time('months', $month, $currenttime);
    $yearselector = html_writer::select_time('years', $year, $currenttime);

    $output = $dayselector . $monthselector . $yearselector;

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Implements a complete little form with a dropdown menu.
 *
 * @deprecated since Moodle 2.0
 *
 * When JavaScript is on selecting an option from the dropdown automatically
 * submits the form (while avoiding the usual acessibility problems with this appoach).
 * With JavaScript off, a 'Go' button is printed.
 *
 * @global object
 * @global object
 * @param string $baseurl The target URL up to the point of the variable that changes
 * @param array $options A list of value-label pairs for the popup list
 * @param string $formid id for the control. Must be unique on the page. Used in the HTML.
 * @param string $selected The option that is initially selected
 * @param string $nothing The label for the "no choice" option
 * @param string $help The name of a help page if help is required
 * @param string $helptext The name of the label for the help button
 * @param boolean $return Indicates whether the function should return the HTML
 *         as a string or echo it directly to the page being rendered
 * @param string $targetwindow The name of the target page to open the linked page in.
 * @param string $selectlabel Text to place in a [label] element - preferred for accessibility.
 * @param array $optionsextra an array with the same keys as $options. The values are added within the corresponding <option ...> tag.
 * @param string $submitvalue Optional label for the 'Go' button. Defaults to get_string('go').
 * @param boolean $disabled If true, the menu will be displayed disabled.
 * @param boolean $showbutton If true, the button will always be shown even if JavaScript is available
 * @return string|void If $return=true returns string, else echo's and returns void
 */
function popup_form($baseurl, $options, $formid, $selected='', $nothing='choose', $help='', $helptext='', $return=false,
    $targetwindow='self', $selectlabel='', $optionsextra=NULL, $submitvalue='', $disabled=false, $showbutton=false) {
    global $OUTPUT, $CFG;

    debugging('popup_form() has been deprecated. Please change your code to use $OUTPUT->single_select() or $OUTPUT->url_select().');

    if (empty($options)) {
        return '';
    }

    $urls = array();

    foreach ($options as $value=>$label) {
        $url = $baseurl.$value;
        $url = str_replace($CFG->wwwroot, '', $url);
        $url = str_replace('&amp;', '&', $url);
        $urls[$url] = $label;
        if ($selected == $value) {
            $active = $url;
        }
    }

    $nothing = $nothing ? array(''=>$nothing) : null;

    $select = new url_select($urls, $active, $nothing, $formid);
    $select->disabled = $disabled;

    $select->set_label($selectlabel);
    $select->set_old_help_icon($help, $helptext);

    $output = $OUTPUT->render($select);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Prints a simple button to close a window
 *
 * @deprecated since Moodle 2.0
 *
 * @global object
 * @param string $name Name of the window to close
 * @param boolean $return whether this function should return a string or output it.
 * @param boolean $reloadopener if true, clicking the button will also reload
 *      the page that opend this popup window.
 * @return string|void if $return is true, void otherwise
 */
function close_window_button($name='closewindow', $return=false, $reloadopener = false) {
    global $OUTPUT;

    debugging('close_window_button() has been deprecated. Please change your code to use $OUTPUT->close_window_button().');
    $output = $OUTPUT->close_window_button(get_string($name));

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Given an array of values, creates a group of radio buttons to be part of a form
 *
 * @deprecated since Moodle 2.0
 *
 * @staticvar int $idcounter
 * @param array  $options  An array of value-label pairs for the radio group (values as keys)
 * @param string $name     Name of the radiogroup (unique in the form)
 * @param string $checked  The value that is already checked
 * @param bool $return Whether this function should return a string or output
 *                     it (defaults to false)
 * @return string|void If $return=true returns string, else echo's and returns void
 */
function choose_from_radio ($options, $name, $checked='', $return=false) {
    debugging('choose_from_radio() has been removed. Please change your code to use html_writer.');
}

/**
 * Display an standard html checkbox with an optional label
 *
 * @deprecated since Moodle 2.0
 *
 * @staticvar int $idcounter
 * @param string $name    The name of the checkbox
 * @param string $value   The valus that the checkbox will pass when checked
 * @param bool $checked The flag to tell the checkbox initial state
 * @param string $label   The label to be showed near the checkbox
 * @param string $alt     The info to be inserted in the alt tag
 * @param string $script If not '', then this is added to the checkbox element
 *                       as an onchange handler.
 * @param bool $return Whether this function should return a string or output
 *                     it (defaults to false)
 * @return string|void If $return=true returns string, else echo's and returns void
 */
function print_checkbox($name, $value, $checked = true, $label = '', $alt = '', $script='', $return=false) {

    // debugging('print_checkbox() has been deprecated. Please change your code to use html_writer::checkbox().');
    global $OUTPUT;

    if (!empty($script)) {
        debugging('The use of the $script param in print_checkbox has not been migrated into html_writer::checkbox().', DEBUG_DEVELOPER);
    }

    $output = html_writer::checkbox($name, $value, $checked, $label);

    if (empty($return)) {
        echo $output;
    } else {
        return $output;
    }

}


/**
 * Display an standard html text field with an optional label
 *
 * @deprecated since Moodle 2.0
 *
 * @param string $name    The name of the text field
 * @param string $value   The value of the text field
 * @param string $alt     The info to be inserted in the alt tag
 * @param int $size Sets the size attribute of the field. Defaults to 50
 * @param int $maxlength Sets the maxlength attribute of the field. Not set by default
 * @param bool $return Whether this function should return a string or output
 *                     it (defaults to false)
 * @return string|void If $return=true returns string, else echo's and returns void
 */
function print_textfield($name, $value, $alt = '', $size=50, $maxlength=0, $return=false) {
    debugging('print_textfield() has been deprecated. Please use mforms or html_writer.');

    if ($alt === '') {
        $alt = null;
    }

    $style = "width: {$size}px;";
    $attributes = array('type'=>'text', 'name'=>$name, 'alt'=>$alt, 'style'=>$style, 'value'=>$value);
    if ($maxlength) {
        $attributes['maxlength'] = $maxlength;
    }

    $output = html_writer::empty_tag('input', $attributes);

    if (empty($return)) {
        echo $output;
    } else {
        return $output;
    }
}


/**
 * Centered heading with attached help button (same title text)
 * and optional icon attached
 *
 * @deprecated since Moodle 2.0
 *
 * @param string $text The text to be displayed
 * @param string $helppage The help page to link to
 * @param string $module The module whose help should be linked to
 * @param string $icon Image to display if needed
 * @param bool $return If set to true output is returned rather than echoed, default false
 * @return string|void String if return=true nothing otherwise
 */
function print_heading_with_help($text, $helppage, $module='moodle', $icon=false, $return=false) {

    debugging('print_heading_with_help() has been deprecated. Please change your code to use $OUTPUT->heading().');

    global $OUTPUT;

    // Extract the src from $icon if it exists
    if (preg_match('/src="([^"]*)"/', $icon, $matches)) {
        $icon = $matches[1];
        $icon = new moodle_url($icon);
    } else {
        $icon = '';
    }

    $output = $OUTPUT->heading_with_help($text, $helppage, $module, $icon);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Returns a turn edit on/off button for course in a self contained form.
 * Used to be an icon, but it's now a simple form button
 * @deprecated since Moodle 2.0
 */
function update_mymoodle_icon() {
    throw new coding_exception('update_mymoodle_icon() has been completely deprecated.');
}

/**
 * Returns a turn edit on/off button for tag in a self contained form.
 * @deprecated since Moodle 2.0
 * @param string $tagid The ID attribute
 * @return string
 */
function update_tag_button($tagid) {
    global $OUTPUT;
    debugging('update_tag_button() has been deprecated. Please change your code to use $OUTPUT->edit_button(moodle_url).');
    return $OUTPUT->edit_button(new moodle_url('/tag/index.php', array('id' => $tagid)));
}


/**
 * Prints the 'update this xxx' button that appears on module pages.
 *
 * @deprecated since Moodle 2.0
 *
 * @param string $cmid the course_module id.
 * @param string $ignored not used any more. (Used to be courseid.)
 * @param string $string the module name - get_string('modulename', 'xxx')
 * @return string the HTML for the button, if this user has permission to edit it, else an empty string.
 */
function update_module_button($cmid, $ignored, $string) {
    global $CFG, $OUTPUT;

    // debugging('update_module_button() has been deprecated. Please change your code to use $OUTPUT->update_module_button().');

    //NOTE: DO NOT call new output method because it needs the module name we do not have here!

    if (has_capability('moodle/course:manageactivities', context_module::instance($cmid))) {
        $string = get_string('updatethis', '', $string);

        $url = new moodle_url("$CFG->wwwroot/course/mod.php", array('update' => $cmid, 'return' => true, 'sesskey' => sesskey()));
        return $OUTPUT->single_button($url, $string);
    } else {
        return '';
    }
}

/**
 * Prints the editing button on search results listing
 * For bulk move courses to another category
 * @deprecated since Moodle 2.0
 */
function update_categories_search_button($search,$page,$perpage) {
    throw new coding_exception('update_categories_search_button() has been completely deprecated.');
}

/**
 * Prints a summary of a user in a nice little box.
 * @deprecated since Moodle 2.0
 */
function print_user($user, $course, $messageselect=false, $return=false) {
    throw new coding_exception('print_user() has been completely deprecated. See user/index.php for new usage.');
}

/**
 * Returns a turn edit on/off button for course in a self contained form.
 * Used to be an icon, but it's now a simple form button
 *
 * Note that the caller is responsible for capchecks.
 *
 * @global object
 * @global object
 * @param int $courseid The course  to update by id as found in 'course' table
 * @return string
 */
function update_course_icon($courseid) {
    global $CFG, $OUTPUT;

    debugging('update_course_button() has been deprecated. Please change your code to use $OUTPUT->edit_button(moodle_url).');

    return $OUTPUT->edit_button(new moodle_url('/course/view.php', array('id' => $courseid)));
}

/**
 * Prints breadcrumb trail of links, called in theme/-/header.html
 *
 * This function has now been deprecated please use output's navbar method instead
 * as shown below
 *
 * <code php>
 * echo $OUTPUT->navbar();
 * </code>
 *
 * @deprecated since 2.0
 * @param mixed $navigation deprecated
 * @param string $separator OBSOLETE, and now deprecated
 * @param boolean $return False to echo the breadcrumb string (default), true to return it.
 * @return string|void String or null, depending on $return.
 */
function print_navigation ($navigation, $separator=0, $return=false) {
    global $OUTPUT,$PAGE;

    # debugging('print_navigation has been deprecated please update your theme to use $OUTPUT->navbar() instead', DEBUG_DEVELOPER);

    $output = $OUTPUT->navbar();

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * This function will build the navigation string to be used by print_header
 * and others.
 *
 * It automatically generates the site and course level (if appropriate) links.
 *
 * If you pass in a $cm object, the method will also generate the activity (e.g. 'Forums')
 * and activityinstances (e.g. 'General Developer Forum') navigation levels.
 *
 * If you want to add any further navigation links after the ones this function generates,
 * the pass an array of extra link arrays like this:
 * array(
 *     array('name' => $linktext1, 'link' => $url1, 'type' => $linktype1),
 *     array('name' => $linktext2, 'link' => $url2, 'type' => $linktype2)
 * )
 * The normal case is to just add one further link, for example 'Editing forum' after
 * 'General Developer Forum', with no link.
 * To do that, you need to pass
 * array(array('name' => $linktext, 'link' => '', 'type' => 'title'))
 * However, becuase this is a very common case, you can use a shortcut syntax, and just
 * pass the string 'Editing forum', instead of an array as $extranavlinks.
 *
 * At the moment, the link types only have limited significance. Type 'activity' is
 * recognised in order to implement the $CFG->hideactivitytypenavlink feature. Types
 * that are known to appear are 'home', 'course', 'activity', 'activityinstance' and 'title'.
 * This really needs to be documented better. In the mean time, try to be consistent, it will
 * enable people to customise the navigation more in future.
 *
 * When passing a $cm object, the fields used are $cm->modname, $cm->name and $cm->course.
 * If you get the $cm object using the function get_coursemodule_from_instance or
 * get_coursemodule_from_id (as recommended) then this will be done for you automatically.
 * If you don't have $cm->modname or $cm->name, this fuction will attempt to find them using
 * the $cm->module and $cm->instance fields, but this takes extra database queries, so a
 * warning is printed in developer debug mode.
 *
 * @deprecated since 2.0
 * @param mixed $extranavlinks - Normally an array of arrays, keys: name, link, type. If you
 *      only want one extra item with no link, you can pass a string instead. If you don't want
 *      any extra links, pass an empty string.
 * @param mixed $cm deprecated
 * @return array Navigation array
 */
function build_navigation($extranavlinks, $cm = null) {
    global $CFG, $COURSE, $DB, $SITE, $PAGE;

    if (is_array($extranavlinks) && count($extranavlinks)>0) {
        # debugging('build_navigation() has been deprecated, please replace with $PAGE->navbar methods', DEBUG_DEVELOPER);
        foreach ($extranavlinks as $nav) {
            if (array_key_exists('name', $nav)) {
                if (array_key_exists('link', $nav) && !empty($nav['link'])) {
                    $link = $nav['link'];
                } else {
                    $link = null;
                }
                $PAGE->navbar->add($nav['name'],$link);
            }
        }
    }

    return(array('newnav' => true, 'navlinks' => array()));
}

/**
 * Returns a small popup menu of course activity modules
 *
 * Given a course and a (current) coursemodule
 * his function returns a small popup menu with all the
 * course activity modules in it, as a navigation menu
 * The data is taken from the serialised array stored in
 * the course record
 *
 * @global object
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_COURSE
 * @param object $course A {@link $COURSE} object.
 * @param object $cm A {@link $COURSE} object.
 * @param string $targetwindow The target window attribute to us
 * @return string
 */
function navmenu($course, $cm=NULL, $targetwindow='self') {
    // This function has been deprecated with the creation of the global nav in
    // moodle 2.0

    return '';
}

/**
 * Returns a little popup menu for switching roles
 *
 * @deprecated in Moodle 2.0
 * @param int $courseid The course  to update by id as found in 'course' table
 * @return string
 */
function switchroles_form($courseid) {
    debugging('switchroles_form() has been deprecated and replaced by an item in the global settings block');
    return '';
}

/**
 * Print header for admin page
 * @deprecated since Moodle 20. Please use normal $OUTPUT->header() instead
 * @param string $focus focus element
 */
function admin_externalpage_print_header($focus='') {
    global $OUTPUT;

    debugging('admin_externalpage_print_header is deprecated. Please $OUTPUT->header() instead.', DEBUG_DEVELOPER);

    echo $OUTPUT->header();
}

/**
 * @deprecated since Moodle 1.9. Please use normal $OUTPUT->footer() instead
 */
function admin_externalpage_print_footer() {
// TODO Still 103 referernces in core code. Don't do debugging output yet.
    debugging('admin_externalpage_print_footer is deprecated. Please $OUTPUT->footer() instead.', DEBUG_DEVELOPER);
    global $OUTPUT;
    echo $OUTPUT->footer();
}

/// CALENDAR MANAGEMENT  ////////////////////////////////////////////////////////////////


/**
 * Call this function to add an event to the calendar table and to call any calendar plugins
 *
 * @param object $event An object representing an event from the calendar table.
 * The event will be identified by the id field. The object event should include the following:
 *  <ul>
 *    <li><b>$event->name</b> - Name for the event
 *    <li><b>$event->description</b> - Description of the event (defaults to '')
 *    <li><b>$event->format</b> - Format for the description (using formatting types defined at the top of weblib.php)
 *    <li><b>$event->courseid</b> - The id of the course this event belongs to (0 = all courses)
 *    <li><b>$event->groupid</b> - The id of the group this event belongs to (0 = no group)
 *    <li><b>$event->userid</b> - The id of the user this event belongs to (0 = no user)
 *    <li><b>$event->modulename</b> - Name of the module that creates this event
 *    <li><b>$event->instance</b> - Instance of the module that owns this event
 *    <li><b>$event->eventtype</b> - The type info together with the module info could
 *             be used by calendar plugins to decide how to display event
 *    <li><b>$event->timestart</b>- Timestamp for start of event
 *    <li><b>$event->timeduration</b> - Duration (defaults to zero)
 *    <li><b>$event->visible</b> - 0 if the event should be hidden (e.g. because the activity that created it is hidden)
 *  </ul>
 * @return int|false The id number of the resulting record or false if failed
 */
 function add_event($event) {
    global $CFG;
    require_once($CFG->dirroot.'/calendar/lib.php');
    $event = calendar_event::create($event);
    if ($event !== false) {
        return $event->id;
    }
    return false;
}

/**
 * Call this function to update an event in the calendar table
 * the event will be identified by the id field of the $event object.
 *
 * @param object $event An object representing an event from the calendar table. The event will be identified by the id field.
 * @return bool Success
 */
function update_event($event) {
    global $CFG;
    require_once($CFG->dirroot.'/calendar/lib.php');
    $event = (object)$event;
    $calendarevent = calendar_event::load($event->id);
    return $calendarevent->update($event);
}

/**
 * Call this function to delete the event with id $id from calendar table.
 *
 * @param int $id The id of an event from the 'event' table.
 * @return bool
 */
function delete_event($id) {
    global $CFG;
    require_once($CFG->dirroot.'/calendar/lib.php');
    $event = calendar_event::load($id);
    return $event->delete();
}

/**
 * Call this function to hide an event in the calendar table
 * the event will be identified by the id field of the $event object.
 *
 * @param object $event An object representing an event from the calendar table. The event will be identified by the id field.
 * @return true
 */
function hide_event($event) {
    global $CFG;
    require_once($CFG->dirroot.'/calendar/lib.php');
    $event = new calendar_event($event);
    return $event->toggle_visibility(false);
}

/**
 * Call this function to unhide an event in the calendar table
 * the event will be identified by the id field of the $event object.
 *
 * @param object $event An object representing an event from the calendar table. The event will be identified by the id field.
 * @return true
 */
function show_event($event) {
    global $CFG;
    require_once($CFG->dirroot.'/calendar/lib.php');
    $event = new calendar_event($event);
    return $event->toggle_visibility(true);
}

/**
 * Converts string to lowercase using most compatible function available.
 *
 * @deprecated Use textlib::strtolower($text) instead.
 *
 * @param string $string The string to convert to all lowercase characters.
 * @param string $encoding The encoding on the string.
 * @return string
 */
function moodle_strtolower($string, $encoding='') {

    debugging('moodle_strtolower() is deprecated. Please use textlib::strtolower() instead.', DEBUG_DEVELOPER);

    //If not specified use utf8
    if (empty($encoding)) {
        $encoding = 'UTF-8';
    }
    //Use text services
    return textlib::strtolower($string, $encoding);
}

/**
 * Original singleton helper function, please use static methods instead,
 * ex: textlib::convert()
 *
 * @deprecated since Moodle 2.2 use textlib::xxxx() instead
 * @see textlib
 * @return textlib instance
 */
function textlib_get_instance() {

    debugging('textlib_get_instance() is deprecated. Please use static calling textlib::functioname() instead.', DEBUG_DEVELOPER);

    return new textlib();
}

/**
 * Gets the generic section name for a courses section
 *
 * The global function is deprecated. Each course format can define their own generic section name
 *
 * @deprecated since 2.4
 * @see get_section_name()
 * @see format_base::get_section_name()
 *
 * @param string $format Course format ID e.g. 'weeks' $course->format
 * @param stdClass $section Section object from database
 * @return Display name that the course format prefers, e.g. "Week 2"
 */
function get_generic_section_name($format, stdClass $section) {
    debugging('get_generic_section_name() is deprecated. Please use appropriate functionality from class format_base', DEBUG_DEVELOPER);
    return get_string('sectionname', "format_$format") . ' ' . $section->section;
}

/**
 * Returns an array of sections for the requested course id
 *
 * It is usually not recommended to display the list of sections used
 * in course because the course format may have it's own way to do it.
 *
 * If you need to just display the name of the section please call:
 * get_section_name($course, $section)
 * {@link get_section_name()}
 * from 2.4 $section may also be just the field course_sections.section
 *
 * If you need the list of all sections it is more efficient to get this data by calling
 * $modinfo = get_fast_modinfo($courseorid);
 * $sections = $modinfo->get_section_info_all()
 * {@link get_fast_modinfo()}
 * {@link course_modinfo::get_section_info_all()}
 *
 * Information about one section (instance of section_info):
 * get_fast_modinfo($courseorid)->get_sections_info($section)
 * {@link course_modinfo::get_section_info()}
 *
 * @deprecated since 2.4
 *
 * @param int $courseid
 * @return array Array of section_info objects
 */
function get_all_sections($courseid) {
    global $DB;
    debugging('get_all_sections() is deprecated. See phpdocs for this function', DEBUG_DEVELOPER);
    return get_fast_modinfo($courseid)->get_section_info_all();
}

/**
 * Given a full mod object with section and course already defined, adds this module to that section.
 *
 * This function is deprecated, please use {@link course_add_cm_to_section()}
 * Note that course_add_cm_to_section() also updates field course_modules.section and
 * calls rebuild_course_cache()
 *
 * @deprecated since 2.4
 *
 * @param object $mod
 * @param int $beforemod An existing ID which we will insert the new module before
 * @return int The course_sections ID where the mod is inserted
 */
function add_mod_to_section($mod, $beforemod = null) {
    debugging('Function add_mod_to_section() is deprecated, please use course_add_cm_to_section()', DEBUG_DEVELOPER);
    global $DB;
    return course_add_cm_to_section($mod->course, $mod->coursemodule, $mod->section, $beforemod);
}

/**
 * Returns a number of useful structures for course displays
 *
 * Function get_all_mods() is deprecated in 2.4
 * Instead of:
 * <code>
 * get_all_mods($courseid, $mods, $modnames, $modnamesplural, $modnamesused);
 * </code>
 * please use:
 * <code>
 * $mods = get_fast_modinfo($courseorid)->get_cms();
 * $modnames = get_module_types_names();
 * $modnamesplural = get_module_types_names(true);
 * $modnamesused = get_fast_modinfo($courseorid)->get_used_module_names();
 * </code>
 *
 * @deprecated since 2.4
 *
 * @param int $courseid id of the course to get info about
 * @param array $mods (return) list of course modules
 * @param array $modnames (return) list of names of all module types installed and available
 * @param array $modnamesplural (return) list of names of all module types installed and available in the plural form
 * @param array $modnamesused (return) list of names of all module types used in the course
 */
function get_all_mods($courseid, &$mods, &$modnames, &$modnamesplural, &$modnamesused) {
    debugging('Function get_all_mods() is deprecated. Use get_fast_modinfo() and get_module_types_names() instead. See phpdocs for details', DEBUG_DEVELOPER);

    global $COURSE;
    $modnames      = get_module_types_names();
    $modnamesplural= get_module_types_names(true);
    $modinfo = get_fast_modinfo($courseid);
    $mods = $modinfo->get_cms();
    $modnamesused = $modinfo->get_used_module_names();
}

/**
 * Returns course section - creates new if does not exist yet
 *
 * This function is deprecated. To create a course section call:
 * course_create_sections_if_missing($courseorid, $sections);
 * to get the section call:
 * get_fast_modinfo($courseorid)->get_section_info($sectionnum);
 *
 * @see course_create_sections_if_missing()
 * @see get_fast_modinfo()
 * @deprecated since 2.4
 *
 * @param int $section relative section number (field course_sections.section)
 * @param int $courseid
 * @return stdClass record from table {course_sections}
 */
function get_course_section($section, $courseid) {
    global $DB;
    debugging('Function get_course_section() is deprecated. Please use course_create_sections_if_missing() and get_fast_modinfo() instead.', DEBUG_DEVELOPER);

    if ($cw = $DB->get_record("course_sections", array("section"=>$section, "course"=>$courseid))) {
        return $cw;
    }
    $cw = new stdClass();
    $cw->course   = $courseid;
    $cw->section  = $section;
    $cw->summary  = "";
    $cw->summaryformat = FORMAT_HTML;
    $cw->sequence = "";
    $id = $DB->insert_record("course_sections", $cw);
    rebuild_course_cache($courseid, true);
    return $DB->get_record("course_sections", array("id"=>$id));
}

/**
 * Return the start and end date of the week in Weekly course format
 *
 * It is not recommended to use this function outside of format_weeks plugin
 *
 * @deprecated since 2.4
 * @see format_weeks::get_section_dates()
 *
 * @param stdClass $section The course_section entry from the DB
 * @param stdClass $course The course entry from DB
 * @return stdClass property start for startdate, property end for enddate
 */
function format_weeks_get_section_dates($section, $course) {
    debugging('Function format_weeks_get_section_dates() is deprecated. It is not recommended to'.
            ' use it outside of format_weeks plugin', DEBUG_DEVELOPER);
    if (isset($course->format) && $course->format === 'weeks') {
        return course_get_format($course)->get_section_dates($section);
    }
    return null;
}
