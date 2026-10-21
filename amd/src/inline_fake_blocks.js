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
 * Moves fake blocks, such as the lesson menu, between the blocks drawer and a column beside the content.
 *
 * On wide screens they sit beside the content; on narrow screens they stay in the drawer, a tap away.
 * Each block is moved rather than copied, so it exists once on the page.
 *
 * @module     theme_snap/inline_fake_blocks
 * @copyright  2026 PickledKitty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Drawers from 'theme_boost/drawers';

const WIDE = window.matchMedia('(min-width: 992px)');
const DRAWER_ID = 'theme_snap-drawers-blocks';
// Set on the body while the drawer holds nothing but blocks shown beside the content.
const DRAWER_EMPTY_CLASS = 'snap-blocks-drawer-empty';

/**
 * Initialise.
 */
export const init = () => {
    const column = document.getElementById('snap-inline-fake-blocks');
    const drawer = document.getElementById(DRAWER_ID);
    if (!column || !drawer) {
        return;
    }
    // A marker where each block lives in the drawer, so it goes back in the same place.
    const blocks = [...drawer.querySelectorAll('.block_fake')].map(block => {
        const marker = document.createComment('snap-inline-fake-block');
        block.before(marker);
        return {block, marker};
    });
    if (!blocks.length) {
        return;
    }
    const drawerhasotherblocks = !!drawer.querySelector('.block:not(.block_fake), [data-key="addblock"]');

    const place = () => {
        const wide = WIDE.matches;
        blocks.forEach(({block, marker}) => {
            if (wide) {
                column.append(block);
            } else {
                marker.after(block);
            }
        });
        const drawerempty = wide && !drawerhasotherblocks;
        document.body.classList.toggle(DRAWER_EMPTY_CLASS, drawerempty);
        if (drawerempty) {
            // Don't leave an empty drawer open.
            Drawers.getDrawerInstanceForNode(drawer)?.closeDrawer({focusOnOpenButton: false, updatePreferences: false});
        }
    };

    place();
    WIDE.addEventListener('change', place);
};
