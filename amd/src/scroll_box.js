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
 * The scrollbar is a visual aid only: the box itself scrolls by swiping and from the keyboard. On every
 * device, keyboard focus is kept clear of the report's pinned rows and column.
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
 * The area the elements matching a selector in the report cover on screen.
 *
 * @param {HTMLElement} report the report
 * @param {string} selector the elements, or '' for none
 * @returns {{top: number, bottom: number, left: number, right: number}|null} their area, or null for none
 */
const area = (report, selector) => {
    const rects = selector === '' ? [] : [...report.querySelectorAll(selector)].map(el => el.getBoundingClientRect());
    return rects.length === 0 ? null : {
        top: Math.min(...rects.map(r => r.top)),
        bottom: Math.max(...rects.map(r => r.bottom)),
        left: Math.min(...rects.map(r => r.left)),
        right: Math.max(...rects.map(r => r.right)),
    };
};

/**
 * Scroll whatever scrolls an element along one axis: the report's box, Snap's content area or the page.
 *
 * @param {HTMLElement} el the element
 * @param {string} axis 'x' or 'y'
 * @param {number} by how far, in pixels
 */
const scrollAlong = (el, axis, by) => {
    for (let up = el.parentElement; up && up !== document.body; up = up.parentElement) {
        const style = getComputedStyle(up);
        const overflow = axis === 'x' ? style.overflowX : style.overflowY;
        const room = axis === 'x' ? up.scrollWidth > up.clientWidth : up.scrollHeight > up.clientHeight;
        if (room && (overflow === 'auto' || overflow === 'scroll')) {
            up.scrollBy(axis === 'x' ? by : 0, axis === 'y' ? by : 0);
            return;
        }
    }
    window.scrollBy(axis === 'x' ? by : 0, axis === 'y' ? by : 0);
};

/**
 * Keep keyboard focus clear of a report's pinned rows and column.
 *
 * Browsers scroll a focused control into view, but count one behind a pinned row or column as in view
 * already: moving back along a row with Shift+Tab, a cell can land under the pinned names. So once the
 * browser has scrolled, move a focused control that's still partly behind them into the clear. Pinned
 * controls are always in view. On the document, as focus events go no further down on some pages.
 *
 * @param {HTMLElement} report the report
 * @param {string} pinnedtop the rows pinned to the top
 * @param {string} pinnedbottom the rows pinned to the bottom
 * @param {string} pinnedleft the heading of the column pinned to the left
 */
const keepFocusClear = (report, pinnedtop, pinnedbottom, pinnedleft) => {
    const reveal = el => {
        for (let up = el; up !== report; up = up.parentElement) {
            if (getComputedStyle(up).position === 'sticky') {
                return;
            }
        }
        const target = el.getBoundingClientRect();
        const left = area(report, pinnedleft);
        if (left && target.left < left.right && target.right > left.left) {
            scrollAlong(el, 'x', target.left - left.right);
        }
        const top = area(report, pinnedtop);
        const bottom = area(report, pinnedbottom);
        if (top && target.top < top.bottom && target.bottom > top.top) {
            scrollAlong(el, 'y', target.top - top.bottom);
        } else if (bottom && target.bottom > bottom.top && target.top < bottom.bottom) {
            scrollAlong(el, 'y', target.bottom - bottom.top);
        }
    };
    document.addEventListener('focusin', e => {
        if (report.contains(e.target)) {
            requestAnimationFrame(() => reveal(e.target));
        }
    }, true);
};

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
        // Keyboard focus moving through the box scrolls the focused cell clear of the pinned rows and column,
        // where browsers keep to it.
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
 * Keep keyboard focus clear of a report's pinned rows and column, and on tablets and phones add the
 * scrollbar to its box.
 *
 * @param {string} selector the report's scrolling box
 * @param {string} pinnedtop the rows pinned to the top of the box once scrolled
 * @param {string} pinnedbottom the rows pinned to the bottom of the box, if any
 * @param {string} pinnedleft the heading of the column pinned to the left of the box, if any
 */
export const init = (selector, pinnedtop, pinnedbottom = '', pinnedleft = '') => {
    const box = document.querySelector(selector);
    if (!box) {
        return;
    }
    keepFocusClear(box, pinnedtop, pinnedbottom, pinnedleft);
    if (!document.body.matches('.device-type-tablet, .device-type-mobile')) {
        return;
    }
    const update = addScrollbar(box, pinnedtop, pinnedbottom, pinnedleft);
    const observer = new ResizeObserver(update);
    observer.observe(box);
    observer.observe(box.firstElementChild);
};
