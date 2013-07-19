<?php
/**
 * @package    filter
 * @subpackage courseup1
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__DIR__)) . '/config.php');
require_once($CFG->dirroot . '/local/up1_courselist/courselist_tools.php');
require_once $CFG->dirroot . '/local/widget_courselist/locallib.php';


class filter_courseup1 extends moodle_text_filter {

    public function filter($text, array $options = array()) {
        while ( preg_match('#\[course(tree|list|table|search)\s*(\S?[^\]]*?)\]#', $text, $matches) ) {
            list ($replace, $format, $paramstr) = $matches;

            switch ($format) {
                case 'tree':
                    $params = self::parse_parameters($paramstr, false);
                    if (empty($params['node'])) {
                        $replace = "<p>Erreur [course$format] : le paramètre requis 'node' n'est pas présent.</p>";
                    } else if (courselist_common::get_courses_from_pseudopath($params['node'])) {
                        $widget_url =  new moodle_url('/local/mwscoursetree/widget.js');
                        $script = '<script type="text/javascript" src="' . $widget_url . '"></script>';
                        $div = '<div class="coursetree" data-root="' . $params['node'] .'"></div>';
                        $replace = $div . $script;
                    } else {
                        $replace = '<p>'
                                . get_string('nomatchingcourse', 'local_up1_courselist')
                                . '</p>';
                    }
                    break;
                case 'table':
                case 'list':
                    $params = self::parse_parameters($paramstr, true);
                    $jsurl = new moodle_url('/local/jquery/init.dataTables.js');
                    $jsscript = '<script type="text/javascript" src="' . $jsurl . '"></script>';
                    /** @todo Simplify by using only one function in every case? */
                    if (isset($params['node']) && count($params) === 1) {
                        // simple case, without an extended search
                        $replace = courselist_common::list_courses_html($params['node'], $format) . $jsscript;
                    } else {
                        $replace = widget_courselist_query($format, (object) $params, false) . $jsscript;
                    }
                    break;
                case 'search':
                    $params = self::parse_parameters($paramstr, false);
                    $rand = random_string(10);
                    $json = json_encode($params);
                    $jsurl = new moodle_url('/local/widget_coursesearch/coursesearch.js');
                    $jsscript = '<script type="text/javascript" src="' . $jsurl . '"></script>'
                            .'<script type="text/javascript">'
                            . "window.coursesearch($json);"
                            . '</script>';
                    $init = "<div id=\"coursesearch-$rand\" class=\"widget-coursesearch\"></div>";
                    $replace = $init . $jsscript;
                    break;
                default:
                    $replace = '[course ' . $format . ' INCONNU]';
            }
            $text = str_replace($matches[0], $replace, $text);
        }
        return $text;
    }

    /**
     * Parses a string containing attributes like « k=v id="hop"  k2='v2'»
     *
     * @param string $str
     * @param bool   $inject_fields Insert up1* fields into profile_fields_*
     * @return array assoc array of parameters
     */
    protected static function parse_parameters($str, $inject_fields) {
        $toJs = array('true' => true, 'false' => false);
        $params = array();
        $coursefields = array();
        $table = array();
        $str = trim($str);
        while ($str) {
            $str = trim($str) . " ";
            $all = '';
            if (preg_match('/^\s*(\S+?)=([\'"])(.+?)\\2\s/', $str, $m)) {
                list ($all, $key, , $value) = $m;
            } else if (preg_match('/^\s*(\S+?)=([^\'"]\S*)\s/', $str, $m)) {
                list ($all, $key, $value) = $m;
            }
            if ($all) {
                if (strncmp($key, 'up1', 3) === 0) {
                    if ($inject_fields) {
                        $params['profile_field_' . $key] = $value;
                    } else {
                        $coursefields[] = $key;
                    }
                } else if (strncmp($key, 'table-', 6) === 0) {
                    if (isset($toJs[$value])) {
                        $value = $toJs[$value];
                    }
                    $table[substr($key, 6)] = $value;
                } else {
                    $params[$key] = $value;
                }
                $str = str_replace($all, '', $str);
            } else {
                if (trim($str) !== "") {
                    /// @todo Error: no more parameter found, yet the string isn't empty
                }
                break;
            }
        }
        if ($coursefields) {
            $params['fields'] = $coursefields;
        }
        if ($table) {
            $params['tableconfig'] = json_encode($table);
        }
        return $params;
    }
}
