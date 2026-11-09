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
# Tests for the blocks panel holding a book's contents on narrow screens.
#
# @package    theme_snap
# @copyright  2026 PickledKitty
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_ax
Feature: On narrow screens a book's contents are in the blocks panel
  In order to find a book's chapters on a phone
  As a student
  I need the panel holding them to say what it holds

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name      | course | idnumber |
      | book     | Test Book | C1     | book1    |
    And the following "mod_book > chapter" exists:
      | book    | Test Book     |
      | title   | First chapter |
      | content | Chapter text  |

  # On test sites Snap adds the Navigation block to every page (config.php, requiredblocks), so here the drawer
  # always holds a real block as well as the contents, and keeps its "Blocks" name. A drawer that holds only a
  # book's or lesson's contents, named after them, is tested in core_renderer_test.

  @javascript @accessibility
  Scenario: With other blocks, the book contents stay a block with their own heading in the Blocks panel
    Given I change window size to "mobile"
    And I log in as "student1"
    And I am on the "Test Book" "book activity" page
    Then the "title" attribute of ".blocks-drawer-button" "css_element" should contain "Toggle block drawer"
    And I click on ".blocks-drawer-button" "css_element"
    And I wait until "#theme_snap-drawers-blocks .drawertoggle" "css_element" is visible
    And I should see "Blocks" in the "#theme_snap-drawers-blocks .snap-panel-title" "css_element"
    And I should see "Table of contents" in the "#theme_snap-drawers-blocks .block_fake" "css_element"
    And I should see "First chapter" in the "#theme_snap-drawers-blocks .block_fake" "css_element"
    And the "#theme_snap-drawers-blocks" "css_element" should meet accessibility standards

