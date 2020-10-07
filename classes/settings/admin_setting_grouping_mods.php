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
 * local digital training account services admin setting class for grouping modules
 *
 * @package    local_digital_training_account_services
 * @author Céline Pervès <cperves@unistra.fr>
 * @copyright Université de Strasbourg 2020 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_digital_training_account_services\settings;

defined('MOODLE_INTERNAL') || die();

class admin_setting_grouping_mods extends \admin_setting_configtext {

    /**
     * override write_setting to add inverted object module -> groupnames construct from  groupname -> modules
     * @param $data
     * @return mixed|string|void
     */
    public function write_setting($data) {
        $groups = json_decode($data, true);
        $modgroups = array();
        foreach ($groups as $groupname => $groupmodules) {
            foreach ($groupmodules as $groupmodule) {
                if (!array_key_exists($groupmodule, $modgroups)) {
                    $modgroups[$groupmodule] = array();
                }
                if (!in_array($groupname, $modgroups[$groupmodule])) {
                    $modgroups[$groupmodule][] = $groupname;
                }
            }
        }
        // We store modulegroups and groupedmodule version.
        $fusion = array();
        $fusion["groupmodules"] = $groups;
        $fusion["modulegroups"] = $modgroups;
        return parent::write_setting(json_encode($fusion));
    }

    public function output_html($data, $query= '') {
        // Retrieve only one part of stored object to display.
        $dataarray = json_decode($data, true);
        $data = json_encode($dataarray['groupmodules']);
        return parent::output_html($data, $query);
    }
}