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
 * Close buttons for the side menu panels that don't have one, like the messages drawer's.
 *
 * The site administration and personal feeds panels open and close only with their side menu
 * buttons. Each gets a cross at its top right, which clicks that same button, so the button's
 * highlight, expanded state and saved preference stay in step.
 *
 * @module     theme_snap/panel_close
 * @copyright  2026 PickledKitty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';
import Templates from 'core/templates';
import Notification from 'core/notification';

// The side menu buttons whose panels get a cross. Each names its panel by aria-controls or href.
const TRIGGERS = ['#admin-menu-trigger', '#snap_feeds_side_menu_trigger'];

/**
 * The panel a side menu button opens.
 *
 * @param {HTMLElement} trigger
 * @returns {HTMLElement|null}
 */
const panelFor = trigger => {
    const id = trigger.getAttribute('aria-controls') || (trigger.getAttribute('href') || '').replace(/^#/, '');
    return id ? document.getElementById(id) : null;
};

/**
 * Initialise.
 */
export const init = async() => {
    if (!TRIGGERS.some(selector => document.querySelector(selector))) {
        return;
    }
    try {
        const [label, icon] = await Promise.all([
            getString('closebuttontitle', 'core'),
            Templates.renderPix('i/window_close', 'core'),
        ]);
        TRIGGERS.forEach(selector => {
            const trigger = document.querySelector(selector);
            const panel = trigger && panelFor(trigger);
            if (!panel || panel.querySelector(':scope > .snap-panel-close')) {
                return;
            }
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-link text-body';
            button.title = label;
            button.setAttribute('aria-label', label);
            button.innerHTML = icon;
            button.addEventListener('click', () => trigger.click());
            const row = document.createElement('div');
            row.className = 'snap-panel-close';
            row.append(button);
            panel.prepend(row);
        });
    } catch (error) {
        Notification.exception(error);
    }
};
