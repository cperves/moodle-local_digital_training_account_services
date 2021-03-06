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
 * local digital training account services
 *
 * @package    local_digital_training_account_services
 * @author Céline Pervès <cperves@unistra.fr>
 * @copyright Université de Strasbourg 2020 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2020102700; // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires = 2018050800; // Requires this Moodle version.
$plugin->component = 'local_digital_training_account_services';
$plugin->dependencies = array(
        'logstore_last_viewed_course_module' => ANY_VERSION,
        'logstore_last_updated_course_module' => ANY_VERSION,
	'local_metadata' => ANY_VERSION,
	'local_metadata_tools' => ANY_VERSION
);
