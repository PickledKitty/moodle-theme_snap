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
 * Completion reports: the column headings stay in view when scrolling down the page.
 *
 * The heading block pins as a whole, below Snap's header. Only its last two rows (the column names and
 * the icons) stay in view: the course completion report's criteria group and aggregation method rows
 * above them scroll away. The styles do the pinning; this measures the rows and the header for them.
 * On tablets and phones, where the table scrolls in a box of its own, this also adds a scrollbar that's
 * always shown.
 *
 * @module     theme_snap/report_sticky_headings
 * @copyright  2026 PickledKitty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const PINNED_ROWS = 2;

/**
 * Set the heights the styles need as custom properties on the page.
 *
 * @param {HTMLTableSectionElement} thead the report's heading rows
 * @param {HTMLElement|null} header Snap's header
 */
const measure = (thead, header) => {
    const rows = [...thead.rows];
    const split = Math.max(0, rows.length - PINNED_ROWS);
    const height = list => list.reduce((sum, row) => sum + row.getBoundingClientRect().height, 0);
    const root = document.documentElement.style;
    root.setProperty('--snap-report-header-height', `${header ? header.offsetHeight : 0}px`);
    root.setProperty('--snap-report-thead-offset', `${height(rows.slice(0, split))}px`);
    root.setProperty('--snap-report-thead-pinned', `${height(rows.slice(split))}px`);
};

/**
 * On tablets and phones the table scrolls in a box of its own, where those browsers hide the scrollbar.
 * Wrap it with a scrollbar that's always shown, beside the rows below the pinned headings, and mark the
 * wrapper while there's more to see below or to the right, for the fades along those edges.
 *
 * The scrollbar is a visual aid only: the table itself scrolls by swiping and from the keyboard.
 *
 * @param {HTMLTableElement} table the report
 * @returns {Function} updates the scrollbar and the marks
 */
const addScrollbar = table => {
    const wrapper = document.createElement('div');
    wrapper.className = 'snap-report-scroll';
    const track = document.createElement('div');
    track.className = 'snap-report-scrollbar';
    track.setAttribute('aria-hidden', 'true');
    const thumb = document.createElement('div');
    thumb.className = 'snap-report-scrollbar-thumb';
    track.append(thumb);
    table.before(wrapper);
    wrapper.append(table, track);

    const update = () => {
        // Beside the rows, below where the pinned headings sit once scrolled.
        const pinned = table.clientTop + [...table.tHead.rows].slice(-PINNED_ROWS)
            .reduce((sum, row) => sum + row.getBoundingClientRect().height, 0);
        const rows = table.clientHeight - pinned;
        const range = table.scrollHeight - table.clientHeight;
        const scrollbar = table.offsetHeight - table.clientTop * 2 - table.clientHeight;
        wrapper.style.setProperty('--snap-report-scrollbar-height', `${scrollbar}px`);
        track.style.top = `${pinned}px`;
        track.style.height = `${rows}px`;
        const thumbheight = range > 0 ? Math.max(32, rows * rows / (rows + range)) : rows;
        thumb.style.height = `${thumbheight}px`;
        thumb.style.transform = `translateY(${range > 0 ? (rows - thumbheight) * table.scrollTop / range : 0}px)`;
        wrapper.classList.toggle('snap-report-more-below', table.scrollTop < range - 1);
        wrapper.classList.toggle('snap-report-more-right',
            table.scrollLeft < table.scrollWidth - table.clientWidth - 1);
    };

    // Scroll the table to put the thumb's top at a point on the track.
    const scrollTo = thumbtop => {
        const free = track.clientHeight - thumb.offsetHeight;
        const range = table.scrollHeight - table.clientHeight;
        table.scrollTop = free > 0 ? Math.min(Math.max(thumbtop / free, 0), 1) * range : 0;
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

    table.addEventListener('scroll', update, {passive: true});
    return update;
};

export const init = () => {
    const table = document.querySelector('table#completion-progress');
    if (!table?.tHead) {
        return;
    }
    const thead = table.tHead;
    const header = document.getElementById('mr-nav');
    const touch = document.body.matches('.device-type-tablet, .device-type-mobile');
    const update = touch ? addScrollbar(table) : () => null;
    const observer = new ResizeObserver(() => {
        measure(thead, header);
        update();
    });
    observer.observe(thead);
    observer.observe(table);
    if (header) {
        observer.observe(header);
    }
};
