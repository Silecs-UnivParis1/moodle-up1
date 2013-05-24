<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'assignsubmission_onlineaudio', language 'fr'
 *
 * @package assignsubmission_onlineaudio
 * @copyright 2012 Paul Nicholls
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$string['pluginname'] = 'Enregistrement audio en-ligne';
$string['recording'] = 'Enregistrement audio en-ligne';

$string['enabled'] = 'Enregistrement audio en-ligne';
$string['enabled_help'] = 'S\'il est activé, les étudiants sont autorisés à remettre leurs devoirs sous forme d\'enregistrement audio.';

$string['configmaxbytes'] = 'Taille limite de fichier';
$string['maxbytes'] = 'Taille limite de fichier';

$string['maxfilessubmission'] = 'Nombre maximal d\'enregistrements';
$string['maxfilessubmission_help'] = 'Si le plugin est activé, chaque étudiant sera autorisé à remettre au maximum ce nombre d\'enregistrements.'

$string['defaultname'] = 'Motif de fichier par défaut';
$string['defaultname_help'] = 'Ce paramètre permet de pré-remplir le nom de fichier à partir d\'un motif. '
    . 'Le nom pré-rempli peut être contrôlé en fixant à "Non" le paramètre "Autoriser les étudiants à changer le nom".';

$string['nodefaultname'] = 'Aucun (vide)';
$string['defaultname1'] = 'username_assignment_course_date';
$string['defaultname2'] = 'fullname_assignment_course_date';

$string['allownameoverride'] = 'Autoriser les étudiants à changer le nom du fichier';
$string['allownameoverride_help'] = 'S\'il est activé, les étudiants sont autorisés à changer le nom du fichier par défaut comme ils le veulent. '
    . 'Ce paramètre est sans effet si "Motif de fichier par défaut" est fixé à "Aucun (vide)", puisqu\'un nom doit être spécifié';

$string['countfiles'] = '{$a} fichiers';
$string['nosuchfile'] = 'Aucun fichier correspondant.';
$string['confirmdeletefile'] = 'Êtes vous certain de vouloir supprimer le fichier <strong>{$a}</strong>?';
$string['upload'] = 'Envoyer';
$string['uploaderror'] = 'Erreur pendant l\'envoi de l\'enregistrement.';
$string['maxfilesreached'] = 'Vous avez déjà atteint le nombre maximal de remises pour ce devoir.';

