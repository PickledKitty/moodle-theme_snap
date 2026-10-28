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
 * A category's course listing: one control to open or close all the course summaries.
 *
 * Moodle opens and closes each summary from its link (course/yui categoryexpander); this clicks those for every
 * course that needs it, and keeps the control's wording to what it will do next.
 *
 * @module     theme_snap/category_toggle
 * @copyright  2026 PickledKitty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getStrings} from 'core/str';

const SELECTORS = {
    TOGGLE_ALL: '.coursebox-expand-all',
    CLOSED: '.coursebox.collapsed .moreinfo a',
    OPEN: '.coursebox.loaded:not(.collapsed) .moreinfo a',
    COURSE: '.coursebox',
};

export const init = async() => {
    const toggle = document.querySelector(SELECTORS.TOGGLE_ALL);
    if (!toggle || toggle.dataset.snapInit) {
        return;
    }
    toggle.dataset.snapInit = '1';
    const [expandall, collapseall] = await getStrings([
        {key: 'expandall', component: 'core'},
        {key: 'collapseall', component: 'core'},
    ]);

    // Open them all while any is closed, then close them all.
    const update = () => {
        toggle.textContent = document.querySelector(SELECTORS.CLOSED) ? expandall : collapseall;
    };
    update();

    toggle.addEventListener('click', () => {
        const closed = document.querySelectorAll(SELECTORS.CLOSED);
        const links = closed.length ? closed : document.querySelectorAll(SELECTORS.OPEN);
        links.forEach(link => link.click());
    });
    // Whenever a course opens or closes, by this or on its own: a summary loads first, then opens.
    const observer = new MutationObserver(update);
    document.querySelectorAll(SELECTORS.COURSE).forEach(course =>
        observer.observe(course, {attributes: true, attributeFilter: ['class']}));
};
