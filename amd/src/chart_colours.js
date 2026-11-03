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
 * Chart colours in dark mode.
 *
 * Moodle's charts (Chart.js) draw their labels in mid grey and their grid lines in faint black, made for a light
 * page: too faint to read on a dark one. In dark mode these use the page's text colour and faint white lines, and
 * pie slices are separated by the page's background rather than white. Only pages with a chart load Chart.js for
 * this, and the charts are redrawn if the colour mode changes.
 *
 * @module     theme_snap/chart_colours
 * @copyright  2026 PickledKitty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const CHART = '.chart-area';

let light = null;

/**
 * Whether the page is in dark mode.
 *
 * @return {boolean}
 */
const isDark = () => document.documentElement.dataset.bsTheme === 'dark';

/**
 * Set the colours for the current mode, and redraw the charts already on the page.
 *
 * @param {Object} Chart Chart.js
 */
const apply = Chart => {
    // Chart.js's own colours, kept for light mode.
    light = light || {color: Chart.defaults.color, borderColor: Chart.defaults.borderColor};
    const page = getComputedStyle(document.documentElement);
    const dark = isDark();

    Chart.defaults.color = dark ? page.getPropertyValue('--bs-body-color').trim() : light.color;
    Chart.defaults.borderColor = dark ? 'rgba(255, 255, 255, 0.15)' : light.borderColor;

    Object.values(Chart.instances).forEach(chart => {
        if (['pie', 'doughnut'].includes(chart.config.type)) {
            chart.data.datasets.forEach(dataset => {
                dataset.borderColor = dark ? page.getPropertyValue('--bs-body-bg').trim() : '#fff';
            });
        }
        chart.update('none');
    });
};

export const init = () => {
    if (!document.querySelector(CHART)) {
        return;
    }
    import('core/chartjs').then(Chart => {
        Chart = Chart.default || Chart;
        if (isDark()) {
            apply(Chart);
        }
        new MutationObserver(() => apply(Chart))
            .observe(document.documentElement, {attributes: true, attributeFilter: ['data-bs-theme']});
        return Chart;
    }).catch(() => {
        // No charts can be drawn without Chart.js either.
    });
};
