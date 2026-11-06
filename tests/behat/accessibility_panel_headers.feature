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
# Tests for the header bar of the right-hand panels opened from Snap's sidebar.
#
# @package    theme_snap
# @copyright  2026 PickledKitty
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_ax
Feature: Every right-hand panel has a header with its name and its close button
  In order to know which panel is open and close it the same way every time
  As a user
  I need each panel from the sidebar to show its name and one close button in a bar at its top

  @javascript @accessibility
  Scenario Outline: A panel shows its name in its header, focus goes to its close button, and back to its sidebar button
    Given I change window size to "large"
    And I log in as "admin"
    And I am on site homepage
    When I click on "<trigger>" "css_element"
    Then I should see "<title>" in the "<header>" "css_element"
    And the focused element is "<close>" "css_element"
    And the "<header>" "css_element" should meet accessibility standards
    And I click on "<close>" "css_element"
    And the focused element is "<trigger>" "css_element"
    Examples:
      | trigger                         | title          | header                                          | close                                                 |
      | #admin-menu-trigger             | Administration | .block_settings .snap-panel-header              | .block_settings .snap-panel-close-button              |
      | #snap_feeds_side_menu_trigger   | Snap feeds     | #snap_feeds_side_menu .snap-panel-header        | #snap_feeds_side_menu .snap-panel-close-button        |
      | [id^=message-drawer-toggle]     | Messaging      | [data-region=right-hand-drawer] .snap-panel-header | [data-region=right-hand-drawer] .snap-panel-close-button |
      | .blocks-drawer-button           | Blocks         | #theme_snap-drawers-blocks .drawerheader        | #theme_snap-drawers-blocks .drawertoggle              |

  @javascript @accessibility
  Scenario: On a narrow screen, Snap feeds has one header with one close button
    # The narrowest the test browser goes (about 490px). There the test site's own panel rule (top 0) puts the page
    # header over the panel's bar, so it's closed from the keyboard: focus is on its close button once it opens.
    Given I change window size to "mobile"
    And I log in as "admin"
    And I am on site homepage
    When I click on "#snap_feeds_side_menu_trigger" "css_element"
    Then I should see "Snap feeds" in the "#snap_feeds_side_menu .snap-panel-header" "css_element"
    And ".snap-feeds-mobile-menu .btn-close" "css_element" should not exist
    # WCAG 2.1 AA, as required (CLAUDE.md). On the test site axe sees the close button as partly covered (a 2.2
    # target-size check); in a browser at this size it's 32px square and nothing covers it.
    And the "#snap_feeds_side_menu .snap-panel-header" "css_element" should meet "wcag2a, wcag2aa, wcag21a, wcag21aa" accessibility standards
    And the focused element is "#snap_feeds_side_menu .snap-panel-close-button" "css_element"
    And I press enter
    And the focused element is "#snap_feeds_side_menu_trigger" "css_element"
