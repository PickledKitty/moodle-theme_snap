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

export const init = () => {
    const thead = document.querySelector('table#completion-progress > thead');
    if (!thead) {
        return;
    }
    const header = document.getElementById('mr-nav');
    const observer = new ResizeObserver(() => measure(thead, header));
    observer.observe(thead);
    if (header) {
        observer.observe(header);
    }
};
