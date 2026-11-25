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

namespace theme_snap\services;

defined('MOODLE_INTERNAL') || die();

use theme_snap\renderables\course_card;
use theme_snap\local;

require_once($CFG->dirroot.'/course/lib.php');

/**
 * Course service class.
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course {

    private function __construct() {
    }

    /**
     * Return singleton.
     *
     * @return course service
     */
    public static function service() {
        static $instance = null;
        if ($instance === null) {
            $instance = new course();
        }
        return $instance;
    }

    /**
     * Return false if the summary files are is not suitable for course cover images.
     * @param $context
     * @return bool
     */
    protected function check_summary_files_for_image_suitability($context) {

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0);
        $tmparr = [];
        // Remove '.' file from files array.
        foreach ($files as $file) {
            if ($file->get_filename() !== '.') {
                $tmparr[] = $file;
            }
        }
        $files = $tmparr;

        if (empty($files)) {
            // If the course summary files area is empty then its fine to upload an image.
            return true;
        }

        if (count($files) > 1) {
            // We have more than one file in the course summary files area, which is bad.
            return false;
        }

        // @codingStandardsIgnoreLine
        /* @var \stored_file $file*/
        $file = end($files);
        $ext = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        if (!in_array($ext, local::supported_coverimage_types())) {
            // Unsupported file type.
            return false;
        }

        return true;
    }

    /**
     * @param string $croppedimagedata
     */
    public function savecroppedimage($context, $croppedimagedata, $ext = null, $originalimageurl = null) {

        $image_parts = explode(";base64,", $croppedimagedata);
        $image_base64 = base64_decode($image_parts[1]);
        if (empty($ext)) {
            $fs = get_file_storage();
            $originalfile = $fs->get_area_files($context->id, 'theme_snap', 'coverimage', 0, "itemid, filepath, filename", false);
            if ($originalfile) {
                $originalfilename = reset($originalfile)->get_filename();
                $ext = strtolower(pathinfo($originalfilename, PATHINFO_EXTENSION));
            } else if ($originalimageurl !== null) {
                $ext = strtolower(pathinfo($originalimageurl, PATHINFO_EXTENSION));
            }
        }

        if ($context->contextlevel === CONTEXT_COURSE) {
            $filerecord = [
                'contextid' => $context->id,
                'component' => 'theme_snap',
                'filearea'  => 'croppedimage',
                'itemid'    => 0,
                'filepath'  => '/',
                'filename'  => 'course-image-cropped.'.$ext,
            ];
        } else if ($context->contextlevel === CONTEXT_COURSECAT) {
            $filerecord = [
                'contextid' => $context->id,
                'component' => 'theme_snap',
                'filearea'  => 'croppedimage',
                'itemid'    => 0,
                'filepath'  => '/',
                'filename'  => 'category-image-cropped.'.$ext,
            ];
        } else if ($context->contextlevel === CONTEXT_SYSTEM) {
            $filerecord = [
                'contextid' => $context->id,
                'component' => 'theme_snap',
                'filearea'  => 'croppedimage',
                'itemid'    => 0,
                'filepath'  => '/',
                'filename'  => 'site-image-cropped.'.$ext,
            ];
        }

        // Copy file to temp directory.
        $tmpimage = tempnam(sys_get_temp_dir(), 'tmpimg');
        file_put_contents($tmpimage, $image_base64);
        $fs = get_file_storage();
        $fs->create_file_from_pathname($filerecord, $tmpimage);
    }

    /**
     * @param \context $context
     * @param string $data
     * @param string $filename
     * @return array
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function setcoverimage(\context $context, $filename, $fileid, $croppedimagedata) {

        global $CFG, $USER;

        require_capability('moodle/course:changesummary', $context);

        $fs = get_file_storage();
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        if (!file_extension_in_typegroup($filename, 'web_image')) {
            return ['success' => false, 'warning' => get_string('unsupportedcoverimagetype', 'theme_snap', $ext)];
        }

        $newfilename = time().'rawcoverimage.'.$ext;

        $usercontext = \context_user::instance($USER->id);

        $filefromdraft = $fs->get_file($usercontext->id, 'user', 'draft', $fileid, '/', $filename);
        if ($filefromdraft->get_filesize() > get_max_upload_file_size($CFG->maxbytes)) {
            throw new \core\exception\moodle_exception('error:coverimageexceedsmaxbytes', 'theme_snap');
        }

        if ($context->contextlevel === CONTEXT_COURSE) {
            // Course cover images.
            // Check suitability of course summary files area for use with cover images.
            if (!$this->check_summary_files_for_image_suitability($context)) {
                return ['success' => false, 'warning' => get_string('coursesummaryfilesunsuitable', 'theme_snap')];
            }

            $fileinfo = array(
                'contextid' => $context->id,
                'component' => 'course',
                'filearea' => 'overviewfiles',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $newfilename, );

            // Remove any old course summary image files for this context.
            $fs->delete_area_files($context->id, $fileinfo['component'], $fileinfo['filearea']);
            // Purge course image cache in case image has been updated.
            \cache::make('core', 'course_image')->delete($context->instanceid);
        } else if ($context->contextlevel === CONTEXT_SYSTEM || $context->contextlevel === CONTEXT_COURSECAT) {
            $fileinfo = array(
                'contextid' => $context->id,
                'component' => 'theme_snap',
                'filearea' => 'poster',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $newfilename, );

            // Remove everything from poster area for this context.
            $fs->delete_area_files($context->id, 'theme_snap', 'poster');
            // Purge course image cache in case image has been updated.
            \cache::make('core', 'course_image')->delete($context->instanceid);
        } else {
            throw new \core\exception\coding_exception('Unsupported context level '.$context->contextlevel);
        }

        // Create new cover image file and process it.
        $storedfile = $fs->create_file_from_storedfile($fileinfo, $filefromdraft);
        $success = $storedfile instanceof \stored_file;
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            set_config('poster', $newfilename, 'theme_snap');
            local::process_coverimage($context);
            $this->savecroppedimage($context, $croppedimagedata, $ext);
            $coverimageurl = local::site_coverimage_url();
            $coverimageurl = "url($coverimageurl);";
        } else if ($context->contextlevel === CONTEXT_COURSE || $context->contextlevel === CONTEXT_COURSECAT) {
            local::process_coverimage($context, $storedfile);
            $this->savecroppedimage($context, $croppedimagedata, $ext);
            if ($context->contextlevel === CONTEXT_COURSE) {
                $coverimageurl = local::course_coverimage_url($context->instanceid);
                $coverimageurl = "url($coverimageurl);";
            } else {
                $coverimageurl = local::course_cat_coverimage_url($context->instanceid);
                $coverimageurl = "url($coverimageurl);";
            }
        }
        return ['success' => $success, 'imageurl'=> $coverimageurl];
    }

    /**
     * Is a specific course favorited or not for the specified or current user.
     *
     * @param int $courseid
     * @param null | int $userid
     * @param bool $fromcache
     * @return bool
     */
    public function favorited($courseid, $userid = null, $fromcache = true) {
        global $USER;

        $userid = $userid !== null ? $userid : $USER->id;

        $favorites = $this->favorites($userid, $fromcache);
        return !empty($favorites) && !empty($favorites[$courseid]);
    }

    /**
     * Get course favorites for specific userid.
     * @param null $userid
     * @param bool $fromcache
     * @return array
     */
    public function favorites($userid = null, $fromcache = true) {
        global $USER, $DB;

        $userid = $userid !== null ? $userid : $USER->id;

        static $favorites = [];

        if (!$fromcache) {
            unset($favorites[$userid]);
        }

        if (!isset($favorites[$userid])) {
            $favorites[$userid] = $DB->get_records('favourite',
                ['userid' => $userid, 'itemtype' => 'courses'],
                'itemid ASC',
                'itemid'
            );
        }

        return $favorites[$userid];
    }

    /**
     * The current user's courses for the front page's course tiles, split into tabs: Favourites, one per year
     * (newest first), Other (no year), and one per checkbox course custom field chosen in theme_snap/fptilesfieldtabs,
     * for the courses with it ticked. Courses whose top-level category's name contains one of the words in
     * theme_snap/fptilescategorywords also go in the tab of the field in theme_snap/fptilescategorytab (a DEV tab,
     * say), which is a tab even if it isn't ticked in fptilesfieldtabs.
     *
     * A course in a field's tab is only there, not under its year. A starred course is in Favourites as well as its
     * own tab. Every list is sorted by name.
     *
     * @return array ['favourites' => course[], 'years' => [year => course[]], 'other' => course[],
     *     'fieldtabs' => [shortname => ['name' => string, 'courses' => course[]]]]
     */
    public function my_courses_by_tab(): array {
        $courses = enrol_get_my_courses(['enddate', 'summary', 'summaryformat'], 'fullname ASC, id DESC');
        $favourites = $this->favorites();
        $words = array_filter(array_map(fn($w) => \core_text::strtolower(trim($w)),
            explode("\n", (string)get_config('theme_snap', 'fptilescategorywords'))));
        $categorytab = (string)get_config('theme_snap', 'fptilescategorytab');
        $fieldtabs = array_filter(explode(',', (string)get_config('theme_snap', 'fptilesfieldtabs')));
        if ($categorytab !== '' && $words) {
            $fieldtabs[] = $categorytab;
        }

        // The ticked checkbox fields of each course, in one query. All fields, whoever can see them: they sort the
        // courses, they aren't shown.
        $ticked = [];
        $fieldnames = [];
        if ($fieldtabs) {
            $handler = \core_course\customfield\course_handler::create();
            foreach ($handler->get_fields() as $field) {
                $fieldnames[$field->get('shortname')] = $field->get_formatted_name();
            }
            $data = $courses ? $handler->get_instances_data(array_keys($courses), true) : [];
            foreach ($data as $courseid => $fields) {
                foreach ($fields as $fielddata) {
                    $field = $fielddata->get_field();
                    if ($field->get('type') === 'checkbox' && $fielddata->get_value()) {
                        $ticked[$courseid][] = $field->get('shortname');
                    }
                }
            }
        }

        $result = ['favourites' => [], 'years' => [], 'other' => [], 'fieldtabs' => []];
        // In the order the fields are sorted in (Site administration > Courses > Course custom fields).
        foreach ($fieldnames as $shortname => $name) {
            if (in_array($shortname, $fieldtabs)) {
                $result['fieldtabs'][$shortname] = ['name' => $name, 'courses' => []];
            }
        }
        foreach ($courses as $course) {
            if (isset($favourites[$course->id])) {
                $result['favourites'][$course->id] = $course;
            }
            $coursetabs = $ticked[$course->id] ?? [];
            if (isset($result['fieldtabs'][$categorytab]) && $this->in_category_with_words($course, $words)) {
                $coursetabs[] = $categorytab;
            }
            $intab = false;
            foreach ($coursetabs as $shortname) {
                if (isset($result['fieldtabs'][$shortname])) {
                    $result['fieldtabs'][$shortname]['courses'][$course->id] = $course;
                    $intab = true;
                }
            }
            if ($intab) {
                continue;
            }
            $year = self::course_year($course);
            if ($year === null) {
                $result['other'][$course->id] = $course;
            } else {
                $result['years'][$year][$course->id] = $course;
            }
        }
        krsort($result['years']);
        return $result;
    }

    /**
     * Whether a course's top-level category's name contains any of the words (lower case).
     *
     * @param \stdClass $course
     * @param string[] $words
     * @return bool
     */
    private function in_category_with_words(\stdClass $course, array $words): bool {
        if (!$words) {
            return false;
        }
        $category = \core_course_category::get($course->category, IGNORE_MISSING, true);
        if (!$category) {
            return false;
        }
        $parents = $category->get_parents();
        $top = $parents ? \core_course_category::get(reset($parents), IGNORE_MISSING, true) : $category;
        $name = \core_text::strtolower((string)($top->name ?? ''));
        foreach ($words as $word) {
            if (\core_text::strpos($name, $word) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * The year a course belongs to: from its ID number when the site's year pattern (theme_snap/courseyearpattern)
     * finds one, otherwise from its end date. Used by the front page's year tabs and the My courses Year filter, so
     * both always agree.
     *
     * @param \stdClass $course with idnumber and enddate
     * @return string|null the year, like '2026', or null when there's none (shown as Other on the front page)
     */
    public static function course_year(\stdClass $course): ?string {
        $pattern = (string)get_config('theme_snap', 'courseyearpattern');
        if ($pattern !== '' && !empty($course->idnumber)
                && @preg_match(self::year_pattern_regex($pattern), $course->idnumber, $matches)
                && isset($matches[1]) && preg_match('/^\d{4}$/', $matches[1])) {
            return $matches[1];
        }
        if (!empty($course->enddate)) {
            return userdate($course->enddate, '%Y');
        }
        return null;
    }

    /**
     * A merged course's full name without its repeats. Merged courses repeat each unit's name once per class, and the
     * name may be cut off part way through a part at the database's 254 characters. Parts (separated by a slash with a
     * space on at least one side, so "and/or" stays whole) are kept in the order first seen; exact repeats (not
     * case-sensitive) go, and when the name is at the limit, a last part that's the start of an earlier one goes too.
     * Works on raw or formatted names.
     *
     * @param string $fullname
     * @return string
     */
    public static function tidy_fullname(string $fullname): string {
        if (strpos($fullname, '/') === false) {
            return $fullname;
        }
        $truncated = \core_text::strlen($fullname) >= 254;
        $parts = preg_split('/\s+\/\s*|\s*\/\s+/u', trim($fullname));
        $lastindex = count($parts) - 1;
        $kept = [];
        $seen = [];
        foreach ($parts as $i => $part) {
            $part = trim($part);
            $lower = \core_text::strtolower($part);
            if ($lower === '' || in_array($lower, $seen, true)) {
                continue;
            }
            if ($truncated && $i === $lastindex) {
                foreach ($seen as $earlier) {
                    if (strpos($earlier, $lower) === 0) {
                        continue 2;
                    }
                }
            }
            $kept[] = $part;
            $seen[] = $lower;
        }
        return implode(' / ', $kept);
    }

    /**
     * A person's name in normal case, for names stored in capitals or lower case: each word capitalised, also after
     * an apostrophe or hyphen, and after "Mc" (O'Brien-Smith, McDonald).
     *
     * @param string $name
     * @return string
     */
    public static function name_case(string $name): string {
        $name = \core_text::strtotitle(\core_text::strtolower($name));
        $name = preg_replace_callback("/(['\x{2019}-]|\bMc)(\p{Ll})/u",
            fn($m) => $m[1] . \core_text::strtoupper($m[2]), $name);
        return $name;
    }

    /**
     * The year pattern setting as a PHP regular expression: entered without delimiters, so these are added.
     *
     * @param string $pattern
     * @return string
     */
    public static function year_pattern_regex(string $pattern): string {
        return '~' . str_replace('~', '\~', $pattern) . '~';
    }

    /**
     * Set favorite status on or off.
     *
     * @param string $courseshortname
     * @param bool $on
     * @param null | int $userid
     * @return bool
     */
    public function setfavorite($courseshortname, $on = true, $userid = null) {
        global $USER, $DB;

        $course = $this->coursebyshortname($courseshortname);
        $coursecontext = \context_course::instance($course->id);
        $userid = $userid !== null ? $userid : $USER->id;
        $usercontext = \context_user::instance($userid);

        $favorited = $this->favorited($course->id, $userid, false);
        $ufservice = \core_favourites\service_factory::get_service_for_user_context($usercontext);
        if ($on) {
            if (!$favorited) {
                $ufservice->create_favourite('core_course', 'courses', $course->id, $coursecontext);
            }
        } else {
            if ($favorited) {
                $ufservice->delete_favourite('core_course', 'courses', $course->id, $coursecontext);
            }
        }
        // Kill favorited cache and return if favorited.
        return $this->favorited($course->id, $userid, false);
    }

    /**
     * Get course by shortname.
     * @param string $shortname
     * @return mixed
     */
    public function coursebyshortname($shortname, $fields = '*') {
        global $DB;
        $course = $DB->get_record('course', ['shortname' => $shortname], $fields, MUST_EXIST);
        return $course;
    }

    /**
     * Get a card renderable by course shortname.
     * @param string $shortname
     * @return course_card (renderable)
     */
    public function cardbyshortname($shortname) {
        $course = $this->coursebyshortname($shortname);
        return new course_card($course);
    }
}
