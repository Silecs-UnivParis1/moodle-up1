<?php
/**
 * Plugin upgrade code.
 *
 * @package    local
 * @subpackage up1_metadata
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../datalib.php');
require_once(__DIR__ . '/../insertlib.php');
require_once(__DIR__ . '/../../roftools/roflib.php');

function xmldb_local_up1_metadata_upgrade($oldversion) {
    global $CFG, $DB;


    if ( true ) { // on peut faire cette mise à jour inconditionnellement
        $metadata = up1_course_metadata();

        echo "Création des catégories :<br />\n";
        insert_metadata_categories($metadata, 'course');

        echo "<br />\n<br />\n";
        echo "Création des champs :<br />\n";
        insert_metadata_fields($metadata, 'course');
    }

    if ( $oldversion < 2013070301 ) { // initialisation de categoriesbisrof
        echo "Initialisation de categoriesbisrof :<br />\n";
        update_categoriesbisrof();
    }


    return true;
}