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
 * local digital training account services settings
 *
 * @package    local_digital_training_account_services
 * @author Céline Pervès <cperves@unistra.fr>
 * @copyright Université de Strasbourg 2020 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!defined('DEFAUT_MODULE_GROUPED')) {
    define('DEFAUT_MODULE_GROUPED', '{"resource":["resource","folder","page","url","book"],"msg":["forum"]}');
}
if ($hassiteconfig) {
    require_once(__DIR__.'/locallib.php');
    $settings = new admin_settingpage('local_digital_training_account_services',
            get_string('pluginname', 'local_digital_training_account_services'));
    $ADMIN->add('localplugins', $settings);
    $settings->add(
        new \local_digital_training_account_services\settings\admin_setting_grouping_mods(
            'local_digital_training_account_services/modulegrouped',
            get_string('modulegrouped', 'local_digital_training_account_services'),
            get_string('modulegrouped_desc', 'local_digital_training_account_services'),
            DEFAUT_MODULE_GROUPED
        )
    );
    $settings->add(
        new admin_setting_configcheckbox('local_digital_training_account_services/unloggedasnew',
            get_string('unloggedasnew', 'local_digital_training_account_services'),
            get_string('unloggedasnew_desc', 'local_digital_training_account_services'),
            0
        )
    );
    $settings->add(
        new admin_setting_configcheckbox('local_digital_training_account_services/courseviewreinit',
            get_string('courseviewreinit', 'local_digital_training_account_services'),
            get_string('courseviewreinit_desc', 'local_digital_training_account_services'),
            1
        )
    );
    // Hook choice to counters.
    $settings->add(
        new \local_digital_training_account_services\settings\admin_setting_config_hook_multiselect(
            'local_digital_training_account_services/counterhookedmodules',
            get_string('counterhookedmodules', 'local_digital_training_account_services'),
            get_string('counterhookedmodules_desc', 'local_digital_training_account_services'),
                '_counter_special_implementation',
            '/local/digital_training_account_services/hooklib.php',
            array('mod_forum')
        )
    );
    // Hook choice for course_view events. uncomment if any new _course_view_special_implementation in hooklib.php
    $settings->add(
        new \local_digital_training_account_services\settings\admin_setting_config_hook_multiselect(
            'local_digital_training_account_services/courseviewhookedmodules',
            get_string('courseviewhookedmodules', 'local_digital_training_account_services'),
            get_string('courseviewhookedmodules_desc', 'local_digital_training_account_services'),
            '_course_view_special_implementation',
            '/local/digital_training_account_services/hooklib.php',
            array()
        )
    );
    $settings->add(
        new admin_setting_configtext('local_digital_training_account_services/calendarlimitnumber',
            get_string('calendarlimitnumber', 'local_digital_training_account_services'),
            get_string('calendarlimitnumber_desc', 'local_digital_training_account_services'),
            200
        )
    );
    // Meta datas section for positioning tests.
    $settings->add(
        new admin_setting_configtext('local_digital_training_account_services/positioning_metadata_field',
            get_string('positioning_metadata_field', 'local_digital_training_account_services'),
            get_string('positioning_metadata_field_desc', 'local_digital_training_account_services'),
            'eolepositioning'
        )
    );
}