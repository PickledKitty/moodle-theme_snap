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
 * Printing in dark mode.
 *
 * Browsers leave out background colours when printing, so a page in dark mode would print its light text on white
 * paper. The page is switched to light colours just before printing and back afterwards. All the dark colours hang
 * off the data-bs-theme attribute, so this covers every one of them.
 *
 * @module     theme_snap/print_colours
 * @copyright  2026 PickledKitty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

let mode = null;

export const init = () => {
    const root = document.documentElement;

    window.addEventListener('beforeprint', () => {
        mode = root.getAttribute('data-bs-theme');
        if (mode === 'dark') {
            root.setAttribute('data-bs-theme', 'light');
        }
    });

    window.addEventListener('afterprint', () => {
        if (mode === 'dark') {
            root.setAttribute('data-bs-theme', 'dark');
        }
        mode = null;
    });
};
