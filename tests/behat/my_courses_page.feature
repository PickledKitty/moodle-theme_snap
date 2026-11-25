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
# along with Moodle. If not, see <http://www.gnu.org/licenses/>.
#
# Test for Snap's My courses page
#
# @package    theme_snap
# @autor      Daniel Cifuentes
# @copyright  Copyright (c) 2023 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap
Feature: Users can access to the My Courses page in Snap.

  Background:
    Given the following "users" exist:
      | username  | firstname  | lastname  | email                 |
      | student1  | Student    | 1         | student1@example.com  |
      | teacher1 | Teacher     | 1         | teacher1@example.com  |
    And the following "courses" exist:
      | fullname | shortname | format | summary      | enablecompletion |
      | Course 1 | C1        | topics | Summary text | 1                |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | student1  | C1      | student         |
      | teacher1 | C1       | editingteacher  |
    Given the following "activities" exist:
      | activity | course | idnumber | name             | intro             | duedate   | completion |
      | assign   | C1     | assign1  | Test assignment1 | Test assignment 1 | ##today## | 1          |
      | assign   | C1     | assign2  | Test assignment2 | Test assignment 2 | ##today## | 1          |
      | assign   | C1     | assign3  | Test assignment3 | Test assignment 3 | ##today## | 1          |
      | assign   | C1     | assign4  | Test assignment4 | Test assignment 4 | ##today## | 1          |
      | assign   | C1     | assign5  | Test assignment5 | Test assignment 5 | ##today## | 1          |
      | assign   | C1     | assign6  | Test assignment6 | Test assignment 6 | ##today## | 1          |
    And the following config values are set as admin:
      | defaulthomepage | 3 |
    And the following "permission overrides" exist:
      | capability            | permission | role  | contextlevel | reference |
      | moodle/course:request | Allow      | user  | System       |           |

  @javascript
  Scenario: User can access to course management options in Snap's My Courses page.
    Given I log in as "admin"
    And I should see "Course overview"
    Then ".block_myoverview" "css_element" should exist
    And ".snap-page-my-courses-options .btn-group" "css_element" should exist
    And I should see "Create course"
    And I should see "Manage courses"
    And I should not see "Request a course"
    When I click on "Create course" "button" in the "page-content" "region"
    And I should see "Add a new course"
    And I click on "#snap-home" "css_element"
    When I click on "Manage courses" "button" in the "page-content" "region"
    And I should see "Manage course categories and courses"
    And I log out
    And I log in as "student1"
    And I should see "Course overview"
    Then ".block_myoverview" "css_element" should exist
    And ".snap-page-my-courses-options .btn-group" "css_element" should exist
    And I should not see "Create course"
    And I should not see "Manage courses"
    And I should see "Request a course"
    When I click on "Request a course" "button" in the "page-content" "region"
    And I should see "Details of the course you are requesting"
    And the following config values are set as admin:
      | enablecourserequests | 0 |
    Then I am on site homepage
    And I follow "My Courses"
    And I should see "Course overview"
    Then ".block_myoverview" "css_element" should exist
    And ".snap-page-my-courses-options .btn-group" "css_element" should not exist
    And I click on "#snap-sidebar-menu button.blocks-drawer-button" "css_element"
    Then I should see "Navigation"
    Then I should see "Site pages"
    Then I should see "Site badges"

  @javascript @accessibility
  Scenario Outline: The Course overview and the course buttons fit the page at <size>
    # Its row's negative margins used to push it off the left edge and under the sidebar.
    Given I change viewport size to "<size>"
    # My Courses is the home page (Background).
    And I log in as "admin"
    And I should see "Course overview"
    Then the element "#region-main" should fit its width
    And the element "#page-mast" should fit its width
    And the page should meet accessibility standards

    Examples:
      | size     |
      | 480x900  |
      | 576x900  |
      | 768x900  |
      | 992x900  |
      | 1200x900 |
      | 1400x900 |

  @javascript @accessibility
  Scenario: With more courses than fit on a page, the Course overview pages them, though no course has an end date
    # With no end dates there's no year menu, and the list used to read that as a chosen year and show every course.
    Given the following "courses" exist:
      | fullname  | shortname | category |
      | Paging 01 | P01       | 0        |
      | Paging 02 | P02       | 0        |
      | Paging 03 | P03       | 0        |
      | Paging 04 | P04       | 0        |
      | Paging 05 | P05       | 0        |
      | Paging 06 | P06       | 0        |
      | Paging 07 | P07       | 0        |
      | Paging 08 | P08       | 0        |
      | Paging 09 | P09       | 0        |
      | Paging 10 | P10       | 0        |
      | Paging 11 | P11       | 0        |
      | Paging 12 | P12       | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | P01   | student |
      | student1 | P02   | student |
      | student1 | P03   | student |
      | student1 | P04   | student |
      | student1 | P05   | student |
      | student1 | P06   | student |
      | student1 | P07   | student |
      | student1 | P08   | student |
      | student1 | P09   | student |
      | student1 | P10   | student |
      | student1 | P11   | student |
      | student1 | P12   | student |
    And I change window size to "large"
    When I log in as "student1"
    Then I should see "Course overview"
    And "#yeardropdown" "css_element" should not exist
    And "[data-region='paging-bar']" "css_element" should be visible
    # 13 courses by name: Course 1 and Paging 01 to 11 on the first page, Paging 12 on the second.
    And I should see "Paging 11" in the "[data-region='courses-view']" "css_element"
    And I should not see "Paging 12" in the "[data-region='courses-view']" "css_element"
    And the page should meet accessibility standards

  @javascript
  Scenario: User will see a warning message when the Course overview block is disabled.
    Given the following config values are set as admin:
      | defaulthomepage | 3 |
    And I log in as "admin"
    And I wait until the page is ready
    And I go to "Site administration > Plugins > Blocks > Manage blocks" in snap administration
    And I click on "Disable Course overview" "checkbox" in the "Course overview" "table_row"
    And I follow "My Courses"
    Then ".block_myoverview" "css_element" should not exist
    And I should see "The Course overview block is disabled"
    And I go to "Site administration > Plugins > Blocks > Manage blocks" in snap administration
    And I click on "Enable Course overview" "checkbox" in the "Course overview" "table_row"
    And I follow "My Courses"
    Then ".block_myoverview" "css_element" should exist
    And I should not see "The Course overview block is disabled"

  @javascript
  Scenario: User can use the year filter in the Course overview block in Snap.
    Given I log in as "teacher1"
    And I should see "Course overview"
    And "#yeardropdown" "css_element" should not exist
    And the following "courses" exist:
      | fullname | shortname | category | startdate     | enddate      |
      | Course 2 | C2        | 0        | ##-1 years##  | ##+1 years## |
      | Course 3 | C3        | 0        | ##-1 years##  | ##+2 years## |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | teacher1 | C2       | editingteacher  |
      | teacher1 | C3       | editingteacher  |
    Then I am on site homepage
    And I follow "My Courses"
    And I should see "Course overview"
    And "#yeardropdown" "css_element" should exist
    And I click on "#yeardropdown" "css_element"
    And "ul[aria-labelledby='yeardropdown'] :nth-child(1)" "css_element" should exist
    And "ul[aria-labelledby='yeardropdown'] :nth-child(2)" "css_element" should exist
    And "ul[aria-labelledby='yeardropdown'] :nth-child(3)" "css_element" should exist
    And "ul[aria-labelledby='yeardropdown'] :nth-child(4)" "css_element" should not exist
    And I click on "ul[aria-labelledby='yeardropdown'] :nth-child(2)" "css_element"
    And I should see "Course 2"
    And I should not see "Course 3"
    And I click on "#yeardropdown" "css_element"
    And I click on "ul[aria-labelledby='yeardropdown'] :nth-child(3)" "css_element"
    And I should see "Course 3"
    And I should not see "Course 2"
    And I reload the page
    And I should see "Course 3"
    And I should not see "Course 2"
    And I click on "#yeardropdown" "css_element"
    And I click on "ul[aria-labelledby='yeardropdown'] :nth-child(1)" "css_element"
    And I should see "Course 2"
    And I should see "Course 3"

  @javascript
  Scenario: User can use the completion filter in the Course overview block in Snap.
    Given the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 2 | C2        | 0        | 1                |
      | Course 3 | C3        | 0        | 1                |
    And the following "course enrolments" exist:
      | user      | course  | role            |
      | teacher1  | C2      | editingteacher  |
      | student1  | C2      | student         |
      | student1  | C3      | student         |
    And the following "activities" exist:
      | activity    | name          | intro                       | course | idnumber   | section |
      | assign      | Assignment 1  | Test assign description 1   | C2     | assign1    | 0       |
    Then I log in as "teacher1"
    And I am on "Course 2" course homepage
    And I am on "Course 2" course homepage with editing mode "on"
    And I open "Assignment 1" actions menu
    And I choose "Edit settings" in the open action menu
    And I expand all fieldsets
    And I set the field "Students must manually mark the activity as done" to "1"
    And I press "Save and return to course"
    Then I log in as "student1"
    And I am on "Course 2" course homepage
    And I click on ".activity.modtype_assign .activityname" "css_element"
    When I press "Mark as done"
    And I wait until "Done" "button" exists
    And I follow "My Courses"
    And I click on "#progressdropdown" "css_element"
    And I follow "Completed"
    And I should see "Course 2"
    And I should not see "Course 1"
    And I should not see "Course 3"
    And I click on "#progressdropdown" "css_element"
    And I follow "Not completed"
    And I should not see "Course 2"
    And I should see "Course 1"
    And I should see "Course 3"
    And I reload the page
    And I should not see "Course 2"
    And I should see "Course 1"
    And I should see "Course 3"
    And I click on "#progressdropdown" "css_element"
    And I click on "ul[aria-labelledby='progressdropdown'] :nth-child(1)" "css_element"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

  @javascript
  Scenario Outline: User can star course using card star icon.
    Given I log in as "student1"
    And I change window size to "large"
    And I click on "#displaydropdown" "css_element"
    And I follow "<Option>"
    And I should see "Course 1"
    And ".star-button [data-action='add-favourite']" "css_element" should be visible
    And I click on "#groupingdropdown" "css_element"
    And I follow "Starred"
    And I should not see "Course 1"
    And I click on "#groupingdropdown" "css_element"
    And I follow "All"
    And I hover "<Class>" "css_element"
    And I click on ".star-button" "css_element"
    And ".star-button [data-action='remove-favourite']" "css_element" should be visible
    And I click on "#groupingdropdown" "css_element"
    And I follow "Starred"
    And I should see "Course 1"
    And I click on ".star-button" "css_element"
    Examples:
      | Option   | Class   |
      | Card     | .course-card     |
      | List     | .course-listitem     |
      | Summary  | .course-summaryitem  |