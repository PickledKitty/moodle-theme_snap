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
 * A scrollbar that's always shown, for a wide report that scrolls in a box of its own on tablets and phones.
 *
 * Tablets and phones widen the whole layout when a page scrolls sideways, pushing Snap's header and sidebar
 * out of view, so there the styles give the report a box of its own. Those browsers hide scrollbars, so this
 * wraps the box with one that's always shown, beside the rows between the pinned headings and any pinned row
 * at the bottom, and marks the wrapper while there's more to see below or to the right, for fades along
 * those edges. The styles show the scrollbar only where the box is used.
 *
 * The scrollbar is a visual aid only: the box itself scrolls by swiping and from the keyboard.
 *
 * @module     theme_snap/scroll_box
 * @copyright  2026 PickledKitty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The total height of the elements matching a selector in the box.
 *
 * @param {HTMLElement} box the scrolling box
 * @param {string} selector the elements, or '' for none
 * @returns {number} their height in pixels
 */
const height = (box, selector) => selector === '' ? 0 :
    [...box.querySelectorAll(selector)].reduce((sum, el) => sum + el.getBoundingClientRect().height, 0);

/**
 * Wrap the box with the scrollbar.
 *
 * @param {HTMLElement} box the scrolling box
 * @param {string} pinnedtop the rows pinned to the top of the box once scrolled
 * @param {string} pinnedbottom the rows pinned to the bottom of the box
 * @param {string} pinnedleft the heading of the column pinned to the left of the box
 * @returns {Function} updates the scrollbar and the marks
 */
const addScrollbar = (box, pinnedtop, pinnedbottom, pinnedleft) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'snap-scroll-box';
    const track = document.createElement('div');
    track.className = 'snap-scroll-box-bar';
    track.setAttribute('aria-hidden', 'true');
    const thumb = document.createElement('div');
    thumb.className = 'snap-scroll-box-thumb';
    track.append(thumb);
    box.before(wrapper);
    wrapper.append(box, track);

    const update = () => {
        const top = box.clientTop + height(box, pinnedtop);
        const bottom = height(box, pinnedbottom);
        const rows = box.clientHeight - top - bottom;
        const range = box.scrollHeight - box.clientHeight;
        const scrollbar = box.offsetHeight - box.clientTop * 2 - box.clientHeight;
        wrapper.style.setProperty('--snap-scroll-box-scrollbar-height', `${scrollbar}px`);
        wrapper.style.setProperty('--snap-scroll-box-pinned-bottom', `${bottom}px`);
        // Keyboard focus moving through the box scrolls the focused cell clear of the pinned rows and column.
        box.style.scrollPaddingTop = `${top}px`;
        box.style.scrollPaddingBottom = `${bottom}px`;
        box.style.scrollPaddingLeft = `${pinnedleft === '' ? 0 : box.querySelector(pinnedleft)?.offsetWidth ?? 0}px`;
        track.style.top = `${top}px`;
        track.style.height = `${rows}px`;
        const thumbheight = range > 0 ? Math.max(32, rows * rows / (rows + range)) : rows;
        thumb.style.height = `${thumbheight}px`;
        thumb.style.transform = `translateY(${range > 0 ? (rows - thumbheight) * box.scrollTop / range : 0}px)`;
        wrapper.classList.toggle('snap-scroll-box-more-below', box.scrollTop < range - 1);
        wrapper.classList.toggle('snap-scroll-box-more-right', box.scrollLeft < box.scrollWidth - box.clientWidth - 1);
    };

    // Browsers don't always keep to the scroll padding when focus moves, so move a focused control that's
    // still partly behind the pinned rows or column into the clear, once the browser has scrolled to it.
    // Pinned controls are always in view.
    const reveal = el => {
        for (let up = el; up !== box; up = up.parentElement) {
            if (getComputedStyle(up).position === 'sticky') {
                return;
            }
        }
        const target = el.getBoundingClientRect();
        const area = box.getBoundingClientRect();
        const style = box.style;
        const left = area.left + box.clientLeft + parseFloat(style.scrollPaddingLeft);
        const top = area.top + parseFloat(style.scrollPaddingTop);
        const bottom = area.top + box.clientTop + box.clientHeight - parseFloat(style.scrollPaddingBottom);
        if (target.left < left) {
            box.scrollLeft -= left - target.left;
        }
        if (target.top < top) {
            box.scrollTop -= top - target.top;
        } else if (target.bottom > bottom) {
            box.scrollTop += target.bottom - bottom;
        }
    };
    // On the document, as focus events go no further down on some pages.
    document.addEventListener('focusin', e => {
        if (box.contains(e.target)) {
            requestAnimationFrame(() => reveal(e.target));
        }
    }, true);

    // Scroll the box to put the thumb's top at a point on the track.
    const scrollTo = thumbtop => {
        const free = track.clientHeight - thumb.offsetHeight;
        const range = box.scrollHeight - box.clientHeight;
        box.scrollTop = free > 0 ? Math.min(Math.max(thumbtop / free, 0), 1) * range : 0;
    };
    let grab = null;
    track.addEventListener('pointerdown', e => {
        e.preventDefault();
        const trackTop = track.getBoundingClientRect().top;
        if (e.target === thumb) {
            grab = e.clientY - thumb.getBoundingClientRect().top;
        } else {
            // A tap on the track centres the thumb there, and then it can be dragged on.
            grab = thumb.offsetHeight / 2;
            scrollTo(e.clientY - trackTop - grab);
        }
        track.setPointerCapture(e.pointerId);
    });
    track.addEventListener('pointermove', e => {
        if (grab !== null) {
            scrollTo(e.clientY - track.getBoundingClientRect().top - grab);
        }
    });
    const release = () => {
        grab = null;
    };
    track.addEventListener('pointerup', release);
    track.addEventListener('pointercancel', release);

    box.addEventListener('scroll', update, {passive: true});
    return update;
};

/**
 * Add the scrollbar to a report's box, on tablets and phones.
 *
 * @param {string} selector the report's scrolling box
 * @param {string} pinnedtop the rows pinned to the top of the box once scrolled
 * @param {string} pinnedbottom the rows pinned to the bottom of the box, if any
 * @param {string} pinnedleft the heading of the column pinned to the left of the box, if any
 */
export const init = (selector, pinnedtop, pinnedbottom = '', pinnedleft = '') => {
    const box = document.querySelector(selector);
    if (!box || !document.body.matches('.device-type-tablet, .device-type-mobile')) {
        return;
    }
    const update = addScrollbar(box, pinnedtop, pinnedbottom, pinnedleft);
    const observer = new ResizeObserver(update);
    observer.observe(box);
    observer.observe(box.firstElementChild);
};
