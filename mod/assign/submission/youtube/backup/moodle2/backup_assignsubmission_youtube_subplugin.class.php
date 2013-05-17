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
 * This file contains the class for backup of this submission plugin
 * Development funded by: Global Awakening (@link http://www.globalawakening.com)
 *
 * @package    assignsubmission_youtube
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('ASSIGNSUBMISSION_YOUTUBE_TABLE', 'assignsubmission_youtube');

/**
 * Provides the information to backup youtube submissions
 *
 * This just adds its filearea to the annotations and records the submissiontext and format
 *
 * @package    assignsubmission_youtube
 * @copyright 2012 Justin Hunt {@link http://www.poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_assignsubmission_youtube_subplugin extends backup_subplugin {

    /**
     *
     * Returns the subplugin information to attach to submission element
     * @return backup_subplugin_element
     */
    protected function define_submission_subplugin_structure() {

        // create XML elements
        $subplugin = $this->get_subplugin_element(); // virtual optigroup element
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $subpluginelement = new backup_nested_element('submission_youtube', null, array('widgettype', 'submission'));

        // connect XML elements into the tree
        $subplugin->add_child($subpluginwrapper);
        $subpluginwrapper->add_child($subpluginelement);

        // set source to populate the data
        $subpluginelement->set_source_table(ASSIGNSUBMISSION_YOUTUBE_TABLE, array('submission' => backup::VAR_PARENTID));

        $subpluginelement->annotate_files(ASSIGNSUBMISSION_YOUTUBE_TABLE, 'submissions_youtube', 'submission');
        return $subplugin;
    }

}
