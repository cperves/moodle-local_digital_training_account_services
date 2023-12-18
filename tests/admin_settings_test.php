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
 * local_digital_training_account_services tests.
 *
 * @package    local_digital_training_account_services
 * @copyright  2020 Université de Strasbourg {@link https://unistra.fr}
 * @author  Céline Pervès <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_digital_training_account_services;
use advanced_testcase;
use local_digital_training_account_services\settings\admin_setting_config_hook_multiselect;

require_once(__DIR__.'/../locallib.php');

class admin_settings_test extends advanced_testcase{
    public function test_admin_setting_config_hook_multiselect_counter_hook() {
        global $CFG;
        require_once($CFG->libdir.'/adminlib.php');
        $setting = new admin_setting_config_hook_multiselect(
            'local_digital_training_account_services/counterhookedmodules',
            get_string('counterhookedmodules', 'local_digital_training_account_services'),
            get_string('counterhookedmodules_desc', 'local_digital_training_account_services'),
            '_counter_special_implementation',
            '/local/digital_training_account_services/tests/hooklibmoc.php',
            'forum'
        );
        $choices = $setting->choices;
        $this->assertCount(2, $choices);
        $this->assertContains('mod_forum', array_keys($choices));
        $this->assertContains('mod_chat', array_keys($choices));
    }

    public function test_admin_setting_config_hook_multiselect_courseview_hook() {
        global $CFG;
        require_once($CFG->libdir.'/adminlib.php');
        $setting = new admin_setting_config_hook_multiselect(
            'local_digital_training_account_services/counterhookedmodules',
            get_string('courseviewhookedmodules', 'local_digital_training_account_services'),
            get_string('courseviewhookedmodules_desc', 'local_digital_training_account_services'),
            '_course_view_special_implementation',
            '/local/digital_training_account_services/tests/hooklibmoc.php',
            'forum'
        );
        $choices = $setting->choices;
        $this->assertCount(2, $choices);
        $this->assertContains('mod_forum', array_keys($choices));
    }
}