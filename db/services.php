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
 * local digital training account services web services declaration
 *
 * @package    local_digital_training_account_services
 * @author Céline Pervès <cperves@unistra.fr>
 * @copyright Université de Strasbourg 2020 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_digital_training_account_services_get_courses_infos' => array(
        'classname' => 'local_digital_training_account_services_external',
        'methodname' => 'get_courses_infos',
        'classpath' => 'local/digital_training_account_services/externallib.php',
        'description' => 'get courses informations',
        'type' => 'read',
        'capabilitites' => 'local/digital_training_account_services:course_list_informations_for_other_user',
        'ajax' => true,
        'services' => array('wsdigitaltrainingaccountservice'),
    ),
    'local_digital_training_account_services_get_positioning_tests' => array(
        'classname' => 'local_digital_training_account_services_external',
        'methodname' => 'get_positioning_tests',
        'classpath' => 'local/digital_training_account_services/externallib.php',
        'description' => 'get positioning tests',
        'type' => 'read',
        'capabilitites' => 'local/digital_training_account_services:positioning_tests_list_informations_for_other_user',
        'ajax' => true,
        'services' => array('wsdigitaltrainingaccountservice'),
    ),
    'local_digital_training_account_services_get_competencies' => array(
        'classname' => 'local_digital_training_account_services_external',
        'methodname' => 'get_competencies',
        'classpath' => 'local/digital_training_account_services/externallib.php',
        'description' => 'get competencies',
        'type' => 'read',
        'capabilitites' => 'local/digital_training_account_services:course_list_informations_for_other_user, '
            .'moodle/moodle/competency:usercompetencyview',
        'ajax' => true,
        'services' => array('wsdigitaltrainingaccountservice'),
    )
);

$services = array(
        'Digital training account services web services' => array(
                'functions' => array (  'local_digital_training_account_services_get_positioning_tests',
                                        'local_digital_training_account_services_get_courses_infos',
                                        'local_digital_training_account_services_get_competencies'
                                ),
                'requiredcapability' => '',
                'restrictedusers' => 1,
                'enabled' => 1,
                'shortname' => 'wsdigitaltrainingaccountservice'
        )
);