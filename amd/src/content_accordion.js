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
 * Accordions in course content: each .accordion-btn opens and closes the element after it.
 *
 * Teachers add the markup in the editor, a button (or any element) followed by its panel. This makes them
 * work with the keyboard and screen readers, closed to start with (or open, with aria-expanded="true"
 * on the button), including in content loaded after the page, such as course sections. The styles only
 * close a panel once its button has been set up here, so the content stays readable without this script.
 *
 * @module     theme_snap/content_accordion
 * @copyright  2026 PickledKitty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const BUTTON = '.accordion-btn';

let count = 0;

/**
 * Set up the accordion buttons in part of the page that aren't set up yet.
 *
 * @param {Element|Document} root where to look
 */
const setUp = root => {
    const buttons = [...root.querySelectorAll(BUTTON)];
    if (root.matches?.(BUTTON)) {
        buttons.push(root);
    }
    buttons.forEach(button => {
        const panel = button.nextElementSibling;
        if (button.dataset.snapAccordion || !panel) {
            return;
        }
        button.dataset.snapAccordion = '1';
        count++;
        if (button.tagName !== 'BUTTON') {
            button.setAttribute('role', 'button');
            button.tabIndex = 0;
        }
        button.id = button.id || `snap-accordion-${count}`;
        panel.id = panel.id || `snap-accordion-panel-${count}`;
        panel.setAttribute('role', 'region');
        panel.setAttribute('aria-labelledby', button.id);
        button.setAttribute('aria-controls', panel.id);
        button.setAttribute('aria-expanded', button.getAttribute('aria-expanded') === 'true' ? 'true' : 'false');
    });
};

/**
 * Open or close the panel of a button.
 *
 * @param {Element} button the accordion button
 */
const toggle = button => {
    button.setAttribute('aria-expanded', button.getAttribute('aria-expanded') === 'true' ? 'false' : 'true');
};

export const init = () => {
    setUp(document);

    document.addEventListener('click', e => {
        const button = e.target.closest(`${BUTTON}[data-snap-accordion]`);
        if (button) {
            e.preventDefault();
            toggle(button);
        }
    });
    // Buttons made from other elements: Enter and Space, as a native button.
    document.addEventListener('keydown', e => {
        const button = e.target.closest(`${BUTTON}[data-snap-accordion][role="button"]`);
        if (button && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            toggle(button);
        }
    });

    // Content added later, such as course sections.
    new MutationObserver(mutations => mutations.forEach(mutation => mutation.addedNodes.forEach(node => {
        if (node.nodeType === Node.ELEMENT_NODE) {
            setUp(node);
        }
    }))).observe(document.body, {childList: true, subtree: true});
};
