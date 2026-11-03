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
 * Tests for the lighter brand colours Snap adds for dark mode.
 *
 * @package   theme_snap
 * @copyright 2026 PickledKitty
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('theme_snap_dark_brand_color')]
#[\PHPUnit\Framework\Attributes\CoversFunction('theme_snap_add_dark_brand_colors')]
final class dark_brand_colors_test extends \advanced_testcase {

    /** @var string Bootstrap's $gray-800, the background of cards in dark mode. */
    private const SURFACE = '#343a40';

    /**
     * Snap with the given brand colour.
     *
     * @param string $color
     * @return \core\output\theme_config
     */
    private function theme(string $color): \core\output\theme_config {
        $theme = \core\output\theme_config::load('snap');
        $theme->settings->themecolor = $color;
        return $theme;
    }

    /**
     * Brand colours, and the lighter version each should get in dark mode.
     *
     * @return array
     */
    public static function brand_color_provider(): array {
        return [
            'dark purple is tinted' => ['#82009e', '#cd99d8'],
            'mid blue is tinted' => ['#3366cc', '#85a3e0'],
            'a colour already light enough is kept' => ['#ffcc00', '#ffcc00'],
            'black ends up light' => ['#000000', null],
        ];
    }

    /**
     * The lighter colour reads at 4.5:1 on dark mode cards.
     *
     * @param string $color
     * @param string|null $expected The lighter colour, or null to check only its contrast.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('brand_color_provider')]
    public function test_dark_brand_color(string $color, ?string $expected): void {
        $light = theme_snap_dark_brand_color($color);
        if ($expected !== null) {
            $this->assertSame($expected, $light);
        }
        $this->assertGreaterThanOrEqual(4.5, color_contrast::calculate_luminosity_ratio($light, self::SURFACE));
    }

    /**
     * Text, borders and outlines in the brand colour get a dark mode copy with only those declarations.
     */
    public function test_rule_gets_dark_copy(): void {
        $this->resetAfterTest();
        $css = theme_snap_add_dark_brand_colors('.a{color:#82009e;background:#82009e;padding:0}', $this->theme('#82009e'));
        $this->assertStringContainsString('.a{color:#82009e;background:#82009e;padding:0}:where([data-bs-theme=dark]) .a{color:#cd99d8}', $css);
    }

    /**
     * Rules without the brand colour, and @keyframes steps, are left alone.
     */
    public function test_other_rules_untouched(): void {
        $this->resetAfterTest();
        $css = theme_snap_add_dark_brand_colors('.b{color:#000}.c{background:#82009e}@keyframes k{from{color:#82009e}}',
            $this->theme('#82009e'));
        $this->assertStringNotContainsString(':where([data-bs-theme=dark]) .b', $css);
        $this->assertStringNotContainsString(':where([data-bs-theme=dark]) .c', $css);
        $this->assertStringNotContainsString(':where([data-bs-theme=dark]) from', $css);
    }

    /**
     * Selectors on html get the attribute themselves, and commas inside :not() don't split a selector.
     */
    public function test_selectors(): void {
        $this->resetAfterTest();
        $css = theme_snap_add_dark_brand_colors('html .c,.d:not(.e,.f){border-color:#82009e}', $this->theme('#82009e'));
        $this->assertStringContainsString('html:where([data-bs-theme=dark]) .c,:where([data-bs-theme=dark]) .d:not(.e,.f){border-color:#cd99d8}',
            $css);
    }

    /**
     * The darker shades Snap uses for hover, and the short form of a colour in compressed CSS, are covered too.
     */
    public function test_shades_and_short_form(): void {
        $this->resetAfterTest();
        // darken(#82009e, 10%).
        $css = theme_snap_add_dark_brand_colors('.h{color:#58006b}', $this->theme('#82009e'));
        $this->assertStringContainsString(':where([data-bs-theme=dark]) .h{color:#cd99d8}', $css);

        $css = theme_snap_add_dark_brand_colors('.s{color:#36c}', $this->theme('#3366cc'));
        $this->assertStringContainsString(':where([data-bs-theme=dark]) .s{color:#85a3e0}', $css);
    }

    /**
     * Category colours get their own lighter version.
     */
    public function test_category_colors(): void {
        $this->resetAfterTest();
        set_config('category_color', json_encode(['5' => '#3366cc']), 'theme_snap');
        $css = theme_snap_add_dark_brand_colors('.cat{color:#3366cc}', $this->theme('#82009e'));
        $this->assertStringContainsString(':where([data-bs-theme=dark]) .cat{color:#85a3e0}', $css);
    }

    /**
     * The site's lighter colour becomes the link colour in dark mode.
     */
    public function test_variables(): void {
        $this->resetAfterTest();
        $css = theme_snap_add_dark_brand_colors('', $this->theme('#82009e'));
        $this->assertStringContainsString('[data-bs-theme=dark]{--snap-brand-dark:#cd99d8;--bs-link-color:#cd99d8;' .
            '--bs-link-color-rgb:205,153,216;', $css);
    }
}
