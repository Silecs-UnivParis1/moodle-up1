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
        $avalider = up1_meta_get_text($course->id, 'up1avalider', false);
        $datevalid = up1_meta_get_text($course->id, 'datevalid', false);
        $cdate = usergetdate($course->startdate);
        $dispdate = 'Créé le ' . $cdate['mday'].'/'.$cdate['mon'].'/'.$cdate['year']
                . (($avalider == 1 && $datevalid == 0) ? " (en attente d'approbation)" : '');

        $this->content =  new stdClass;
        $courseformatter = new courselist_format('list');
        $this->content->text  = 'Composante : '
            . $this->br(up1_meta_get_list($course->id, 'up1composante', false, ' / ', false))
            . $this->br(up1_meta_get_list($course->id, 'up1mention', false, ' / ', true))
            . $this->br(up1_meta_get_list($course->id, 'up1niveau', false, ' / ', true))
            . $this->br('Enseignants : ' . $courseformatter->format_teachers($course, 'teachers', 3))
            . $this->br($dispdate .' '.  $courseformatter->format_icons($course, 'icons'));
        //$this->content->footer = 'Footer here...';

    return $this->content;
  }


    /**
     * return the input string followed by a newline (<br />) if not empty, or empty string otherwise.
     * @param type $text
     * @return string
     */
    public function br($text) {
        if ($text) {
            return $text . '<br />';
        } else {
            return '';
        }
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