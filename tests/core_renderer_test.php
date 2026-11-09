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
 * Test snap core renderer page heading
 * @author    Juan Ibarra
 * @copyright Copyright (c) 2021 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_snap;

/**
 * Class theme_snap_core_renderer_testcase
 * @author    Juan Ibarra
 * @copyright Copyright (c) 2021 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversMethod(\theme_snap\output\core_renderer::class, 'snap_blocks_drawer_contents')]
class core_renderer_test extends \advanced_testcase {

    /**
     * Test heading should show only h1 tags on site level.
     */
    public function test_default_heading_on_standard_pagelayout_sitelevel() {
        global $COURSE, $PAGE;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $COURSE = $this->getDataGenerator()->create_course();
        $COURSE->id = SITEID; // Simulate site level.

        $PAGE = new \moodle_page();
        $PAGE->set_url('/user/view.php', array('id' => $user->id, 'course' => $COURSE->id));
        $PAGE->set_context(\context_user::instance($user->id));
        $PAGE->set_pagelayout('standard');

        $target = null;
        $corerenderer = new \theme_snap\output\core_renderer($PAGE, $target);
        $heading = $corerenderer->page_heading();

        // The id is the title toggle's target (core_renderer::snap_title_toggle()).
        $this->assertEquals('<h1 id="snap-page-title"></h1>', $heading);
    }

    /**
     * Test heading should not show links on sitelevel.
     */
    public function test_course_link_should_not_appear_on_sitelevel() {
        global $COURSE, $PAGE;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $COURSE = $this->getDataGenerator()->create_course();
        $COURSE->id = SITEID; // Simulate site level.

        $PAGE->force_theme('snap');
        $PAGE->set_url('/user/view.php', array('id' => $user->id, 'course' => $COURSE->id));
        $PAGE->set_context(\context_user::instance($user->id));
        $PAGE->set_pagelayout('mypublic');

        $target = null;
        $corerenderer = new \theme_snap\output\core_renderer($PAGE, $target);
        $heading = $corerenderer->page_heading();

        $url = new \core\url('/course/view.php', ['id' => $COURSE->id]);
        $this->assertFalse(strpos($heading, $url->out()));
    }

    /**
     * Test heading should show link to course on courselevel.
     */
    public function test_course_link_should_appear_on_courselevel() {
        global $COURSE, $PAGE;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $COURSE = $this->getDataGenerator()->create_course();

        $PAGE = new \moodle_page();
        $PAGE->set_url('/user/view.php', array('id' => $user->id, 'course' => $COURSE->id));
        $PAGE->set_context(\context_user::instance($user->id));
        $PAGE->set_pagelayout('mypublic');
        $PAGE->set_heading($COURSE->fullname);

        $target = null;
        $corerenderer = new \theme_snap\output\core_renderer($PAGE, $target);
        $heading = $corerenderer->page_heading();

        $url = new \core\url('/course/view.php', ['id' => $COURSE->id]);
        $this->assertStringContainsString($url->out(), $heading);
    }

    /**
     * A book page whose blocks drawer holds only the book's contents.
     *
     * @param string $pagetype The page type.
     * @param array $otherblocks Names of real blocks to add to the drawer's region.
     * @param bool $editing Whether a teacher is looking at it in editing mode.
     * @return \theme_snap\output\core_renderer
     */
    private function contents_page(string $pagetype, array $otherblocks = [],
            bool $editing = false): \theme_snap\output\core_renderer {
        global $USER;
        global $PAGE;
        $course = $this->getDataGenerator()->create_course();
        $book = $this->getDataGenerator()->create_module('book', ['course' => $course->id]);
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, $editing ? 'editingteacher' : 'student'));
        $USER->editing = $editing;
        foreach ($otherblocks as $name) {
            $this->getDataGenerator()->create_block($name, ['parentcontextid' => \context_course::instance($course->id)->id,
                'showinsubcontexts' => 1, 'pagetypepattern' => '*', 'defaultregion' => 'side-pre']);
        }

        $PAGE = new \moodle_page();
        $PAGE->set_url('/mod/book/view.php', ['id' => $book->cmid]);
        $PAGE->set_cm(get_coursemodule_from_id('book', $book->cmid), $course);
        $PAGE->set_pagetype($pagetype);
        $PAGE->set_pagelayout('incourse');
        $PAGE->force_theme('snap');
        $bc = new \block_contents();
        $bc->title = 'Table of contents';
        $bc->attributes['class'] = 'block';
        $bc->content = 'Chapters';
        $PAGE->blocks->add_fake_block($bc, 'side-pre');
        // As the page's header does before the layout asks.
        $PAGE->blocks->load_blocks();
        $PAGE->blocks->create_all_block_instances();
        return new \theme_snap\output\core_renderer($PAGE, null);
    }

    /**
     * The blocks drawer is named after a book's or lesson's contents when that's all it holds.
     */
    public function test_blocks_drawer_named_after_contents(): void {
        $this->resetAfterTest();
        $this->assertEquals(['title' => 'Book contents', 'icon' => 'e/numbered_list'],
            $this->contents_page('mod-book-view')->snap_blocks_drawer_contents());
        $this->assertEquals(['title' => 'Lesson menu', 'icon' => 'e/bullet_list'],
            $this->contents_page('mod-lesson-view')->snap_blocks_drawer_contents());
    }

    /**
     * The blocks drawer stays "Blocks" when it holds other blocks too, in editing mode, or on other pages.
     */
    public function test_blocks_drawer_not_named_with_other_blocks(): void {
        $this->resetAfterTest();
        $this->assertNull($this->contents_page('mod-book-view', ['calendar_month'])->snap_blocks_drawer_contents());
        $this->assertNull($this->contents_page('mod-quiz-attempt')->snap_blocks_drawer_contents());
        // In editing mode it has "Add a block".
        $this->assertNull($this->contents_page('mod-book-view', [], true)->snap_blocks_drawer_contents());
    }
}
