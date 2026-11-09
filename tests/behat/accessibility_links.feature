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
# Tests that links in text stand apart from the text around them by more than colour.
#
# @package    theme_snap
# @copyright  2026 PickledKitty
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_ax
Feature: Links in text are underlined in both colour modes
  In order to find the links in a paragraph without telling colours apart
  As a user
  I need links in text to be underlined, in light and dark mode

  Background:
    Given the following config values are set as admin:
      | enablecolourmodes | 1 | theme_boost |

  @javascript @accessibility
  Scenario Outline: The My courses message with no courses has its links underlined in <mode> mode
    # With no courses on the site, an admin gets "Need help getting started? Check out the Moodle documentation or
    # take your first steps with our Quickstart guide."
    Given the following "user preferences" exist:
      | user  | preference             | value  |
      | admin | theme_boost_colourmode | <mode> |
    When I log in as "admin"
    And I visit "/my/courses.php"
    Then I should see "Quickstart guide" in the "#region-main" "css_element"
    And the "#region-main" "css_element" should meet accessibility standards
    Examples:
      | mode  |
      | light |
      | dark  |

  @javascript @accessibility
  Scenario Outline: A link in a course's text is underlined in <mode> mode
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
      | activity | name       | intro                                                                       | course | section |
      | label    | Text label | <p>Before you start, read the <a href="https://moodle.org/">course guide</a> first.</p> | C1     | 0       |
    And the following "user preferences" exist:
      | user     | preference             | value  |
      | student1 | theme_boost_colourmode | <mode> |
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "course guide" in the "#region-main" "css_element"
    And the "#region-main" "css_element" should meet accessibility standards
    Examples:
      | mode  |
      | light |
      | dark  |
