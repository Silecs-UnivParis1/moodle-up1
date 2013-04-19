<?php
/**
 * @package    local
 * @subpackage up1_courselist
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$handlers = array (
    'course_created' => array (
        'handlerfile'      => '/local/up1_courselist/eventslib.php',
        'handlerfunction'  => 'handle_course_modified',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),

    'course_updated' => array (
        'handlerfile'      => '/local/up1_courselist/eventslib.php',
        'handlerfunction'  => 'handle_course_modified',
        'schedule'         => 'instant',
        'internal'         => 1,
    )
);