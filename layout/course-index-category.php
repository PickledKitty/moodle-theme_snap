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
 * Layout - course-index-category.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2017 Open LMS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use theme_snap\local;

require(__DIR__.'/header.php');

$iscoursecat = $PAGE->context->contextlevel == CONTEXT_COURSECAT;

// @codingStandardsIgnoreStart
// Note, coding standards ignore is required so that we can have more readable indentation under php tags.

$mastimage = local::has_cover_image() ? 'mast-image' : '';
?>

<!-- moodle js hooks -->
<div id="page">
    <div id="page-content">
    <!--
    ////////////////////////// MAIN  ///////////////////////////////
    -->
        <div id="moodle-page" class="clearfix">
        <div id="page-header" class="clearfix snap-category-header <?php echo $mastimage; ?>">
        <?php if ($PAGE->navbar->has_items()) { ?>
        <nav class="breadcrumb-nav" aria-label="breadcrumbs"><?php echo $OUTPUT->navbar(); ?></nav>
        <?php } ?>
            <?php
            $categories = $PAGE->categories;
            $cat = reset($categories);
            if (!empty($categories)) {
                $cat = reset($categories);
            }
            $manageurl = false;
            if (has_capability('moodle/category:manage', $PAGE->context)) {
                $manageurl = new \core\url('/course/management.php');
            }

            echo $OUTPUT->snap_page_header();
            ?>
        </div>
        <section id="region-main">
            <?php
            // Course management buttons, on the course listing only: other pages with this layout, such
            // as a category's content bank, don't load the category they need.
            if ($PAGE->pagetype === 'course-index-category') {
            ?>
            <div class="snap-category-buttons">
                <?php
                global $OUTPUT;
                $context = get_category_or_system_context(empty($cat) ? 0 : $cat->id);
                $button = function(\core\url $url, string $text): string {
                    return \core\output\html_writer::link($url, $text, ['class' => 'btn btn-secondary']);
                };
                if ($iscoursecat) {
                    if (has_capability('moodle/course:create', $context)) {
                        // Print link to create a new course, for the 1st available category.
                        if ($cat->id) {
                            $url = new \core\url('/course/edit.php', ['category' => $cat->id, 'returnto' => 'category']);
                        } else {
                            $url = new \core\url('/course/edit.php', ['category' => $CFG->defaultrequestcategory, 'returnto' => 'topcat']);
                        }
                        echo $button($url, get_string('addnewcourse', 'moodle'));
                    }
                    if (has_capability('moodle/category:manage', $context)) {
                        echo $button(new \core\url('/course/editcategory.php', ['parent' => $cat->id]),
                            get_string('addsubcategory', 'moodle'));
                    }
                    if ($manageurl) {
                        echo $button($manageurl, get_string('managecourses', 'moodle'));
                    }
                    // Here, with the rest, rather than in the page header (core_renderer::snap_page_header()).
                    if (can_edit_in_category($cat->id)) {
                        echo $button(new \core\url('/course/editcategory.php', ['id' => $cat->id]),
                            get_string('categoryedit', 'theme_snap'));
                    }
                    echo $OUTPUT->container_start('buttons');
                    if (\core_course_category::is_simple_site() == 1) {
                        snap_print_course_request_buttons(\context_system::instance());
                    } else {
                        snap_print_course_request_buttons($context);
                    }
                    echo $OUTPUT->container_end();
                } else {
                    if ($manageurl) {
                        echo $button($manageurl, get_string('managecourses', 'moodle'));
                    }
                    if (has_capability('moodle/course:create', $context)) {
                        // Print link to create a new course, for the 1st available category.
                        echo $button(new \core\url('/course/edit.php', ['category' => $CFG->defaultrequestcategory, 'returnto' => 'topcat']),
                            get_string('addnewcourse', 'moodle'));
                    }
                }
                ?>
            </div>
            <?php
            }

                echo $OUTPUT->main_content();
                ?>
        </section>
        </div>
        <?php
        // The side blocks are in the blocks drawer only: here as well, they'd be a hidden second copy.
        require __DIR__.'/blocks_drawer.php';
        echo $OUTPUT->snap_feeds_side_menu();
        ?>
    </div>
</div>
<?php echo $OUTPUT->standard_after_main_region_html() ?>
    <!-- close moodle js hooks -->
<?php
// @codingStandardsIgnoreEnd
require(__DIR__.'/footer.php');
