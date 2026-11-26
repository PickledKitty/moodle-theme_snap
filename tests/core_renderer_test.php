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
#[\PHPUnit\Framework\Attributes\CoversMethod(\theme_snap\output\core_renderer::class, 'custom_menu')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\theme_snap\local::class, 'filter_custom_menu')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\theme_snap\output\core_renderer::class, 'cover_carousel')]
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

        // The id is the title toggle's target (core_renderer::snap_title_toggle()); the class is the title strip's,
        // whichever element it is (core_renderer::snap_title_classes()).
        $this->assertEquals('<h1 class="snap-page-title" id="snap-page-title"></h1>', $heading);
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

    /**
     * Who sees which lines of the custom menu: roles, siteadmin, "!", and dropdowns going with their item.
     */
    public function test_filter_custom_menu(): void {
        $menu = "Public|https://example.com/p\n" .
            "[academic_staff] Staff\n" .
            "-Staff guide|https://example.com/s\n" .
            "-[elearn_admin] Support only|https://example.com/x\n" .
            "-##\n" .
            "[elearn_admin, !siteadmin] Support admin\n" .
            "-Support page|https://example.com/a\n" .
            "[siteadmin] Full admin\n" .
            "-Admin page|https://example.com/f\n" .
            "Last|https://example.com/l";
        $lines = fn(array $roles, bool $admin) => explode("\n", local::filter_custom_menu($menu, $roles, $admin));

        // Everyone else: the unmarked lines only, with each hidden item's dropdown gone too.
        $this->assertSame(['Public|https://example.com/p', 'Last|https://example.com/l'], $lines([], false));

        // Staff: their menu, without the line for another role inside it; brackets taken off.
        $this->assertSame(['Public|https://example.com/p', 'Staff', '-Staff guide|https://example.com/s', '-##',
            'Last|https://example.com/l'], $lines(['academic_staff', 'student'], false));

        // The support role, not a site admin: the support menu.
        $this->assertContains('Support admin', $lines(['elearn_admin'], false));
        $this->assertNotContains('Full admin', $lines(['elearn_admin'], false));

        // A site admin with the support role too: only the full admin menu.
        $admin = $lines(['elearn_admin'], true);
        $this->assertContains('Full admin', $admin);
        $this->assertNotContains('Support admin', $admin);
        $this->assertNotContains('-Support page|https://example.com/a', $admin);

        // Nothing left: no menu at all.
        $this->assertSame('', local::filter_custom_menu('[siteadmin] Admin|https://example.com', [], false));
    }

    /**
     * Snap's own menu setting is used when it's set, Custom menu items when it isn't, and no menu when the lines
     * left for someone are none.
     */
    public function test_custom_menu_source(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $render = function() use (&$PAGE): string {
            $PAGE = new \moodle_page();
            $PAGE->set_url('/');
            $PAGE->set_context(\context_system::instance());
            return (new \theme_snap\output\core_renderer($PAGE, null))->custom_menu();
        };

        set_config('custommenuitems', 'Core item|https://example.com/core');
        set_config('custommenu', '', 'theme_snap');
        $this->assertStringContainsString('Core item', $render());

        set_config('custommenu', "Snap item|https://example.com/snap\n[siteadmin] Admin item|https://example.com/a",
            'theme_snap');
        $html = $render();
        $this->assertStringContainsString('Snap item', $html);
        $this->assertStringNotContainsString('Core item', $html);
        $this->assertStringNotContainsString('Admin item', $html);

        set_config('custommenu', '[siteadmin] Admin item|https://example.com/a', 'theme_snap');
        $this->assertSame('', $render());
    }

    /**
     * The carousel's Hero style (the default) shows the title over the slide; the Banner style gives each slide its
     * image's shape and height and keeps the title for screen readers. An image for small screens is passed as well.
     */
    public function test_cover_carousel_styles(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $syscontextid = \context_system::instance()->id;
        foreach (['slide_one_image' => [1600, 160], 'slide_one_image_small' => [640, 240]] as $filearea => [$w, $h]) {
            $img = imagecreatetruecolor($w, $h);
            ob_start();
            imagepng($img);
            get_file_storage()->create_file_from_string(['contextid' => $syscontextid, 'component' => 'theme_snap',
                'filearea' => $filearea, 'itemid' => 0, 'filepath' => '/', 'filename' => "$filearea.png"], ob_get_clean());
            set_config($filearea, "/$filearea.png", 'theme_snap');
        }
        set_config('slide_one_title', 'My student portal', 'theme_snap');
        set_config('cover_carousel', 1, 'theme_snap');

        $render = function() {
            $page = new \moodle_page();
            $page->set_url('/');
            $page->set_context(\context_system::instance());
            $page->force_theme('snap');
            return (new \theme_snap\output\core_renderer($page, null))->cover_carousel();
        };

        $hero = $render();
        $this->assertStringContainsString('My student portal', $hero);
        $this->assertStringContainsString('slide_one_image_small.png', $hero);
        $this->assertStringNotContainsString('--snap-slide-ratio', $hero);
        $this->assertStringNotContainsString('snap-carousel-banner', $hero);
        $this->assertMatchesRegularExpression('/class="carousel-caption"/', $hero);

        set_config('cover_carousel_style', 'banner', 'theme_snap');
        $banner = $render();
        $this->assertStringContainsString('snap-carousel-banner', $banner);
        $this->assertStringContainsString('--snap-slide-ratio: 1600 / 160;', $banner);
        $this->assertStringContainsString('--snap-slide-height: 160px;', $banner);
        $this->assertStringContainsString('--snap-slide-ratio-small: 640 / 240;', $banner);
        $this->assertStringContainsString('carousel-caption visually-hidden', $banner);
        $this->assertStringContainsString('My student portal', $banner);
    }
}
