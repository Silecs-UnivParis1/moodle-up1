<?php
/**
 * @package    local
 * @subpackage courseboard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/custominfo/lib.php');

function table_course_vs_rof($crsid, $rofdata) {

    $crsfields = custominfo_data::type('course')->get_structured_fields_short($crsid, true);
    echo "<ul>\n";
    // var_dump($rofcourse);

    foreach ($crsfields as $category => $fields) {
        if ($category == 'Other fields') continue;
            echo "<h4>" . $category . "</h4>\n";

            $table = new html_table();
            // var_dump($rofdata[$category]);
            // var_dump($fields);
            foreach ($fields as $shortname => $field ) {
                $row = new html_table_row();
                $row->cells[0] = new html_table_cell($field['name']);
                $row->cells[0]->attributes = array('title' => $shortname, 'class' => '');
                $row->cells[1] = $field['data'];
                $row->cells[2] = (isset($rofdata[$category][$shortname]) ? $rofdata[$category][$shortname] : '(donnée absente)');
                $table->data[] = $row;
            }
            $table->data = array_merge(get_table_course_header(), $table->data);
            echo html_writer::table($table);
    } // categories
    echo "</ul>\n";
}


function get_table_course_header() {
    $headings = array('Métadonnée', 'Cours', 'ROF');
    $row = array();
    foreach ($headings as $h) {
        $cell = new html_table_cell($h);
        $cell->header = true;
        $row[] = $cell;
    }
    return array($row);
}
