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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/theme/snap/lib.php');

/**
 * Tests for the header band's gradient colours (Cover display settings).
 *
 * @package   theme_snap
 * @copyright 2026 PickledKitty
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('theme_snap_get_pre_scss')]
final class header_gradient_test extends \advanced_testcase {

    /**
     * Snap's pre-SCSS with the given gradient settings.
     *
     * @param string|null $start
     * @param string|null $end
     * @return string
     */
    private function pre_scss(?string $start, ?string $end): string {
        $theme = \core\output\theme_config::load('snap');
        $theme->settings->headergradientstart = $start;
        $theme->settings->headergradientend = $end;
        return theme_snap_get_pre_scss($theme);
    }

    /**
     * Without settings the SCSS defaults apply: the brand colour, then a lighter shade of it.
     */
    public function test_empty_settings_leave_the_brand_gradient(): void {
        $this->resetAfterTest();
        $scss = $this->pre_scss(null, '');
        $this->assertStringNotContainsString('$snap-header-gradient-start', $scss);
        $this->assertStringNotContainsString('$snap-header-gradient-end', $scss);
    }

    /**
     * Each colour that's set becomes its SCSS variable; the other is left to its default.
     */
    public function test_set_colours_become_scss_variables(): void {
        $this->resetAfterTest();
        $scss = $this->pre_scss('#82c341', '#00abcc');
        $this->assertStringContainsString('$snap-header-gradient-start: #82c341;', $scss);
        $this->assertStringContainsString('$snap-header-gradient-end: #00abcc;', $scss);

        $scss = $this->pre_scss('#82c341', '');
        $this->assertStringContainsString('$snap-header-gradient-start: #82c341;', $scss);
        $this->assertStringNotContainsString('$snap-header-gradient-end', $scss);
    }
}
