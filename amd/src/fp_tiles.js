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
 * The front page's course tiles (theme_snap/course_tiles): starring courses, and keeping the open tab in the address.
 *
 * A starred course keeps its tile where it is and gets a copy in Favourites. Unstarring removes the copy; when that
 * copy had focus, focus goes to the next tile's star in Favourites, or to the Favourites tab.
 *
 * A tile's details open on hover, when View course has focus (CSS), or with its Details button. Escape closes them,
 * and keeps hover from opening them again until the pointer leaves the tile.
 *
 * @module     theme_snap/fp_tiles
 * @copyright  2026 PickledKitty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import {getString} from 'core/str';
import Notification from 'core/notification';
import Pending from 'core/pending';
import {Tab} from 'bootstrap';

const SELECTORS = {
    root: '[data-region="snap-fp-tiles"]',
    star: '[data-action="favourite"]',
    tile: '.snap-fp-tile',
    details: '[data-action="details"]',
    favPane: '#snap-fp-tiles-fav',
    favTab: '#snap-fp-tab-fav',
};

/**
 * The tiles' title, for messages.
 *
 * @param {HTMLElement} tile
 * @returns {string}
 */
const tileTitle = tile => tile.querySelector('.snap-fp-tile-title').textContent.trim();

/**
 * Show or hide the Favourites tab, its empty text and the hint, from whether it has tiles.
 *
 * @param {HTMLElement} root
 */
const refreshFavourites = root => {
    const pane = root.querySelector(SELECTORS.favPane);
    const tab = root.querySelector(SELECTORS.favTab);
    const hasTiles = !!pane.querySelector(SELECTORS.tile);
    const empty = pane.querySelector('[data-region="empty"]');
    if (hasTiles) {
        // Hidden tabs are also disabled, so Bootstrap's arrow keys skip them too.
        tab.hidden = false;
        tab.disabled = false;
    }
    if (empty) {
        empty.hidden = hasTiles;
    }
    root.querySelector('[data-region="hint"]').hidden = hasTiles;
};

/**
 * Open or close a tile's details.
 *
 * @param {HTMLElement} tile
 * @param {boolean} open
 */
const setOpen = (tile, open) => {
    tile.classList.toggle('snap-fp-tile-open', open);
    tile.querySelector(SELECTORS.details).setAttribute('aria-expanded', open ? 'true' : 'false');
};

/**
 * Escape closes the tile's details: the ones opened with Details, by focus or by hover. Focus in them goes to the
 * Details button, and hover won't open them again until the pointer has left the tile.
 *
 * @param {HTMLElement} root
 * @param {KeyboardEvent} e
 */
const closeOnEscape = (root, e) => {
    if (e.key !== 'Escape') {
        return;
    }
    const tile = (e.target.closest && e.target.closest(`${SELECTORS.root} ${SELECTORS.tile}`))
        || root.querySelector(`${SELECTORS.tile}:hover`);
    if (!tile) {
        return;
    }
    const details = tile.querySelector('[data-region="details"]');
    setOpen(tile, false);
    tile.classList.add('snap-fp-tile-dismissed');
    if (details.contains(document.activeElement)) {
        tile.querySelector(SELECTORS.details).focus();
    }
};

/**
 * Details that don't fit their tile scroll, so they take keyboard focus then (arrow keys scroll them); details that
 * fit don't, so they add no Tab stop.
 *
 * @param {HTMLElement} root
 */
const focusableWhenScrolling = root => {
    root.querySelectorAll('[data-region="details"]').forEach(details => {
        if (details.scrollHeight > details.clientHeight + 1) {
            details.tabIndex = 0;
        } else {
            details.removeAttribute('tabindex');
        }
    });
};

/**
 * A copy of a tile for Favourites, with its ids made unique for that tab.
 *
 * @param {HTMLElement} tile
 * @returns {HTMLElement} the list item
 */
const favouriteCopy = tile => {
    const copy = tile.cloneNode(true);
    // Copied while its star is saving: the copy's star isn't.
    delete copy.querySelector(SELECTORS.star).dataset.busy;
    const title = copy.querySelector('.snap-fp-tile-title');
    title.id = `snap-fp-tile-fav-${copy.dataset.courseid}`;
    copy.setAttribute('aria-labelledby', title.id);
    copy.querySelector('[data-region="details"]').id = `snap-fp-tile-details-fav-${copy.dataset.courseid}`;
    copy.querySelector(SELECTORS.details).setAttribute('aria-controls', `snap-fp-tile-details-fav-${copy.dataset.courseid}`);
    copy.classList.remove('snap-fp-tile-dismissed');
    setOpen(copy, false);
    const item = document.createElement('li');
    item.append(copy);
    return item;
};

/**
 * Put a tile in Favourites, in name order.
 *
 * @param {HTMLElement} root
 * @param {HTMLElement} tile
 */
const addToFavourites = (root, tile) => {
    const list = root.querySelector(`${SELECTORS.favPane} [data-region="tiles"]`);
    if (list.querySelector(`${SELECTORS.tile}[data-courseid="${tile.dataset.courseid}"]`)) {
        return;
    }
    const title = tileTitle(tile).toLocaleLowerCase();
    const after = [...list.children].find(item => tileTitle(item.querySelector(SELECTORS.tile)).toLocaleLowerCase() > title);
    list.insertBefore(favouriteCopy(tile), after || null);
    refreshFavourites(root);
    focusableWhenScrolling(root);
};

/**
 * Take a course out of Favourites. If its tile there had focus, focus moves to the next tile's star, or the tab.
 *
 * @param {HTMLElement} root
 * @param {string} courseid
 */
const removeFromFavourites = (root, courseid) => {
    const tile = root.querySelector(`${SELECTORS.favPane} ${SELECTORS.tile}[data-courseid="${courseid}"]`);
    if (!tile) {
        return;
    }
    const item = tile.closest('li');
    const hadFocus = tile.contains(document.activeElement);
    const next = item.nextElementSibling || item.previousElementSibling;
    item.remove();
    refreshFavourites(root);
    if (hadFocus) {
        (next ? next.querySelector(SELECTORS.star) : root.querySelector(SELECTORS.favTab)).focus();
    }
};

/**
 * Star or unstar a course: save it, then update its tiles and say what happened.
 *
 * @param {HTMLElement} root
 * @param {HTMLButtonElement} button
 */
const toggleFavourite = async(root, button) => {
    if (button.dataset.busy) {
        return;
    }
    const pending = new Pending('theme_snap/fp_tiles:favourite');
    button.dataset.busy = '1';
    const courseid = button.dataset.courseid;
    const favourite = button.getAttribute('aria-pressed') !== 'true';
    const tile = button.closest(SELECTORS.tile);
    try {
        const [result] = await Promise.all(fetchMany([{
            methodname: 'core_course_set_favourite_courses',
            args: {courses: [{id: courseid, favourite}]},
        }]));
        if (result.warnings && result.warnings.length) {
            throw new Error(result.warnings[0].message);
        }
        root.querySelectorAll(`${SELECTORS.star}[data-courseid="${courseid}"]`)
            .forEach(star => star.setAttribute('aria-pressed', favourite ? 'true' : 'false'));
        const title = tileTitle(tile);
        if (favourite) {
            addToFavourites(root, tile);
        } else {
            removeFromFavourites(root, courseid);
        }
        root.querySelector('[data-region="status"]').textContent =
            await getString(favourite ? 'fptilesadded' : 'fptilesremoved', 'theme_snap', title);
    } catch (error) {
        Notification.exception(error);
    } finally {
        delete button.dataset.busy;
        pending.resolve();
    }
};

/**
 * Open the tab named in the address (#snap-fp-tab-<id>), and keep the address up to date as tabs are opened, so
 * reloading or coming back keeps the same tab.
 *
 * @param {HTMLElement} root
 */
const rememberTab = root => {
    const id = window.location.hash.slice(1);
    const fromHash = id.startsWith('snap-fp-tab-') && root.querySelector(`#${CSS.escape(id)}`);
    if (fromHash && !fromHash.hidden) {
        Tab.getOrCreateInstance(fromHash).show();
    }
    root.addEventListener('shown.bs.tab', e => {
        window.history.replaceState(null, '', `#${e.target.id}`);
    });
};

/**
 * Set up the course tiles on the page.
 */
export const init = () => {
    const root = document.querySelector(SELECTORS.root);
    if (!root || root.dataset.initialised) {
        return;
    }
    root.dataset.initialised = '1';
    const favTab = root.querySelector(SELECTORS.favTab);
    if (favTab && favTab.hidden) {
        favTab.disabled = true;
    }
    root.addEventListener('click', e => {
        const button = e.target.closest(SELECTORS.star);
        if (button) {
            e.preventDefault();
            toggleFavourite(root, button);
            return;
        }
        const toggle = e.target.closest(SELECTORS.details);
        if (toggle) {
            const tile = toggle.closest(SELECTORS.tile);
            tile.classList.remove('snap-fp-tile-dismissed');
            setOpen(tile, toggle.getAttribute('aria-expanded') !== 'true');
        }
    });
    // Escape works wherever focus is, since a hovered tile may not have it.
    document.addEventListener('keydown', e => closeOnEscape(root, e));
    root.addEventListener('mouseout', e => {
        const tile = e.target.closest(SELECTORS.tile);
        if (tile && !tile.contains(e.relatedTarget)) {
            tile.classList.remove('snap-fp-tile-dismissed');
        }
    });
    rememberTab(root);
    // Whether details scroll changes with the width, and with the open tab (hidden tabs measure nothing).
    focusableWhenScrolling(root);
    new ResizeObserver(() => focusableWhenScrolling(root)).observe(root);
    root.addEventListener('shown.bs.tab', () => focusableWhenScrolling(root));
};
