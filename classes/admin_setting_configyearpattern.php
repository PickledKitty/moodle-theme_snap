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

namespace theme_snap;

/**
 * The pattern that finds a course's year in its ID number: a regular expression without delimiters, with the year
 * in its first brackets. Saved only if it's a valid expression with a group.
 *
 * @package   theme_snap
 * @copyright 2026 PickledKitty
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_configyearpattern extends \admin_setting_configtext {
    /**
     * Constructor.
     *
     * @param string $name
     * @param string $visiblename
     * @param string $description
     */
    public function __construct($name, $visiblename, $description) {
        parent::__construct($name, $visiblename, $description, '', PARAM_RAW_TRIMMED);
    }

    /**
     * Empty, or a valid expression with brackets around the year.
     *
     * @param string $data
     * @return bool|string true, or the error message
     */
    public function validate($data) {
        if ($data === '') {
            return true;
        }
        if (@preg_match(services\course::year_pattern_regex($data), '') === false) {
            return get_string('courseyearpattern_invalid', 'theme_snap');
        }
        if (strpos($data, '(') === false) {
            return get_string('courseyearpattern_nogroup', 'theme_snap');
        }
        return true;
    }
}
