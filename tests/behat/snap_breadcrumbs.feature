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
# Tests for toggle course section visibility in non edit mode in snap.
#
# @package    theme_snap
# @author     Diego Casas
# @copyright  Copyright (c) 2019 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@theme @theme_snap @theme_snap_breadcrumbs
Feature: Check that the breadcrumbs are being shown correctly.
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                 |
      | teacher1 | Teacher   | 1        | teacher1@example.com  |
      | student1 | Student   | 1        | student1@example.com  |
    And the following "courses" exist:
      | fullname | shortname  | category  |
      | Course 1 | C1         | 0         |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | admin    | C1 | manager |

  @javascript
  Scenario Outline: Breadcrumbs start at My Courses, without Home or Dashboard, whatever the default home page
    Given the following config values are set as admin:
      | defaulthomepage | <defaulthomepage> |
    And I log in as "<user>"
    And I am on the course main page for "C1"
    Then "My Courses" "link" should exist in the ".breadcrumb li.snap-crumb-mycourses:first-child" "css_element"
    And "C1" "link" should exist in the ".breadcrumb li.snap-crumb-course" "css_element"
    And I should not see "Home" in the ".breadcrumb" "css_element"
    And I should not see "Dashboard" in the ".breadcrumb" "css_element"
    Examples:
      | user     | defaulthomepage |
      | admin    | 0               |
      | teacher1 | 0               |
      | student1 | 0               |
      | admin    | 1               |
      | student1 | 1               |
      | admin    | 2               |
      | student1 | 2               |
