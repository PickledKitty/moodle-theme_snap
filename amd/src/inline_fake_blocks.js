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
    // Checked live: other scripts can move blocks out of the drawer after this runs, such as Snap
    // moving the Administration block into its admin menu.
    const noblocks = () => WIDE.matches && !drawer.querySelector('.block:not(.block_fake)');
    const drawerempty = () => noblocks() && !drawer.querySelector('[data-key="addblock"]');

    // In edit mode a drawer without blocks still has "Add a block", so it opens only when asked to,
    // with its button. Only a real click counts: Snap's sidebar opens the drawer on some pages by
    // clicking the button from script.
    let asked = false;
    document.addEventListener('click', e => {
        if (e.isTrusted && e.target.closest(`[data-toggler="drawers"][data-target="${DRAWER_ID}"]`)) {
            asked = true;
        }
    }, true);
    const unwanted = () => drawerempty() || (noblocks() && !asked);

    // Don't let anything else open the drawer while it has no blocks, such as the quiz or book forcing
    // it open or a remembered open drawer being restored.
    document.addEventListener(Drawers.eventTypes.drawerShow, e => {
        if (e.target === drawer && unwanted()) {
            e.preventDefault();
        }
    });
    document.addEventListener(Drawers.eventTypes.drawerHidden, e => {
        if (e.target === drawer) {
            asked = false;
        }
    });

    // Hide the drawer button while there's nothing in it, and close the drawer if it opened unasked.
    const update = () => {
        document.body.classList.toggle(DRAWER_EMPTY_CLASS, drawerempty());
        if (unwanted() && drawer.classList.contains('show')) {
            Drawers.getDrawerInstanceForNode(drawer)?.closeDrawer({focusOnOpenButton: false, updatePreferences: false});
        }
    };

    const place = () => {
        const wide = WIDE.matches;
        blocks.forEach(({block, marker}) => {
            if (wide) {
                column.append(block);
            } else {
                marker.after(block);
            }
        });
        update();
    };

    place();
    WIDE.addEventListener('change', place);
    new MutationObserver(update).observe(drawer, {childList: true, subtree: true});
};
