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
 * Layout - default.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require(__DIR__.'/header.php');

use theme_snap\local;
use theme_snap\output\shared;

// @codingStandardsIgnoreStart
// Note, coding standards ignore is required so that we can have more readable indentation under php tags.

$mastimage = ($COURSE->id != SITEID && local::has_cover_image()) ? 'mast-image' : '';
$carouselheader = !empty($carousel) ? 'snap-carousel-header' : '';
if ($PAGE->pagetype == 'admin-search') {
    $PAGE->set_secondary_navigation(false);
}
// Snap has its own activity navigation, so no Moodle Previous/Next footer bar. Set before the
// activity header is rendered, so the header keeps the Mark as done button instead of the bar.
$PAGE->set_show_navigation_footer(false);
?>

<!-- Moodle js hooks -->
<div id="page">
<div id="page-content">

<!--
////////////////////////// MAIN  ///////////////////////////////
-->
<div id="moodle-page" class="clearfix">
<?php
echo $OUTPUT->custom_menu_spacer();
?>
<div id="page-header" class="clearfix <?php echo $mastimage; ?> <?php echo $carouselheader; ?>">
    <?php if ($PAGE->pagetype !== 'site-index' && $PAGE->navbar->has_items()) { ?>
        <nav class="breadcrumb-nav" aria-label="breadcrumbs"><?php echo $OUTPUT->navbar(); ?></nav>
    <?php }
        if ($carousel) {
            // Front page carousel.
            echo $carousel;
        } else {
            // Front page banner image.
    ?>
        <?php
            echo $OUTPUT->snap_page_header();

        } // End else.
        if ($PAGE->pagetype == 'admin-search') {
            echo implode('', $PAGE->get_header_actions());
        }
        ?>
</div>
<?php
if ($PAGE->cm && course_get_format($COURSE)->uses_course_index()) {
    // The course index, as on the course page, to move around the course from any activity.
    $snapactivitycourseindex = true;
    require(__DIR__ . '/course_index_drawer.php');
}
?>
<?php
/**
 * The secondary navigation tabs: Course, Settings, Participants... for a course, the activity's own pages for an
 * activity. Built after the breadcrumbs, so they keep the whole trail.
 *
 * @return string
 */
$snapsecondarynavigation = function() use ($PAGE, $OUTPUT): string {
    if (!$PAGE->has_secondary_navigation()) {
        return '';
    }
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    return $OUTPUT->render_from_template('theme_snap/secondary_navigation',
        ['secondarymoremenu' => $moremenu->export_for_template($OUTPUT)]);
};

// The book and lesson show their contents in a column beside the content, so their heading and tabs go
// across the page above both columns.
$headingacross = in_array($PAGE->pagetype, ['mod-book-view', 'mod-lesson-view'], true);
if ($headingacross) {
    echo html_writer::start_div('snap-activity-heading-across');
    echo $OUTPUT->context_header();
    echo $OUTPUT->activity_header();
    echo $snapsecondarynavigation();
    echo html_writer::end_div();
}
?>
<div id="region-main-box">
<section id="region-main">
<?php
if ($OUTPUT->snap_page_is_activity_view() && !$headingacross) {
    echo $OUTPUT->context_header();
}
echo $OUTPUT->course_content_header();

// Ensure edit blocks button is only shown for appropriate pages.
if ($PAGE->button === null) {
    $hasadminbutton = false;
} else {
    $hasadminbutton = stripos($PAGE->button, '"adminedit"') || stripos($PAGE->button, '"edit"');
}

if ($hasadminbutton) {
    // List paths to black list for 'turn editting on' button here.
    // Note, to use regexs start and end with a pipe symbol - e.g. |^/report/| .
    $editbuttonblacklist = array(
        '/comment/',
        '/cohort/index.php',
        '|^/report/|',
        '|^/admin/|',
        '|^/mod/data/|',
        '/tag/manage.php',
        '/grade/edit/scale/index.php',
        '/outcome/admin.php',
        '/mod/assign/adminmanageplugins.php',
        '/theme/index.php',
        '/user/editadvanced.php',
        '/user/profile/index.php',
        '/mnet/service/enrol/index.php',
        '/local/mrooms/view.php',
    );
    $pagepath = local::current_url_path();

    foreach ($editbuttonblacklist as $blacklisted) {
        if ($blacklisted[0] == '|' && $blacklisted[strlen($blacklisted) - 1] == '|') {
            // Use regex to determine blacklisting.
            if (preg_match ($blacklisted, $pagepath) === 1) {
                // This url path is blacklisted, stop button from being displayed.
                $PAGE->set_button('');
            }
        } else if ($pagepath == $blacklisted) {
            // This url path is blacklisted, stop button from being displayed.
            $PAGE->set_button('');
        }
    }
}

// Validation added to check if settings option should be displayed.
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !local::show_setting_menu();
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;
if ($regionmainsettingsmenu) {
    echo "<div class='snap-page-heading-button'>";
    echo $regionmainsettingsmenu;
    echo "</div>";
}
if ($PAGE->pagelayout === 'mycourses') {
    // Add course management options in my courses page.
    echo $OUTPUT->snap_my_courses_management_options();
}
if ($PAGE->pagelayout === 'frontpage' && $PAGE->pagetype === 'site-index') {
    require(__DIR__.'/faux_site_index.php');
} else {
    if (!$headingacross) {
        echo $OUTPUT->activity_header();
        echo $snapsecondarynavigation();
    }
    echo $OUTPUT->main_content();
}

if ($PAGE->pagetype === 'mod-quiz-report') {
    // Table or cards view of the results, with each student's attempts banded together.
    $PAGE->requires->js_call_amd('theme_snap/quiz_report_view', 'init');
}
if (in_array($PAGE->pagetype, ['report-completion-index', 'report-progress-index'], true)) {
    // The column headings stay in view when scrolling down.
    $PAGE->requires->js_call_amd('theme_snap/report_sticky_headings', 'init');
    // Keyboard focus kept clear of the pinned rows and names; on tablets and phones the table scrolls in a box,
    // with a scrollbar that's always shown.
    $PAGE->requires->js_call_amd('theme_snap/scroll_box', 'init',
        ['table#completion-progress', 'thead > tr:nth-last-child(-n+2)', '', 'thead > tr:last-child > th:first-child']);
}
if ($PAGE->pagetype === 'grade-report-grader-index') {
    // Keyboard focus kept clear of the pinned rows and names; on tablets and phones the report scrolls in a box,
    // with a scrollbar that's always shown.
    $PAGE->requires->js_call_amd('theme_snap/scroll_box', 'init',
        ['.gradeparent', 'tr.heading', 'tr.lastrow:not(.userrow)', '#studentheader']);
}

echo $OUTPUT->course_content_footer();
if (stripos($PAGE->bodyclasses, 'format-singleactivity') !== false ) {
    // Shared renderer is only loaded if required at this point.
    $output = \theme_snap\output\shared::course_tools();
    if (!empty($output)) {
        echo $output;
    }
}

?>

</section>
</div>

<?php

require __DIR__.'/blocks_drawer.php';
echo $OUTPUT->snap_feeds_side_menu();
// Previous and next activity across the page, below any column beside the content.
echo $OUTPUT->activity_navigation();
?>
</div>

</div>
</div>

<?php echo $OUTPUT->standard_after_main_region_html() ?>
<!-- close moodle js hooks -->
<?php // @codingStandardsIgnoreEnd
require(__DIR__.'/footer.php');
