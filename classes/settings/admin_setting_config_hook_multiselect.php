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
 * local digital training account services admin setting class for selecting hooks
 *
 * @package    local_digital_training_account_services
 * @author Céline Pervès <cperves@unistra.fr>
 * @copyright Université de Strasbourg 2020 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * */
namespace local_digital_training_account_services\settings;
defined('MOODLE_INTERNAL') || die();

class admin_setting_config_hook_multiselect extends \admin_setting_configmultiselect {

    public function __construct($name, $visiblename, $description, $functionsuffixe, $hookfilerelativepath, $defaultsetting) {
        global $CFG;
        $hookfile = $CFG->dirroot.'/'.$hookfilerelativepath;
        $functionpattern = '/([a-z]+_[a-z]+)'.$functionsuffixe.'/';// Plugintype_plugin_name_functionsuffixe.
        $functionreader = new \local_digital_training_account_services\FunctionPatternFileReader($hookfile);
        $functions = $functionreader->filter_function_with_pattern($functionpattern);
        $choices = array();
        foreach ($functions as $currentfunction) {
            $choices[$currentfunction] = get_string('pluginname', $currentfunction);
        }
         parent::__construct($name, $visiblename, $description, $defaultsetting, $choices);
    }

    public function output_html($data, $query='') {
        global $OUTPUT;

        if (!$this->load_choices() or empty($this->choices)) {
            $context = (object) [
                'id' => $this->get_id(),
                'name' => $this->get_full_name(),
            ];
            $element = $OUTPUT->render_from_template('core_admin/setting_configempty', $context);
            return format_admin_setting($this, $this->visiblename, $element, $this->description, true, '', get_string('none'), $query);
        } else {
            return parent::output_html($data, $query);
        }
    }

    public function write_setting($data) {
        if (!$this->load_choices() or empty($this->choices)) {
            return ($this->config_write($this->name, '') ? '' : get_string('errorsetting', 'admin'));
        } else {
            return parent::write_setting($data);
        }
    }

}