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
