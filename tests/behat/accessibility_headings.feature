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
# Tests for heading levels on the course page.
#
# @package    theme_snap
# @copyright  2026 PickledKitty
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_ax
Feature: Course page heading levels don't skip
  In order to move around the course page by its headings
  As a screen reader user
  I need each heading to be one level below the heading it belongs to

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion | summary              |
      | Course 1 | C1        | topics | 1                | A course for testing |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity | name      | intro                | course | idnumber | section |
      | book     | Test Book | A book with chapters | C1     | book1    | 0       |
    And the following "mod_book > chapter" exists:
      | book    | Test Book     |
      | title   | First chapter |
      | content | Chapter text  |
    And the following config values are set as admin:
      | coursefootertoggle | 1 | theme_snap |

  @javascript @accessibility
  Scenario: A book's chapter list is labelled, not headed, and the dashboard and footer headings follow their H2s
    Given I change window size to "large"
    And I log in as "student1"
    When I am on the course main page for "C1"
    Then "ol.bookmod-chapters[aria-labelledby]" "css_element" should exist
    And I should see "Chapters" in the ".snap-book-chapters-label" "css_element"
    And "#region-main h4" "css_element" should not exist
    And "#snap-student-dashboard h3" "css_element" should exist
    And "#snap-student-dashboard h4" "css_element" should not exist
    And I should see "Course Contacts" in the "#snap-course-footer h2" "css_element"
    And "#snap-course-footer-contacts ul li" "css_element" should exist
    And "#snap-footer-alert" "css_element" should not exist
    And the page should meet accessibility standards
