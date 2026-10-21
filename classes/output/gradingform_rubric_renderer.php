<?php
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
 * Snap rubric renderer.
 *
 * @package   theme_snap
 * @copyright 2026 PickledKitty
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\output;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/grade/grading/form/rubric/renderer.php');

/**
 * Adds classes describing a rubric's shape, so the theme can size its columns.
 *
 * The criteria table gets levels-N (most levels in any criterion), levels-text-* (wordiest level)
 * and description-text-* (wordiest criterion description); each level cell gets text-*.
 * The * is little (under 5 words), mid (5 to 30) or lot (over 30). Core still renders the markup;
 * the classes are added to its output.
 */
class gradingform_rubric_renderer extends \gradingform_rubric_renderer {

    /** @var string Classes for the criteria table of the rubric being displayed, with a leading space. */
    protected $criteriaclasses = '';

    /**
     * Adds a wordiness class to the level cell.
     *
     * @param int $mode rubric display mode
     * @param array $options display options for this rubric
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param string|int $criterionid either id of the nesting criterion or a macro for template
     * @param array|null $level level data, also in view mode it might also have property $level['checked']
     * @return string
     */
    public function level_template($mode, $options, $elementname = '{NAME}', $criterionid = '{CRITERION-id}', $level = null) {
        $html = parent::level_template($mode, $options, $elementname, $criterionid, $level);
        if (!isset($level['definition'])) {
            return $html;
        }
        $class = 'text-' . $this->wordiness($level['definition']);
        return preg_replace('/class="text-break level/', 'class="text-break level ' . $class, $html, 1);
    }

    /**
     * Works out the rubric's size and wordiness, then displays it.
     *
     * @param array $criteria data about the rubric design
     * @param array $options display options for this rubric
     * @param int $mode rubric display mode
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param array|null $values evaluation result
     * @return string
     */
    public function display_rubric($criteria, $options, $mode, $elementname = null, $values = null) {
        $ranks = ['little' => 0, 'mid' => 1, 'lot' => 2];
        $maxlevels = 0;
        $levelsrank = 0;
        $descriptionrank = 0;
        foreach ($criteria as $criterion) {
            $descriptionrank = max($descriptionrank, $ranks[$this->wordiness($criterion['description'] ?? '')]);
            $levels = $criterion['levels'] ?? [];
            $maxlevels = max($maxlevels, count($levels));
            foreach ($levels as $level) {
                $levelsrank = max($levelsrank, $ranks[$this->wordiness($level['definition'] ?? '')]);
            }
        }
        $names = array_flip($ranks);
        $this->criteriaclasses = ($maxlevels ? " levels-{$maxlevels}" : '') .
            " levels-text-{$names[$levelsrank]} description-text-{$names[$descriptionrank]}";
        try {
            return parent::display_rubric($criteria, $options, $mode, $elementname, $values);
        } finally {
            $this->criteriaclasses = '';
        }
    }

    /**
     * Adds the size and wordiness classes to the criteria table.
     *
     * @param int $mode rubric display mode
     * @param array $options display options for this rubric
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param string $criteriastr evaluated templates for this rubric's criteria
     * @return string
     */
    protected function rubric_template($mode, $options, $elementname, $criteriastr) {
        $html = parent::rubric_template($mode, $options, $elementname, $criteriastr);
        if ($this->criteriaclasses === '') {
            return $html;
        }
        return preg_replace('/class="criteria"/', 'class="criteria' . $this->criteriaclasses . '"', $html, 1);
    }

    /**
     * How wordy a piece of rubric text is.
     *
     * @param string|null $text rubric text, possibly HTML
     * @return string little (under 5 words), mid (5 to 30) or lot (over 30)
     */
    protected function wordiness($text): string {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string) $text, ENT_QUOTES))));
        $words = $plain === '' ? 0 : count(explode(' ', $plain));
        if ($words > 30) {
            return 'lot';
        }
        return $words >= 5 ? 'mid' : 'little';
    }
}
