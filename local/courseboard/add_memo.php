<?php

/**
 * @package    local
 * @subpackage courseboard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//defined('MOODLE_INTERNAL') || die();
require('../../config.php');
global $USER;
require_login();

$memo = $_POST['memo'];
$crsid = $_POST['crsid'];
add_to_log($crsid, 'courseboard', 'memo', '', addslashes($memo));

$url = new moodle_url('/local/courseboard/view.php', array('id' => $crsid, 'anchor' => 'course-log'));
$url->set_anchor('course-log');
redirect($url);


