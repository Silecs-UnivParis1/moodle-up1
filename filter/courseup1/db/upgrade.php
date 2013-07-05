<?php
/**
 * @package    filter
 * @subpackage courseup1
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Plugin upgrade code.
 */

defined('MOODLE_INTERNAL') || die;


function xmldb_filter_courseup1_upgrade($oldversion) {
    //global $CFG, $DB;

    if ( $oldversion < 2013070402 ) {
        echo "Correction des contenus pages :<br />\n";
        update_coursetree_pages();
    }
    return true;
}


function update_coursetree_pages() {
    global $DB;

    $pages = $DB->get_records('page');
    $substpages = 0;
    $substoccur = 0;
    foreach ($pages as $page) {
        $cnt = 0;
        $page->content = preg_replace('/\[courselist format=(\w+)\b/', '[course$1', $page->content, -1, $cnt);
        if ($cnt) {
            $substpages++;
            $substoccur += $cnt;
            $DB->update_record('page', $page);
        }
    }
    echo "Total = $substpages pages, $substoccur substitutions.<br />\n";
}