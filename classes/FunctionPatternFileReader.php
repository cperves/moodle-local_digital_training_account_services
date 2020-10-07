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
 * local digital training account services hookmanager class
 *
 * @package    local_digital_training_account_services
 * @author Céline Pervès <cperves@unistra.fr>
 * @copyright Université de Strasbourg 2020 {@link http://unistra.fr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * */

namespace local_digital_training_account_services;

defined('MOODLE_INTERNAL') || die();

class FunctionPatternFileReader{
    private $relativepath = null;
    private $functionpattern = null;
    private $innerfilefunctions = array();
    private $filteredfunctions = array();
    public function __construct($relativepath) {
        $this->relativepath = $relativepath;
        // Read file and store functions.
        $this->extract_functions();
    }

    private function extract_functions() {
        $contents = file_get_contents($this->relativepath);
        $tokens = token_get_all($contents);
        $this->innerfilefunctions = array();
        for ($i = 0; $i < count($tokens); ++$i) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION) {
                $i += 2;
                $this->innerfilefunctions[] = $tokens[$i][1];
            }
        }
    }

    private function filter_functions() {
        if (isset($this->functionpattern)) {
            foreach ($this->innerfilefunctions as $innerfilefunction) {
                $matches = array();
                if ( preg_match($this->functionpattern, $innerfilefunction, $matches) === 1) {
                    if (count($matches) == 2) {
                        $this->filteredfunctions[] = $matches[1];
                    }
                }
            }
        }
    }

    public function filter_function_with_pattern($functionpattern) {
        $this->functionpattern = $functionpattern;
        $this->filter_functions();
        return $this->filteredfunctions;
    }

}