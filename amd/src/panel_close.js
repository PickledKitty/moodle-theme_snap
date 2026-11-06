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
 * The header bar of the site administration and personal feeds panels: the panel's name and its close button, the
 * same as on the other right-hand panels (the messages drawer, the blocks drawer and, on phones, the course index).
 *
 * These two panels open and close only with their side menu buttons, so the close button clicks that same button,
 * and the button's highlight, expanded state, saved preference and focus return stay in step.
 *
 * @module     theme_snap/panel_close
 * @copyright  2026 PickledKitty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getStrings} from 'core/str';
import Templates from 'core/templates';
import Notification from 'core/notification';

// The side menu buttons whose panels get the header, each with how to find the panel's name: the administration
// block's own heading, or a string.
const PANELS = [
    {trigger: '#admin-menu-trigger', title: panel => {
        const heading = document.getElementById(panel.getAttribute('aria-labelledby'));
        return heading ? heading.textContent.trim() : null;
    }},
    {trigger: '#snap_feeds_side_menu_trigger', title: (panel, strings) => strings.feeds},
];

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
 * Whether a side menu button's panel is open.
 *
 * @param {HTMLElement} trigger
 * @param {HTMLElement} panel
 * @returns {boolean}
 */
const isOpen = (trigger, panel) => trigger.getAttribute('aria-expanded') === 'true'
    || panel.classList.contains('state-visible');

/**
 * Initialise.
 */
export const init = async() => {
    if (!PANELS.some(({trigger}) => document.querySelector(trigger))) {
        return;
    }
    try {
        const [[close, feeds], icon] = await Promise.all([
            getStrings([
                {key: 'closebuttontitle', component: 'core'},
                {key: 'snapfeedsblocktitle', component: 'theme_snap'},
            ]),
            Templates.renderPix('e/cancel', 'core'),
        ]);
        PANELS.forEach(({trigger: selector, title}) => {
            const trigger = document.querySelector(selector);
            const panel = trigger && panelFor(trigger);
            if (!panel || panel.querySelector(':scope > .snap-panel-header')) {
                return;
            }
            const header = document.createElement('div');
            header.className = 'snap-panel-header';

            const name = title(panel, {feeds});
            if (name) {
                const heading = document.createElement('h2');
                heading.className = 'snap-panel-title';
                heading.id = panel.id + '-panel-title';
                heading.textContent = name;
                header.append(heading);
                // This is now the panel's heading: the administration block's own one, shown only to screen
                // readers, goes, and the block's landmark is named by this one.
                const old = document.getElementById(panel.getAttribute('aria-labelledby'));
                if (old) {
                    old.setAttribute('aria-hidden', 'true');
                    panel.setAttribute('aria-labelledby', heading.id);
                }
            }

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn snap-panel-close-button';
            button.title = close;
            button.setAttribute('aria-label', close);
            button.innerHTML = icon;
            // Close with the side menu button, and put focus back on it (theme_snap/sidebar_menu would otherwise
            // stop focus going to it).
            button.addEventListener('click', () => {
                trigger.click();
                document.dispatchEvent(new CustomEvent('theme_snap/sidebar_menu:allowfocusreturn'));
                trigger.focus();
            });
            header.append(button);

            panel.prepend(header);

            // When the panel opens, focus goes to its close button, at the top of it, as on the blocks and messages
            // panels, so the panel is next for keyboard users wherever it sits in the page.
            trigger.addEventListener('click', () => setTimeout(() => {
                if (isOpen(trigger, panel)) {
                    button.focus();
                }
            }, 100));
        });
    } catch (error) {
        Notification.exception(error);
    }
};
