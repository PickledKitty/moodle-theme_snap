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

namespace theme_snap\output\core_user;

use core_user\output\myprofile\tree;
use core\output\html_writer;

/**
 * Profile pages: the sections in Snap's order.
 *
 * @package   theme_snap
 * @copyright 2026 PickledKitty
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class myprofile_renderer extends \core_user\output\myprofile\renderer {

    /**
     * The sections first, by their names in the tree, after which the rest keep Moodle's order.
     */
    const ORDER = ['contact', 'reports', 'administration', 'loginactivity', 'mobile', 'miscellaneous', 'coursedetails',
        'privacyandpolicies'];

    /**
     * Render the whole tree, with the sections in Snap's order. Which sections there are depends on who's looking,
     * so they're placed by name, not position.
     *
     * @param tree $tree
     * @return string
     */
    public function render_tree(tree $tree) {
        $categories = $tree->categories;
        $position = array_flip(self::ORDER);
        $moodleorder = array_flip(array_keys($categories));
        uksort($categories, fn($a, $b) =>
            [$position[$a] ?? count(self::ORDER), $moodleorder[$a]] <=> [$position[$b] ?? count(self::ORDER), $moodleorder[$b]]);

        $return = html_writer::start_tag('div', ['class' => 'profile_tree']);
        foreach ($categories as $category) {
            $return .= $this->render($category);
        }
        $return .= html_writer::end_tag('div');
        return $return;
    }
}
