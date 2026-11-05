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
# Tests for the page header: the band, the title strip and the breadcrumbs.
#
# @package    theme_snap
# @copyright  2026 PickledKitty
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_ax
Feature: The page header is a band, then the title, then the breadcrumbs
  In order to always know where I am in the site
  As a user
  I need each page to show its title in full, with the breadcrumbs under it, below a slim image band

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname                                                                                                                                                                                                                                  | shortname                                                                   | format | initsections |
      | Provide quality service / Maintain equipment for activit / Work effectively in sport, fit / Incorporate anatomy and physio / Recognise and apply exercise c / Instruct fitness programs / Instruct exercise to older cli / Identify risk and apply risk m / Pa copy 2 | XLSQ3202110: 61065 / 61066 / 61067 / 61068 / 61069 / 61070 / 61071 / 61072 / 61073_2 | topics | 1                  |
      | Course 2                                                                                                                                                                                                                                  | C2                                                                          | topics | 1                  |
    And the following "course enrolments" exist:
      | user     | course                                                                      | role    |
      | student1 | XLSQ3202110: 61065 / 61066 / 61067 / 61068 / 61069 / 61070 / 61071 / 61072 / 61073_2 | student |
      | student1 | C2                                                                          | student |
    And the following "activities" exist:
      | activity | name              | intro      | course | idnumber | section |
      | assign   | Important message | Read this. | C2     | assign1  | 1       |

  @javascript @accessibility
  Scenario: The band comes first, then the title strip with the page's only H1, then the breadcrumbs
    Given I change window size to "large"
    And I log in as "student1"
    When I am on "C2" course homepage
    Then "#page-header.snap-header-band + #page-title" "css_element" should exist
    And "#page-title + .breadcrumb-nav" "css_element" should exist
    And I should see "Course 2" in the "#page-title h1" "css_element"
    And "#page-header *" "css_element" should not exist
    And I should not see cover image in page header
    And the page should meet accessibility standards
    And I am on the "Important message" "assign activity" page
    And "#page-header.snap-header-band + #page-title" "css_element" should exist
    And I should see "Course 2" in the "#page-title h1" "css_element"
    And the page should meet accessibility standards

  @javascript @accessibility
  Scenario: A merged course's long title is cut to two lines on a laptop screen too, its button shows it all, and nothing scrolls sideways on a phone
    Given I change window size to "medium"
    And I log in as "student1"
    When I am on "Provide quality service / Maintain equipment for activit / Work effectively in sport, fit / Incorporate anatomy and physio / Recognise and apply exercise c / Instruct fitness programs / Instruct exercise to older cli / Identify risk and apply risk m / Pa copy 2" course homepage
    Then the element "#page-title #page-mast h1" should have clipped content
    And I click on "Show the full title" "button"
    And I should see "Identify risk and apply risk m / Pa copy 2" in the "#page-title h1.snap-title-long" "css_element"
    And the element "#page-title #page-mast h1" should not have clipped content
    And the element ".breadcrumb-nav" should fit its width
    And the page should meet accessibility standards
    And I change window size to "mobile"
    And I reload the page
    And the element ".breadcrumb-nav" should fit its width

  @javascript @accessibility
  Scenario: The breadcrumbs start at My Courses, show the course's short name in full, and Exit activity goes back to the course
    Given I change window size to "large"
    And I log in as "student1"
    When I am on the "Important message" "assign activity" page
    Then "My Courses" "link" should exist in the ".breadcrumb li.snap-crumb-mycourses:first-child" "css_element"
    And ".breadcrumb li.snap-crumb-course .icon" "css_element" should exist
    And I should not see "Home" in the ".breadcrumb" "css_element"
    And I should not see "Dashboard" in the ".breadcrumb" "css_element"
    And the page should meet accessibility standards
    And I click on "Exit activity" "link" in the ".breadcrumb-nav" "css_element"
    And I should see "Important message" in the "#region-main" "css_element"
    And I should see "Course 2" in the "#page-title h1" "css_element"
    And "#page-title + .breadcrumb-nav .snap-exit-activity" "css_element" should not exist
    And I am on "Provide quality service / Maintain equipment for activit / Work effectively in sport, fit / Incorporate anatomy and physio / Recognise and apply exercise c / Instruct fitness programs / Instruct exercise to older cli / Identify risk and apply risk m / Pa copy 2" course homepage
    And I should see "XLSQ3202110: 61065 / 61066 / 61067 / 61068 / 61069 / 61070 / 61071 / 61072 / 61073_2" in the ".breadcrumb li.snap-crumb-course" "css_element"

  @javascript @accessibility
  Scenario: On a phone, a long title is cut to two lines, and its button shows it in full
    Given I change window size to "mobile"
    And I log in as "student1"
    When I am on "Provide quality service / Maintain equipment for activit / Work effectively in sport, fit / Incorporate anatomy and physio / Recognise and apply exercise c / Instruct fitness programs / Instruct exercise to older cli / Identify risk and apply risk m / Pa copy 2" course homepage
    Then the element "#page-title #page-mast h1" should have clipped content
    And the "aria-expanded" attribute of ".snap-title-toggle" "css_element" should contain "false"
    And the page should meet accessibility standards
    And I click on "Show the full title" "button"
    And the "aria-expanded" attribute of ".snap-title-toggle" "css_element" should contain "true"
    And the element "#page-title #page-mast h1" should not have clipped content
    # On phones the course page has no breadcrumbs: My Courses is in the user menu, and the title links to the course.
    And ".breadcrumb-nav" "css_element" should not be visible
    And I am on the "Important message" "assign activity" page
    And ".breadcrumb li.snap-crumb-course" "css_element" should not be visible
    And I should see "Important message" in the ".breadcrumb-nav" "css_element"
    And "Exit activity" "link" should be visible
