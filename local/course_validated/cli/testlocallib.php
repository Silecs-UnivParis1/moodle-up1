<?php
/**
 * @package    local
 * @subpackage course_validated
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // global moodle config file.
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->dirroot.'/local/course_validated/locallib.php');

echo "courses : \n";
var_dump(get_id_courses_to_validate(0, 2));
var_dump(get_id_courses_to_validate(0, 0));
var_dump(get_id_courses_to_validate(0, 1));

var_dump(get_id_courses_to_validate(3, 2));
echo "\n";



