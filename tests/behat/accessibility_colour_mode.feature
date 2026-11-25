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
# Tests for Snap in the light and dark colour modes.
#
# @package    theme_snap
# @copyright  2026 PickledKitty
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_ax
Feature: Snap in the light and dark colour modes
  In order to use the site in either colour mode
  As a user
  I need Snap to switch colour modes from its sidebar and stay accessible in both

  The light examples are the baseline: each pair checks the same page, so a failure only in the dark example
  comes from dark mode rather than from the page itself.

  Background:
    Given the following config values are set as admin:
      | enablecolourmodes | 1 | theme_boost |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | initsections |
      | Course 1 | C1        | topics | 1            |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | name            | intro                     | course | idnumber | section |
      | page     | Test page       | A page to read            | C1     | page1    | 1       |
      | assign   | Test assignment | An assignment to hand in  | C1     | assign1  | 1       |

  @javascript
  Scenario: A user can switch to dark mode from the Snap sidebar
    Given the following "user preferences" exist:
      | user     | preference             | value |
      | student1 | theme_boost_colourmode | light |
    And I log in as "student1"
    And the "data-bs-theme" attribute of "html" "css_element" should contain "light"
    When I click on "Colour mode: Light" "button" in the "#snap-sidebar-menu" "css_element"
    And I click on "Dark" "button" in the "#colourmode-action-menu" "css_element"
    Then the "data-bs-theme" attribute of "html" "css_element" should contain "dark"
    And the focused element is "#colourmode-menu-toggle" "css_element"
    And I am on site homepage
    And the "data-bs-theme" attribute of "html" "css_element" should contain "dark"

  @javascript @accessibility
  Scenario Outline: The dashboard meets accessibility standards in <mode> mode
    Given the following "user preferences" exist:
      | user     | preference             | value  |
      | student1 | theme_boost_colourmode | <mode> |
    When I log in as "student1"
    And I am on homepage
    Then the "data-bs-theme" attribute of "html" "css_element" should contain "<mode>"
    And the page should meet accessibility standards

    Examples:
      | mode  |
      | light |
      | dark  |

  @javascript @accessibility
  Scenario Outline: A course section meets accessibility standards in <mode> mode
    Given the following "user preferences" exist:
      | user     | preference             | value  |
      | student1 | theme_boost_colourmode | <mode> |
    And I log in as "student1"
    When I am on the course main page for "C1"
    And I go to section 1 of course "C1"
    Then the "data-bs-theme" attribute of "html" "css_element" should contain "<mode>"
    And the page should meet accessibility standards

    Examples:
      | mode  |
      | light |
      | dark  |

  @javascript @accessibility
  Scenario Outline: Activity pages meet accessibility standards in <mode> mode
    Given the following "user preferences" exist:
      | user     | preference             | value  |
      | student1 | theme_boost_colourmode | <mode> |
    And I log in as "student1"
    When I am on the "Test page" "page activity" page
    Then the "data-bs-theme" attribute of "html" "css_element" should contain "<mode>"
    And the page should meet accessibility standards
    And I am on the "Test assignment" "assign activity" page
    And the page should meet accessibility standards

    Examples:
      | mode  |
      | light |
      | dark  |

  @javascript @accessibility
  Scenario Outline: The calendar's key, a wiki's version comparison and a workshop's allocation meet accessibility standards in <mode> mode
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "user preferences" exist:
      | user     | preference             | value    |
      | teacher1 | theme_boost_colourmode | <mode>   |
      | teacher1 | htmleditor             | textarea |
    And the following "activities" exist:
      | activity | name       | course | idnumber | firstpagetitle | wikimode      |
      | wiki     | Test wiki  | C1     | wiki1    | First page     | collaborative |
      | workshop | Workshop 1 | C1     | ws1      |                |               |
    And the following "events" exist:
      | name          | eventtype | course |
      | Course event  | course    | C1     |
    And I change window size to "large"
    And I log in as "teacher1"
    # The key under the month: each event type's eye icon sits on that type's colour.
    When I am viewing site calendar
    Then the "data-bs-theme" attribute of "html" "css_element" should contain "<mode>"
    And ".calendar_filters span.calendar_event_course .icon" "css_element" should exist
    And the page should meet accessibility standards
    # Comparing two versions of a wiki page shows the added and deleted words in colour. In a narrow window, where
    # the previous/next bar isn't pinned: the driver scrolls Save only to the window's edge, under the pinned bar.
    And I change window size to "mobile"
    And I am on the "Test wiki" "wiki activity" page
    And I press "Create page"
    And I set the following fields to these values:
      | HTML format | First edition |
    And I press "Save"
    And I select "Edit" from the "jump" singleselect
    And I set the following fields to these values:
      | HTML format | Second edition |
    And I press "Save"
    And I select "History" from the "jump" singleselect
    And I click on "comparewith" "radio" in the "1" "table_row"
    And I click on "compare" "radio" in the "2" "table_row"
    And I press "Compare selected"
    And ".ouw_added" "css_element" should exist
    # The AA rules, contrast among them, but not the A ones: core's wiki links the older version's author picture twice,
    # one link inside the other, so the outer link has no name (mod/wiki/renderer.php, the same in Boost).
    And the ".wiki-diff-container" "css_element" should meet "wcag2aa, wcag21aa" accessibility standards
    # The manual allocation's grey notes beside each participant.
    And I change window size to "large"
    And I am on the "Workshop 1" "workshop activity" page
    And I follow "Allocate submissions"
    And ".manual-allocator .allocations .info" "css_element" should exist
    # The table only: core's allocation method menu above it has no label (mod/workshop/classes/output/actionbar.php,
    # the same in Boost).
    And the ".manual-allocator" "css_element" should meet accessibility standards

    Examples:
      | mode  |
      | light |
      | dark  |
