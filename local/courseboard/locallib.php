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
    global $DB, $OUTPUT;

    $crsfields = custominfo_data::type('course')->get_structured_fields_short($crsid, true);
    foreach ($crsfields as $category => $fields) {
        if ($category == 'Other fields' || $category == 'Autres champs') continue;
            $catid = $DB->get_field('custom_info_category', 'id', array('name' => $category));
            $editurl = new moodle_url('/course/edit.php', array('id' => $crsid));
            $editurl->set_anchor('category_' . $catid);
            echo '<h4>' . $category . ' '
                . $OUTPUT->action_icon($editurl, new pix_icon('t/edit', 'Modifier les métadonnées'))
                . " </h4>\n";

            $table = new html_table();
            $table->data=array();
            foreach ($fields as $shortname => $field ) {
                $row = new html_table_row();
                $row->cells[0] = new html_table_cell($field['name']);
                $row->cells[0]->attributes = array('title' => $shortname, 'class' => '');
                $row->cells[1] = $field['data'];
                $row->cells[2] = (isset($rofdata[$category][$shortname]) ? $rofdata[$category][$shortname] : '(NA)');
                $table->data[] = $row;
            }
            $table->data = array_merge(get_table_course_header(), $table->data);
            echo html_writer::table($table);
    } // categories

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

/**
 * print html table of administration logs for a course (creation, validation...)
 * @global type $DB
 * @param int $crsid
 */
function print_admin_log($crsid) {
    global $DB;

    $table = new html_table();
    $table->classes = array('logtable','generalbox');
    $table->align = array('right', 'left', 'left');
    $table->head = array(
        get_string('time'),
        get_string('fullnameuser'),
        get_string('action'),
        get_string('info')
    );
    $table->data = array();

    $sql = "SELECT l.id, time, userid, ip, module, action, l.url, info, u.firstname, u.lastname "
         . "FROM {log} l JOIN {user} u  ON (l.userid = u.id)"
         . "WHERE ( ( module = 'course' AND action = 'new' AND info LIKE '%ID ".$crsid."%' ) "
         . " OR ( module = 'course' AND action != 'view' AND action != 'login' AND course = ? ) "
         . " OR (module IN ('course_validate', 'crswizard') AND course = ?)  ) "
         . " ORDER BY time ASC ";
    $logs = $DB->get_records_sql($sql, array($crsid, $crsid));

    foreach($logs as $log) {
        $row = new html_table_row();

        $row->cells[0] = new html_table_cell(userdate($log->time, '%Y-%m-%d %H:%M:%S'));
        $row->cells[1] = new html_table_cell($log->firstname . ' ' . $log->lastname);
        $row->cells[2] = new html_table_cell($log->module .' '. $log->action);
        $row->cells[3] = new html_table_cell($log->info);
        $table->data[] = $row;
    }
    echo html_writer::table($table);
}