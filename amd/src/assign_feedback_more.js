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
 * Assignment page: text beside the plus that shows the rest of a student's feedback comments.
 *
 * Moodle shortens long feedback comments and shows the rest from a plus icon, which students miss. The text
 * goes in the link, so it works as part of it, and becomes its name for screen readers too. It goes when the
 * feedback is opened, with the shortened version.
 *
 * @module     theme_snap/assign_feedback_more
 * @copyright  2026 PickledKitty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

export const init = async() => {
    const links = document.querySelectorAll('[class*="summary_assignfeedback_comments"] a.expandsummaryicon');
    if (links.length === 0) {
        return;
    }
    const text = await getString('assignfeedbackmore', 'theme_snap');
    links.forEach(link => {
        const label = document.createElement('span');
        label.className = 'snap-assign-feedback-more';
        label.textContent = text;
        link.prepend(label);
        link.setAttribute('aria-label', text);
    });
};
