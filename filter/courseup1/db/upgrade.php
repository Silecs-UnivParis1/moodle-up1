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
    global $CFG, $DB;

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
        if ( preg_match('/\[courselist format=tree/', $page->content) ) {
            $substpages++;
            $newcontent = str_replace('[courselist format=tree', '[coursetree', $page->content, $cnt);
            $substoccur += $cnt;
            $page->content = $newcontent;
            $DB->update_record('page', $page);
        }
    }
    echo "Total = $substpages pages, $substoccur substitutions.<br />\n";
}