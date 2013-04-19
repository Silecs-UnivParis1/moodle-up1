<?php
/**
 * @package    filter
 * @subpackage coursetree
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/up1_courselist/courselist_tools.php');


class filter_coursetree extends moodle_text_filter {

    public function filter($text, array $options = array()) {
        while ( preg_match( '#\[courselist format=([a-z]+) node=(/[\w/-]*)\]#', $text, $matches) ) {
            $format = $matches[1];
            $node = $matches[2];
            $replace = $matches[0];

            switch ($format) {
                case 'tree':
                    $widget =  new moodle_url('/local/mwscoursetree/widget.js');
                    $script = '<script type="text/javascript" src="' . $widget . '"></script>';
                    $div = '<div class="coursetree" data-root="' . $node .'"></div>';
                    $replace = $script . $div;
                    break;
                case 'table':
                    $replace = courselist_common::html_course_table($node);
                    break;
                case 'list':
                    $replace = courselist_common::html_course_list($node);
                    break;
                default:
                    $replace = '[courselist : FORMAT ' . $format . ' INCONNU]';
            }
            $text = str_replace($matches[0], $replace, $text);
        }
        return $text;
    }

}
