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
 * Quiz results reports as a table or as cards, with each student's attempts banded together.
 *
 * The chosen view is saved as a user preference, and the server adds its body class while building
 * the page, so the layout doesn't change after loading. Each cell is labelled with its column heading
 * for the cards view, and in both views rows alternate between two band classes each time the
 * student changes.
 *
 * @module     theme_snap/quiz_report_view
 * @copyright  2026 PickledKitty
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getStrings} from 'core/str';
import {setUserPreference} from 'core_user/repository';
import Notification from 'core/notification';

const PREFERENCE = 'theme_snap_quizreport_view';
const CARDS_CLASS = 'snap-quizreport-cards';

/**
 * The kind of a report column, from its internal name (the same in every language).
 *
 * @param {HTMLTableCellElement} th the column heading
 * @returns {string} mark, response, time, hidden (picture and email) or summary (the rest)
 */
const columnKind = th => {
    const name = th.querySelector('[data-sortby]')?.dataset.sortby ?? '';
    if (/^qsgrade\d+$/.test(name)) {
        return 'mark';
    }
    if (/^(question|response|right)\d+$/.test(name)) {
        return 'response';
    }
    if (['timestart', 'timefinish', 'duration'].includes(name)) {
        return 'time';
    }
    return ['picture', 'email'].includes(name) || th.classList.contains('picture') ? 'hidden' : 'summary';
};

/**
 * Label each cell with its column heading and kind, for the cards view.
 *
 * Marks get the heading without its maximum ("Q. 1" from "Q. 1/2.50") to keep them compact.
 *
 * @param {HTMLTableElement} table
 */
const labelCells = table => {
    const columns = [...table.querySelectorAll('thead th')].map(th => {
        // Leave out the hide/show column controls and screen reader text.
        const heading = th.cloneNode(true);
        heading.querySelectorAll('.commands, .accesshide, .visually-hidden, .sr-only').forEach(el => el.remove());
        const label = heading.textContent.replace(/\s+/g, ' ').trim();
        const kind = columnKind(th);
        const name = th.querySelector('[data-sortby]')?.dataset.sortby;
        const classes = ['snap-quizreport-' + kind, ...(name ? ['snap-quizreport-col-' + name] : [])];
        th.classList.add(...classes);
        return {label: kind === 'mark' ? label.split('/')[0].trim() : label, classes};
    });
    table.querySelectorAll('tbody tr').forEach(row => {
        [...row.children].forEach((cell, i) => {
            if (columns[i]?.label) {
                cell.dataset.label = columns[i].label;
            }
            if (columns[i]) {
                cell.classList.add(...columns[i].classes);
            }
            // Later attempts by the same student leave the student's details blank.
            if (!cell.textContent.trim() && !cell.querySelector('img, input, .icon')) {
                cell.classList.add('snap-quizreport-emptycell');
            }
        });
    });
    // In cards, marks and responses start on the line under the band. The break cell is hidden in the table.
    table.querySelectorAll('tr').forEach(row => {
        const first = row.querySelector('.snap-quizreport-mark, .snap-quizreport-response');
        if (first) {
            const linebreak = document.createElement(first.tagName);
            linebreak.className = 'snap-quizreport-break';
            linebreak.setAttribute('aria-hidden', 'true');
            first.before(linebreak);
        }
    });
};

/**
 * Band rows by student, and mark the rows that aren't attempts.
 *
 * @param {HTMLTableElement} table
 */
const bandRows = table => {
    let lastuser = null;
    let band = 'b';
    table.querySelectorAll('tbody tr:not(.emptyrow)').forEach(row => {
        const link = row.querySelector('a[href*="/user/view.php"], a[href*="/user/profile.php"]');
        if (!link) {
            const blank = [...row.children].every(cell => !cell.textContent.trim());
            row.classList.add(blank ? 'snap-quizreport-blankrow' : 'snap-quizreport-summaryrow');
            return;
        }
        const user = new URL(link.href).searchParams.get('id');
        if (user !== lastuser) {
            band = band === 'a' ? 'b' : 'a';
            lastuser = user;
        }
        row.classList.add('snap-attempts-band-' + band);
    });
};

/**
 * Add the Table / Cards buttons above the report.
 *
 * @param {HTMLTableElement} table
 */
const addToggle = async table => {
    const [label, tabletext, cardstext] = await getStrings([
        {key: 'quizreportview', component: 'theme_snap'},
        {key: 'quizreportviewtable', component: 'theme_snap'},
        {key: 'quizreportviewcards', component: 'theme_snap'},
    ]);
    const group = document.createElement('div');
    group.className = 'btn-group snap-quizreport-view';
    group.setAttribute('role', 'group');
    group.setAttribute('aria-label', label);
    [['table', tabletext], ['cards', cardstext]].forEach(([view, text]) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary';
        button.dataset.view = view;
        button.textContent = text;
        group.append(button);
    });

    const showpressed = () => {
        const cards = document.body.classList.contains(CARDS_CLASS);
        group.querySelectorAll('button').forEach(button => {
            const pressed = (button.dataset.view === 'cards') === cards;
            button.classList.toggle('active', pressed);
            button.setAttribute('aria-pressed', String(pressed));
        });
    };

    group.addEventListener('click', e => {
        const button = e.target.closest('button[data-view]');
        if (!button) {
            return;
        }
        document.body.classList.toggle(CARDS_CLASS, button.dataset.view === 'cards');
        showpressed();
        setUserPreference(PREFERENCE, button.dataset.view).catch(Notification.exception);
    });

    showpressed();
    (table.closest('.table-responsive') || table).before(group);
};

/**
 * Initialise.
 */
export const init = () => {
    const table = document.querySelector('table#attempts, table#responses');
    if (!table) {
        return;
    }
    labelCells(table);
    bandRows(table);
    addToggle(table);
};
