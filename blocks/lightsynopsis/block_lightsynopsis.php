<?php
/**
 * Light synopsis block.
 *
 * @package    block
 * @subpackage lightsynopsis
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class block_lightsynopsis extends block_base {
    public function init() {
        $this->title = 'À propos de cet espace';
    }
    

    public function get_content() {
        if ($this->content !== null) {
        return $this->content;
        }


        $format = course_get_format($this->page->course);
        $course = $format->get_course();


        $this->content         =  new stdClass;
        $this->content->text   = 'We are in course #' . $course->id;
        //$this->content->footer = 'Footer here...';

    return $this->content;
  }


    public function instance_allow_multiple() {
        return false;
    }

    function preferred_width() {
        return 210;
    }

    function hide_header() {
        return false;
    }

    function has_config() {
        return false;
    }
    
    function applicable_formats() {
        return array('course' => true,
                     'all' => false);
    }
    

} //class