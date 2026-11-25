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
namespace theme_snap;
use theme_snap\services\course;
use theme_snap\renderables\course_card;
use theme_snap\local;

/**
 * Test course card service.
 * @package   theme_snap
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Open LMS (https://www.openlms.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversMethod(course::class, 'course_year')]
#[\PHPUnit\Framework\Attributes\CoversMethod(course::class, 'my_courses_by_tab')]
#[\PHPUnit\Framework\Attributes\CoversMethod(course::class, 'tidy_fullname')]
#[\PHPUnit\Framework\Attributes\CoversMethod(course::class, 'name_case')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\theme_snap\admin_setting_configyearpattern::class, 'validate')]
class services_course_test extends \advanced_testcase {

    /**
     * @var stdClass
     */
    protected $user1;

    /**
     * @var array
     */
    protected $courses = [];

    /**
     * @var course
     */
    protected $courseservice;

    /**
     * Pre-requisites for tests.
     * @throws \core\exception\coding_exception
     */
    public function setUp(): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $this->resetAfterTest();

        $CFG->theme = 'snap';

        // Create 10 courses.
        for ($c = 0; $c < 10; $c++) {
            $this->courses[] = $this->getDataGenerator()->create_course();
        }

        // Create 5 courses in the past.
        for ($c = 0; $c < 5; $c++) {
            $enddate = time() - DAYSECS * ($c + 1) * 10;
            $startdate = $enddate - YEARSECS;
            $record = (object) [
                'startdate' => $startdate,
                'enddate' => $enddate,
            ];
            $this->courses[] = $this->getDataGenerator()->create_course($record);
        }

        $this->user1 = $this->getDataGenerator()->create_user();

        // Enrol user to all courses.
        $sturole = $DB->get_record('role', array('shortname' => 'student'));

        foreach ($this->courses as $course) {
            $this->getDataGenerator()->enrol_user($this->user1->id,
                $course->id,
                $sturole->id);
        }

        $this->courseservice = course::service();
    }

    public function test_service() {
        $testservice = course::service();
        $this->assertEquals($this->courseservice, $testservice);
        $this->assertTrue($testservice instanceof course);
    }

    public function test_favorited() {
        $service = $this->courseservice;
        $favorited = $service->favorited($this->courses[0], $this->user1->id);
        $this->assertFalse($favorited);

        $service->setfavorite($this->courses[0]->shortname, true, $this->user1->id);

        // Make sure marked as favorite.
        $favorited = $service->favorited($this->courses[0]->id, $this->user1->id);
        $this->assertTrue($favorited);
    }

    public function test_favorites() {
        $service = $this->courseservice;
        $service->setfavorite($this->courses[0]->shortname, true, $this->user1->id);
        $service->setfavorite($this->courses[1]->shortname, true, $this->user1->id);

        $favorites = $service->favorites($this->user1->id, false);
        $this->assertTrue(isset($favorites[$this->courses[0]->id]));
        $this->assertTrue(isset($favorites[$this->courses[1]->id]));
        $this->assertFalse(isset($favorites[$this->courses[2]->id]));
    }

    /**
     * The front page's tabs: a starred course is in Favourites and its own tab; years newest first; no year is Other;
     * custom field tabs, one filled by top-level category words too; courses in those tabs not under years.
     */
    public function test_my_courses_by_tab(): void {
        global $DB;
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $dev = $gen->create_category(['name' => 'Staff development']);
        $devchild = $gen->create_category(['name' => 'Drafts', 'parent' => $dev->id]);
        $handler = \core_course\customfield\course_handler::create();
        $fieldcat = $gen->get_plugin_generator('core_customfield')->create_category(
            ['component' => 'core_course', 'area' => 'course']);
        foreach (['devcourse' => 'Development course', 'infocourse' => 'Information course'] as $shortname => $name) {
            $gen->get_plugin_generator('core_customfield')->create_field(['categoryid' => $fieldcat->get('id'),
                'type' => 'checkbox', 'shortname' => $shortname, 'name' => $name]);
        }
        $make = function(string $name, array $record = []) use ($gen, $user) {
            $course = $gen->create_course(['fullname' => $name] + $record);
            $gen->enrol_user($user->id, $course->id, 'student');
            return (int)$course->id;
        };
        $y2025 = $make('B 2025', ['startdate' => make_timestamp(2025, 1, 1), 'enddate' => make_timestamp(2025, 6, 1)]);
        $y2026 = $make('A 2026', ['startdate' => make_timestamp(2026, 1, 1), 'enddate' => make_timestamp(2026, 6, 1)]);
        $noyear = $make('No year');
        $starred = $make('Starred', ['startdate' => make_timestamp(2026, 1, 1), 'enddate' => make_timestamp(2026, 6, 1)]);
        $devcat = $make('In dev category', ['category' => $devchild->id]);
        $devfield = $make('Dev by field', ['customfield_devcourse' => 1]);
        $info = $make('Information', ['customfield_infocourse' => 1, 'enddate' => make_timestamp(2026, 6, 1),
            'startdate' => make_timestamp(2026, 1, 1)]);
        $this->courseservice->setfavorite($DB->get_field('course', 'shortname', ['id' => $starred]), true, $user->id);
        // The Development course field's tab gets the courses with it ticked and, from the category words, the
        // courses in the development category; it's a tab though only Information course is ticked as one.
        set_config('fptilescategorywords', "Snap\nDEVELOPMENT", 'theme_snap');
        set_config('fptilescategorytab', 'devcourse', 'theme_snap');
        set_config('fptilesfieldtabs', 'infocourse', 'theme_snap');
        $this->setUser($user);

        $tabs = $this->courseservice->my_courses_by_tab();
        $this->assertSame([$starred], array_keys($tabs['favourites']));
        $this->assertSame(['2026', '2025'], array_map('strval', array_keys($tabs['years'])));
        $this->assertSame([$y2026, $starred], array_keys($tabs['years']['2026']));
        $this->assertSame([$y2025], array_keys($tabs['years']['2025']));
        $this->assertSame([$noyear], array_keys($tabs['other']));
        $this->assertSame(['devcourse', 'infocourse'], array_keys($tabs['fieldtabs']));
        $this->assertSame('Development course', $tabs['fieldtabs']['devcourse']['name']);
        $this->assertEqualsCanonicalizing([$devcat, $devfield], array_keys($tabs['fieldtabs']['devcourse']['courses']));
        $this->assertSame('Information course', $tabs['fieldtabs']['infocourse']['name']);
        $this->assertSame([$info], array_keys($tabs['fieldtabs']['infocourse']['courses']));

        // Without the settings, those courses go by their years.
        set_config('fptilescategorywords', '', 'theme_snap');
        set_config('fptilescategorytab', '', 'theme_snap');
        set_config('fptilesfieldtabs', '', 'theme_snap');
        $tabs = $this->courseservice->my_courses_by_tab();
        $this->assertSame([], $tabs['fieldtabs']);
        $this->assertContains($info, array_keys($tabs['years']['2026']));
        $this->assertEqualsCanonicalizing([$noyear, $devcat, $devfield], array_keys($tabs['other']));
    }

    public function test_setfavorite() {
        $returned = $this->courseservice->setfavorite($this->courses[0]->shortname, true, $this->user1->id);
        $this->assertTrue($returned);
    }

    public function test_coursebyshortname() {
        $expected = get_course($this->courses[0]->id);
        $actual = $this->courseservice->coursebyshortname($this->courses[0]->shortname);

        $this->assertEquals($expected, $actual);
    }

    public function test_cardbyshortname() {
        $card = $this->courseservice->cardbyshortname($this->courses[0]->shortname);
        $this->assertTrue($card instanceof course_card);
        $this->assertEquals($card->courseid, $this->courses[0]->id);
    }

    // Records for favorite courses should not exist when the user is deleted.
    public function test_user_deletion() {
        global $DB;
        $service = $this->courseservice;
        $service->setfavorite($this->courses[0]->shortname, true, $this->user1->id);
        $service->setfavorite($this->courses[1]->shortname, true, $this->user1->id);
        $params = array('userid' => $this->user1->id, 'component' => 'core_course');
        $favorites = $DB->get_records('favourite', $params);
        $this->assertNotEmpty($favorites);
        delete_user($this->user1);
        $favorites = $DB->get_records('favourite', $params);
        $this->assertEmpty($favorites);
    }

    // Records for favorite courses should not exist when the course is deleted.
    public function test_course_deletion() {
        global $DB;
        $service = $this->courseservice;
        $service->setfavorite($this->courses[0]->shortname, true, $this->user1->id);
        $service->setfavorite($this->courses[1]->shortname, true, $this->user1->id);
        $params = array('userid' => $this->user1->id, 'component' => 'core_course');
        $favorites = $DB->count_records('favourite', $params);
        $this->assertEquals(2, $favorites);
        $this->assertNotEmpty($favorites);
        delete_course($this->courses[0], false);
        $favorites = $DB->count_records('favourite', $params);
        $this->assertEquals(1, $favorites);
        delete_course($this->courses[1], false);
        $favorites = $DB->get_records('favourite', $params);
        $this->assertEmpty($favorites);
    }

    private function count_course_sections($courseid) {
        global $DB;
        $count = $DB->count_records('course_sections', ['course' => $courseid]);
        return $count;
    }

    public function test_section_fragment() {
        $this->markTestSkipped('To be reviewed in INT-20324');
        global $CFG, $DB;
        require_once($CFG->dirroot .'/theme/snap/lib.php');
        $topics = $this->getDataGenerator()->create_course(
            array('numsections' => 5, 'format' => 'topics', 'initsections' => '1'),
            array('createsections' => true));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherole = $DB->get_record('role', array('shortname' => 'editingteacher'));

        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id,
            $topics->id,
            'student');
        $this->getDataGenerator()->enrol_user($teacher->id,
            $topics->id,
            'editingteacher');
        $this->getDataGenerator()->create_module('assign', ['course' => $topics->id, 'section' => 1,
            'name' => 'Section Assign', ]);
        $params = ['courseid' => $topics->id, 'section' => 1];
        $this->setUser($student);
        $section = theme_snap_output_fragment_section($params);
        $this->assertStringContainsString('aria-label="Section 1"', $section);
        // Section doesn't have the modchooser div.
        $this->assertStringNotContainsString('snap-modchooser', $section);
        $this->assertStringContainsString('Section Assign', $section);
        $this->getDataGenerator()->create_module('forum', ['course' => $topics->id, 'section' => 2,
            'name' => 'Fragment forum', ]);
        $params['section'] = 2;
        $section = theme_snap_output_fragment_section($params);
        $this->assertStringContainsString('Fragment forum', $section);
        $this->setUser($teacher);
        // Missing param will result on empty text.
        $params['section'] = '';
        $section = theme_snap_output_fragment_section($params);
        $this->assertEmpty($section);
        $params['section'] = 2;
        $section = theme_snap_output_fragment_section($params);
        $this->assertStringContainsString('Fragment forum', $section);
        $this->assertStringContainsString('snap-modchooser', $section);
    }

    /**
     * A course's year: from its ID number when the pattern finds one, otherwise from its end date, otherwise none.
     */
    public function test_course_year(): void {
        $end2027 = make_timestamp(2027, 2, 12);
        $course = (object)['idnumber' => '91236.202620', 'enddate' => $end2027];

        // No pattern: the end date's year.
        set_config('courseyearpattern', '', 'theme_snap');
        $this->assertSame('2027', course::course_year($course));

        // The pattern finds the year in the ID number, which wins over the end date.
        set_config('courseyearpattern', '\.(\d{4})\d{2}$', 'theme_snap');
        $this->assertSame('2026', course::course_year($course));

        // An ID number the pattern doesn't match: the end date's year.
        $this->assertSame('2027', course::course_year((object)['idnumber' => 'ABC-101', 'enddate' => $end2027]));

        // Neither: no year.
        $this->assertNull(course::course_year((object)['idnumber' => 'ABC-101', 'enddate' => 0]));
        $this->assertNull(course::course_year((object)['idnumber' => '', 'enddate' => 0]));

        // What the brackets find must be a year.
        set_config('courseyearpattern', '^(\d+)', 'theme_snap');
        $this->assertSame('2027', course::course_year($course));

        // A broken pattern saved some other way is ignored.
        set_config('courseyearpattern', '(\d{4', 'theme_snap');
        $this->assertSame('2027', @course::course_year($course));
    }

    /**
     * The year pattern setting saves only a valid expression with brackets, or nothing.
     */
    public function test_year_pattern_setting(): void {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        $setting = new \theme_snap\admin_setting_configyearpattern('theme_snap/courseyearpattern', 'Year', '');
        $this->assertTrue($setting->validate(''));
        $this->assertTrue($setting->validate('\.(\d{4})\d{2}$'));
        $this->assertTrue($setting->validate('a~(\d{4})'));
        $this->assertSame(get_string('courseyearpattern_invalid', 'theme_snap'), $setting->validate('(\d{4'));
        $this->assertSame(get_string('courseyearpattern_nogroup', 'theme_snap'), $setting->validate('\d{4}'));
    }

    /**
     * A merged course's name loses its repeats, and a last part cut off at 254 characters; "and/or" stays whole.
     */
    public function test_tidy_fullname(): void {
        $this->assertSame('Course 1', course::tidy_fullname('Course 1'));
        $this->assertSame('Read and/or write', course::tidy_fullname('Read and/or write'));
        $this->assertSame('Unit A / Unit B', course::tidy_fullname('Unit A / Unit B / unit a / Unit B'));
        $this->assertSame('Unit A / Unit B', course::tidy_fullname('Unit A /Unit B/ Unit A'));
        // Under the limit, a part that only starts like an earlier one is its own part.
        $this->assertSame('Unit Alpha / Unit Al', course::tidy_fullname('Unit Alpha / Unit Al'));
        // At the limit it's the earlier part cut off, so it goes.
        $atlimit = 'Unit Alpha / ' . str_repeat('x', 231) . ' / Unit Al';
        $this->assertSame(254, \core_text::strlen($atlimit));
        $this->assertSame('Unit Alpha / ' . str_repeat('x', 231), course::tidy_fullname($atlimit));
    }

    /**
     * Names in capitals or lower case come out in normal case.
     */
    public function test_name_case(): void {
        $this->assertSame("John O'Brien-Smith", course::name_case("JOHN O'BRIEN-SMITH"));
        $this->assertSame('Mary McDonald', course::name_case('mary mcdonald'));
        $this->assertSame('Mackenzie Ng', course::name_case('MACKENZIE NG'));
        $this->assertSame('Élodie Durand', course::name_case('ÉLODIE DURAND'));
    }
}
