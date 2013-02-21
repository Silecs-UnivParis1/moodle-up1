<?php
/**
 * @package    filter
 * @subpackage coursetree
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class filter_coursetree extends moodle_text_filter {

    public function filter($text, array $options = array()) {
        if ( preg_match( '#\[coursetree (/[\d/]+[\w/-]*)\]#', $text, $matches) ) {
                $widget =  new moodle_url('/local/mwscoursetree/widget.js');
                $script = '<script type="text/javascript" src="' . $widget . '"></script>';
                $div = '<div class="coursetree" data-root="' . $matches[1] .'"></div>';

            //$replace = 'coursetree ' . print_r($options, true);
            $replace = $script . $div;
            return str_replace($matches[0], $replace, $text);
        } else {
            return $text;
        }

    }

}
