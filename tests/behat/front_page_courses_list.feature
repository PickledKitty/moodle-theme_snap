# This file is part of Moodle - http://moodle.org/
#
# Moodle is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Moodle is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
#
# Tests for enrolled courses and available courses in the front page in Snap.
#
# @package    theme_snap
# @author     Daniel Cifuentes <daniel.cifuentes@openlms.net>
# @copyright  Copyright (c) 2024 Open LMS
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: Correct functionality of enrolled courses and available courses in the front page in Snap

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | format |
      | Course 1 | C1        | 0        | topics |
      | Course 2 | C2        | 0        | topics |
      | Course 3 | C3        | 0        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student1 | C2     | student        |
      | student2 | C1     | student        |
    # Home is the front page, with no items for logged-in users.
    And the following config values are set as admin:
      | enablemyhome      | 1 |
      | defaulthomepage   | 0 |
      | frontpageloggedin |   |

  @javascript
  Scenario: Enrolled courses are displayed correctly in front page in Snap.
    Given I log in as "student1"
    And I am on site homepage
    And ".snap-home-course" "css_element" should not exist
    And ".snap-home-course-title" "css_element" should not exist
    And ".snap-home-courses-image" "css_element" should not exist
    And I should not see "Course 1"
    And I should not see "Course 2"
    And I should not see "Course 3"
    Then I log in as "admin"
    And I am on site homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Settings" in current page administration
    And I set the field with xpath "//select[@id='id_s__frontpageloggedin0']" to "Enrolled courses"
    And I press "Save changes"
    And I log out
    Then I log in as "student1"
    And I am on site homepage
    # Enrolled courses are Snap's course tiles, in tabs.
    And ".snap-fp-tiles [role='tablist']" "css_element" should exist
    And ".snap-fp-tile .snap-fp-tile-title" "css_element" should exist
    And I should see "Course 1"
    And I should see "Course 2"
    And I should not see "Course 3"
    And I log out

  @javascript
  Scenario: Available courses are displayed correctly in front page in Snap.
    Given I log in as "student1"
    And I am on site homepage
    And ".snap-home-course" "css_element" should not exist
    And ".snap-home-course-title" "css_element" should not exist
    And ".snap-home-courses-image" "css_element" should not exist
    And I should not see "Course 1"
    And I should not see "Course 2"
    And I should not see "Course 3"
    Then I log in as "admin"
    And I am on site homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Settings" in current page administration
    And I set the field with xpath "//select[@id='id_s__frontpageloggedin0']" to "List of courses"
    And I press "Save changes"
    And I log out
    Then I log in as "student1"
    And I am on site homepage
    And ".snap-home-course" "css_element" should exist
    And ".snap-home-course-title" "css_element" should exist
    And ".snap-home-courses-image" "css_element" should exist
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"
    And I log out

  @javascript
  Scenario: Hidden courses are displayed correctly in front page in Snap in My Courses and Available courses section.
    Given I log in as "admin"
    And I am on site homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Settings" in current page administration
    And I set the field with xpath "//select[@id='id_s__frontpageloggedin0']" to "List of courses"
    And I set the field with xpath "//select[@id='id_s__frontpageloggedin1']" to "Enrolled courses"
    And I press "Save changes"
    And I am on site homepage
    And "Hidden from students" "text" should not exist in the "#frontpage-course-list" "css_element"
    And I am on the course main page for "C1"
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Settings" in current page administration
    And I set the field with xpath "//select[@id = 'id_visible']" to "Hide"
    Then I press "Save and display"
    And I am on site homepage
    And "Hidden from students" "text" should exist in the "#frontpage-course-list" "css_element"

  @javascript
  Scenario: Users can open the course cards info modal.
    Given I log in as "admin"
    And I am on site homepage
    And I click on "#admin-menu-trigger" "css_element"
    And I navigate to "Settings" in current page administration
    And I set the field with xpath "//select[@id='id_s__frontpageloggedin0']" to "List of courses"
    And I set the field with xpath "//select[@id='id_s__frontpageloggedin1']" to "Enrolled courses"
    And I press "Save changes"
    And I am on site homepage
    And ".more-info" "css_element" should not be visible
    And ".additional-options .coursecategory" "css_element" should not be visible
    And ".snap-home-course .favouriteicon .fa-regular" "css_element" should not be visible
    And I hover ".snap-home-course" "css_element"
    And ".more-info" "css_element" should be visible
    And ".additional-options .coursecategory" "css_element" should be visible
    And I should see "0 students"
    And I press the tab key
    And I press the tab key
    And the focused element is "Show course information" "button"
    And I press the enter key
    And ".snap-home-course-card" "css_element" should be visible
    And ".snap-home-course-card .studentsinfo" "css_element" should be visible
    And I should see "0 students"
    And ".snap-home-course-card .coursecategory" "css_element" should be visible
    And I should see "Lorem ipsum dolor sit amet"
    And I click on ".close-course-modal" "css_element"
    And ".snap-home-course-card" "css_element" should not be visible
    And I should not see "Lorem ipsum dolor sit amet"
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C3     | student        |
      | student2 | C3     | student        |
    And I am on site homepage
    And I hover ".snap-home-course" "css_element"
    And I should see "2 students"

  @javascript
  Scenario: Users can star a course from its tile on the front page
    # My courses is off on the test site; it's on here to check stars made on the front page show there.
    Given the following config values are set as admin:
      | frontpageloggedin | 5 |
      | enablemycourses   | 1 |
    And I log in as "student2"
    And I visit "/?redirect=0"
    And I should see "Star a course to add it to Favourites." in the ".snap-fp-tiles" "css_element"
    And "#snap-fp-tab-fav" "css_element" should not be visible
    When I click on "Favourite: Course 1" "button" in the "#snap-fp-tiles-other" "css_element"
    Then the "aria-pressed" attribute of "#snap-fp-tiles-other [data-action='favourite']" "css_element" should contain "true"
    And "#snap-fp-tab-fav" "css_element" should be visible
    And I should not see "Star a course to add it to Favourites."
    # Straight there: the front page also has a "Skip My courses" link, which "I follow" would take.
    And I visit "/my/courses.php"
    And ".course-card [data-region='favourite-icon']" "css_element" should exist
    And ".course-card [data-region='favourite-icon'] .hidden" "css_element" should not exist
    # After a reload Favourites opens first, and the course is in its own tab too.
    And I visit "/?redirect=0"
    And the "aria-selected" attribute of "#snap-fp-tab-fav" "css_element" should contain "true"
    And "#snap-fp-tiles-other .snap-fp-tile" "css_element" should exist
    And I click on "Favourite: Course 1" "button" in the "#snap-fp-tiles-fav" "css_element"
    And "#snap-fp-tiles-fav .snap-fp-tile" "css_element" should not exist
    And I should see "No favourites yet. Star a course to add it here." in the "#snap-fp-tiles-fav" "css_element"
    And I visit "/my/courses.php"
    And ".course-card [data-region='favourite-icon'] .hidden" "css_element" should exist

  @javascript @accessibility
  Scenario: The course tiles' tabs and details work from the keyboard
    # DEV is a checkbox course custom field's tab, filled by the category words.
    Given the following "custom field categories" exist:
      | name        | component   | area   | itemid |
      | Course type | core_course | course | 0      |
    And the following "custom fields" exist:
      | name | category    | type     | shortname |
      | DEV  | Course type | checkbox | dev       |
    And the following "categories" exist:
      | name              | idnumber |
      | Staff development | DEVCAT   |
    And the following "courses" exist:
      | fullname   | shortname | category | startdate  | enddate    |
      | Year tile  | YT        | 0        | 1767225600 | 1782864000 |
      | Dev tile   | DT        | DEVCAT   | 0          | 0          |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | YT     | student |
      | student1 | DT     | student |
    And the following config values are set as admin:
      | frontpageloggedin    | 5           |            |
      | fptilescategorywords | development | theme_snap |
      | fptilescategorytab   | dev         | theme_snap |
    And I change window size to "large"
    And I log in as "student1"
    When I visit "/?redirect=0"
    # No favourites: the 2026 tab is open first, then Other and DEV.
    Then the "aria-selected" attribute of "#snap-fp-tab-y2026" "css_element" should contain "true"
    And I should see "Year tile" in the "#snap-fp-tiles-y2026" "css_element"
    And I click on "#snap-fp-tab-y2026" "css_element"
    And I press the right key
    And the focused element is "#snap-fp-tab-other" "css_element"
    And I should see "Course 1" in the "#snap-fp-tiles-other" "css_element"
    And I press the right key
    And the focused element is "#snap-fp-tab-field-dev" "css_element"
    And I should see "Dev tile" in the "#snap-fp-tiles-field-dev" "css_element"
    # The Details button opens the details; Escape closes them and leaves focus on it.
    And I click on "Details: Dev tile" "button"
    And the "aria-expanded" attribute of "#snap-fp-tiles-field-dev [data-action='details']" "css_element" should contain "true"
    And "View Course: Dev tile" "link" should be visible
    And I press the escape key
    And the "aria-expanded" attribute of "#snap-fp-tiles-field-dev [data-action='details']" "css_element" should contain "false"
    And the focused element is "#snap-fp-tiles-field-dev [data-action='details']" "css_element"

  @javascript @accessibility
  Scenario Outline: The course tiles fit the page and meet accessibility standards at <size> in <mode> mode
    Given the following "courses" exist:
      | fullname  | shortname | startdate  | enddate    | summary                     |
      | Year tile | YT        | 1767225600 | 1782864000 | A course with a summary.    |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | YT     | student |
    And the following "user preferences" exist:
      | user     | preference             | value  |
      | student1 | theme_boost_colourmode | <mode> |
    And the following config values are set as admin:
      | frontpageloggedin | 5 |
    And I change viewport size to "<size>"
    And I log in as "student1"
    When I visit "/?redirect=0"
    Then ".snap-fp-tile" "css_element" should exist
    And the element "#frontpage-course-list" should fit its width
    And the "#frontpage-course-list" "css_element" should meet accessibility standards
    And I click on "Details: Year tile" "button"
    And the "#frontpage-course-list" "css_element" should meet accessibility standards

    Examples:
      | size     | mode  |
      | 480x900  | light |
      | 576x900  | dark  |
      | 768x900  | light |
      | 992x900  | dark  |
      | 1200x900 | light |
      | 1400x900 | dark  |

  @javascript
  Scenario: A merged course's name shows without its repeats, and teachers' names in normal case
    Given the following "users" exist:
      | username | firstname | lastname      | email                |
      | teacher9 | JOHN      | o'brien-smith | teacher9@example.com |
    And the following "courses" exist:
      | fullname                                   | shortname | startdate  | enddate    |
      | Unit Alpha / Unit Beta / Unit Alpha / unit beta | MERGED    | 1767225600 | 1782864000 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | MERGED | student        |
      | teacher9 | MERGED | editingteacher |
    And the following config values are set as admin:
      | frontpageloggedin | 5 |
      | enablemycourses   | 1 |
    And I change window size to "large"
    And I log in as "student1"
    When I visit "/?redirect=0#snap-fp-tab-y2026"
    Then I should see "Unit Alpha / Unit Beta" in the "#snap-fp-tiles-y2026 .snap-fp-tile-title" "css_element"
    And I should not see "Unit Alpha / Unit Beta / Unit Alpha" in the "#snap-fp-tiles-y2026" "css_element"
    And I click on "Details: Unit Alpha / Unit Beta" "button"
    And I should see "Teachers: John O'Brien-Smith" in the "#snap-fp-tiles-y2026" "css_element"
    And I am on "Unit Alpha / Unit Beta / Unit Alpha / unit beta" course homepage
    And I should see "Unit Alpha / Unit Beta" in the "#page-title h1" "css_element"
    And I should not see "Unit Alpha / Unit Beta / Unit Alpha" in the "#page-title" "css_element"
    And I visit "/my/courses.php"
    And I should see "Unit Alpha / Unit Beta" in the "[data-region='courses-view']" "css_element"
    And I should not see "Unit Alpha / Unit Beta / Unit Alpha" in the "[data-region='courses-view']" "css_element"

  @javascript @accessibility
  Scenario: With a course search on the front page, it's at the top too, and All courses follows the course tiles
    Given the following config values are set as admin:
      | frontpageloggedin | 5,7 |
    And I change window size to "large"
    And I log in as "student1"
    When I visit "/?redirect=0"
    Then ".snap-fp-search-top #coursesearchbox" "css_element" should exist
    And "#moodle-frontpage-elements #shortsearchbox" "css_element" should exist
    And "#frontpage-course-list + .snap-fp-allcourses" "css_element" should exist
    And the page should meet accessibility standards
    And I set the field "coursesearchbox" to "Course 1"
    And I press the enter key
    And I should see "Course 1" in the "#region-main" "css_element"
    And I visit "/?redirect=0"
    And I click on "All courses" "link" in the ".snap-fp-allcourses" "css_element"
    # The course list (with one category, that category's page).
    And "body#page-course-index, body#page-course-index-category" "css_element" should exist
