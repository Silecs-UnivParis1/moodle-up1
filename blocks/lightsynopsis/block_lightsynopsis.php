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
        $this->content->text  = up1_meta_get_list($course->id, 'up1composante', false, ' / ', false) . '<br />';
        $this->content->text .= up1_meta_get_list($course->id, 'up1mention', false, ' / ', true) . '<br />';
        $this->content->text .= up1_meta_get_list($course->id, 'up1niveau', false, ' / ', true) . '<br />';

        $cdate = usergetdate($course->startdate);
        $this->content->text .= 'Créé le ' . $cdate['mday'].'/'.$cdate['mon'].'/'.$cdate['year'];
        $avalider = up1_meta_get_text($course->id, 'up1avalider', false);
        $datevalid = up1_meta_get_text($course->id, 'datevalid', false);
        if ($avalider == 1 && $datevalid == 0) {
            $this->content->text .= "(en attente d'approbation)";
        }
        $this->content->text .= '<br />';
        $this->content->text .= 'Enseignants : ' . courselist_format::format_teachers($course, 'span', 'teachers', 3) . '<br />';
        $this->content->text .= courselist_format::format_icons($course, 'span', 'icons');
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