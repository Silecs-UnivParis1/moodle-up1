<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__DIR__))).'/config.php'); // global moodle config file.
require(__DIR__ . '/../roflib.php');


// echo rof_typeDiplome_ordered_list() . "\n\n";
/*
echo rof_format_path(rof_get_course_first_path($argv[1]), 'combined') . "\n\n";
 *
 */
// echo "\n";

// rof_insert_paths_statistics(0);
$path = array('27', 'UP1-PROG37797', 'UP1-PROG25375');
$cpath = array(
    '27' => '27-Mathématiques et Informatique',
    'UP1-PROG37797' => 'Licence Informatique (MIAGE classique)',
    'UP1-PROG25375' => 'Semestre 5 L3'
);

/*
$catid = rof_rofpath_to_category($path);
echo "$catid";
$categorie = $DB->get_record('course_categories', array('id' => $catid));
print_r ($categorie);
*/


// N'existe pas
/*
$path = array('27', 'UP1-PROG17797', 'UP1-PROG25375');
//27-Mathématiques et Informatique / Licence Informatique (MIAGE classique) /  Semestre 5 L3
$catid = rofpath_to_category($path);
*/

/*
echo "\n\n" . rof_get_code_or_rofid('UP1-C25379'); // should return MA32I310
echo "\n\n" . rof_get_code_or_rofid('UP1-C99999');
echo "\n\n" . rof_get_code_or_rofid('UP1-PROG37797');
echo "\n\n" ;
*/

// print_r(rof_get_menu_constant("composante"));
// print_r(rof_get_menu_constant("composante", false));

// bad data test (multiple U4)
// print_r(rof_get_metadata('UP1-PROG20152'));

$bug = array(
        array('01', 'UP1-PROG28576'),
        array('03', 'UP1-PROG35699'),
        array( '07', 'UP1-PROG39740', 'UP1-PROG39751', 'UP1-C39752')
        );


echo "rof_get_metadata (4)" ;
// print_r(rof_get_metadata('UP1-PROG25375'));
/*
print_r(rof_get_metadata($bug[0]));
echo "*****\n";
print_r(rof_get_metadata($bug[1]));
echo "*****\n";
print_r(rof_get_metadata($bug[2]));
echo "*****\n";
*/
// print_r(rof_get_metadata_concat($bug));
echo "*****\n";


//print_r(rof_get_metadata_concat($bugpath));


echo "*****\n";
//print_r(rof_get_metadata_concat(array('UP1-C36772', 'UP1-C36773')));
//print_r(rof_get_metadata($path));
//print_r(rof_get_metadata($cpath));
//print_r(rof_get_metadata('UP1-C25379'));

return 0;
