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
# Tests for the course index (Contents) on phones.
#
# @package    theme_snap
# @copyright  2026 PickledKitty
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_ax
Feature: Contents on phones, and searching it
  In order to have the page's width for the course on a phone, and find an activity quickly
  As a user
  I need to open the course index from Snap's sidebar on a phone, and search it from a box of its own

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | initsections |
      | Course 1 | C1        | topics | 1            |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | name            | intro                    | course | idnumber | section |
      | assign   | Test assignment | An assignment to hand in | C1     | assign1  | 1       |
      | page     | Reading list    | Books for this course    | C1     | page1    | 1       |

  @javascript @accessibility
  Scenario: On a phone, Contents opens from the sidebar on the course page
    Given I change viewport size to "360x740"
    And I log in as "student1"
    When I am on the course main page for "C1"
    Then ".drawer-toggles .drawer-left-toggle" "css_element" should not be visible
    And ".snap-courseindex-sidebar-button" "css_element" should be visible
    And I click on ".snap-courseindex-sidebar-button" "css_element"
    And "#theme_boost-drawers-courseindex" "css_element" should be visible
    And the "aria-expanded" attribute of ".snap-courseindex-sidebar-button" "css_element" should contain "true"
    And the page should meet accessibility standards
    And I click on "#theme_boost-drawers-courseindex [data-action='closedrawer']" "css_element"
    And "#theme_boost-drawers-courseindex" "css_element" should not be visible
    And the focused element is ".snap-courseindex-sidebar-button" "css_element"
    And the "aria-expanded" attribute of ".snap-courseindex-sidebar-button" "css_element" should contain "false"

  @javascript @accessibility
  Scenario: On a phone, Contents opens from the sidebar on an activity page
    Given I change viewport size to "600x740"
    And I log in as "student1"
    When I am on the "Test assignment" "assign activity" page
    Then ".drawer-toggles .drawer-left-toggle" "css_element" should not be visible
    And I click on ".snap-courseindex-sidebar-button" "css_element"
    And "#theme_boost-drawers-courseindex" "css_element" should be visible
    And the page should meet accessibility standards
    And I click on "#theme_boost-drawers-courseindex [data-action='closedrawer']" "css_element"
    And the focused element is ".snap-courseindex-sidebar-button" "css_element"

  @javascript
  Scenario: On a larger screen, the tab at the left edge stays
    Given I change viewport size to "1366x900"
    And I log in as "student1"
    When I am on the "Test assignment" "assign activity" page
    Then ".drawer-toggles .drawer-left-toggle" "css_element" should be visible
    And ".snap-courseindex-sidebar-button" "css_element" should not be visible

  @javascript @accessibility
  Scenario: The course index search finds an activity on the course page
    Given I change viewport size to "1366x900"
    And I log in as "student1"
    And I am on the course main page for "C1"
    When I set the field "Search contents" to "Reading"
    Then I should see "Reading list" in the "#toc-search-results" "css_element"
    And I should not see "Test assignment" in the "#toc-search-results" "css_element"
    And the page should meet accessibility standards

  @javascript @accessibility
  Scenario: On a phone, a search result on an activity page opens that activity
    Given I change viewport size to "360x740"
    And I log in as "student1"
    And I am on the "Test assignment" "assign activity" page
    And I click on ".snap-courseindex-sidebar-button" "css_element"
    When I set the field "Search contents" to "Reading"
    Then I should see "Reading list" in the "#toc-search-results" "css_element"
    And the page should meet accessibility standards
    And I click on "Reading list" "link" in the "#toc-search-results" "css_element"
    And I should see "Reading list" in the "#region-main" "css_element"
    And I should not see "Test assignment" in the "#region-main" "css_element"
