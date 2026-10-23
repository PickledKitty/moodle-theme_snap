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
 * The course index on activity pages: its tab opens it and, in the same place, closes it again.
 *
 * The tab's label follows the drawer, and opening the drawer shows the current activity, opening
 * its section if Snap's course index has it collapsed.
 *
 * @module     theme_snap/activity_course_index
 * @copyright  2026 PickledKitty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Drawers from 'theme_boost/drawers';
import {Collapse} from 'bootstrap';

const DRAWER_ID = 'theme_boost-drawers-courseindex';

/**
 * Initialise.
 */
export const init = () => {
    const drawer = document.getElementById(DRAWER_ID);
    const button = document.querySelector('.snap-courseindex-toggle button');
    if (!drawer || !button) {
        return;
    }

    const label = open => {
        const title = open ? button.dataset.closetitle : button.dataset.opentitle;
        button.setAttribute('title', title);
        button.setAttribute('data-bs-original-title', title);
        button.setAttribute('aria-label', title);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    const showCurrentActivity = () => {
        const item = drawer.querySelector('.pageitem');
        if (!item) {
            return;
        }
        const section = item.closest('.courseindex-item-content.collapse');
        if (section && !section.classList.contains('show')) {
            Collapse.getOrCreateInstance(section, {toggle: false}).show();
        }
        // After the section has opened.
        setTimeout(() => item.scrollIntoView({block: 'nearest'}), 400);
    };

    document.addEventListener(Drawers.eventTypes.drawerShown, e => {
        if (e.target === drawer) {
            label(true);
            showCurrentActivity();
        }
    });
    document.addEventListener(Drawers.eventTypes.drawerHidden, e => {
        if (e.target === drawer) {
            label(false);
        }
    });
};
