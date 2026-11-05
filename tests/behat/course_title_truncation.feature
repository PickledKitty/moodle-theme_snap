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
# Tests that long course/category titles are truncated with ellipsis in the Snap header
# and breadcrumb across course, section, activity and category views.
#
# @package    theme_snap
# @author     Fabian Batioja
# @copyright  Copyright (c) 2026 Open LMS (https://www.openlms.net)
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@javascript @theme @theme_snap @theme_snap_course_title_truncation
Feature: Long course and category titles and breadcrumbs in the Snap header are never cut off without a way to see them

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "categories" exist:
      | name | idnumber |
      | Advanced Systematic Methodologies for Strategic Team Composition through Quantitative Mitigation of Type-Specific Deficiencies, Empirical Validation of Species-Specific Calibration Profiles and Practical Application of Defensive Pivoting Theory | LONGCAT |
    And the following "courses" exist:
      | fullname | shortname | format | category |
      | MSc 601: Advanced Systematic Methodologies for Strategic Team Composition through the Quantitative Mitigation of Type-Specific Deficiencies, the Empirical Validation of Species-Specific Calibration Profiles, and the Practical Application of Defensive Pivoting Theory within High-Intensity, Multimodal Competitive Ecological Environments, Incorporating Cross-Domain Synthesis, Longitudinal Performance Assessment, and Evidence-Based Decision Frameworks for Adaptive Resource Allocation | MSc601-AdvSystematic-StratTeamComp-QuantMitig-TypeSpecific-EmpValSpecific-DefPivoting | topics | LONGCAT |
    And the following "course enrolments" exist:
      | user     | course                                                                                | role           |
      | teacher1 | MSc601-AdvSystematic-StratTeamComp-QuantMitig-TypeSpecific-EmpValSpecific-DefPivoting | editingteacher |
    And the following "activities" exist:
      | activity | name             | course                                                                                | idnumber |
      | forum    | Long Title Forum | MSc601-AdvSystematic-StratTeamComp-QuantMitig-TypeSpecific-EmpValSpecific-DefPivoting | forum1   |
    And the following config values are set as admin:
      | theme | snap |

  Scenario: A long course title is cut to two lines with a button to show it all, and the breadcrumb is shown in full (desktop)
    Given I log in as "admin"
    And I am on "MSc601-AdvSystematic-StratTeamComp-QuantMitig-TypeSpecific-EmpValSpecific-DefPivoting" course homepage
    And I should not see cover image in page header
    Then the element "#page-title #page-mast h1" should have clipped content
    And "#page-title h1.snap-title-long" "css_element" should exist
    And the element "#page-title" should fit its width
    And I click on "Show the full title" "button"
    And the element "#page-title #page-mast h1" should not have clipped content
    And I am on the "Long Title Forum" "forum activity" page
    And the element "#page-title #page-mast h1" should have clipped content
    And the element ".breadcrumb-nav" should fit its width
    And the element ".breadcrumb-nav a[href*='/course/view.php']" should fit its width
    And I am on "MSc601-AdvSystematic-StratTeamComp-QuantMitig-TypeSpecific-EmpValSpecific-DefPivoting" course homepage
    And I upload cover image "bpd_bikes_1280px.jpg"
    And I should see cover image in page header
    And the element "#page-title #page-mast h1" should have clipped content

  Scenario: On a phone a long course title is cut to two lines with a button to show it all, and nothing scrolls sideways
    Given I log in as "teacher1"
    And I change window size to "mobile"
    And I am on "MSc601-AdvSystematic-StratTeamComp-QuantMitig-TypeSpecific-EmpValSpecific-DefPivoting" course homepage
    Then the element "#page-title #page-mast h1" should have clipped content
    And I click on "Show the full title" "button"
    And the element "#page-title #page-mast h1" should not have clipped content
    And the element ".breadcrumb-nav" should fit its width
    And I am on the "Long Title Forum" "forum activity" page
    And the element ".breadcrumb-nav" should fit its width
    And the element ".breadcrumb-nav a[href*='/course/view.php']" should fit its width

  Scenario: A long category title is shown in full on the header band, with and without a cover image (desktop)
    Given I log in as "admin"
    And I am on the course category page for category with idnumber "LONGCAT"
    And I should not see cover image in page header
    Then the element "#page-header #page-mast h1" should not have clipped content
    And the element "#page-header" should fit its width
    And the "title" attribute of "#page-mast h1" "css_element" should contain "Advanced Systematic Methodologies"
    And I upload cover image "bpd_bikes_1280px.jpg"
    And I should see cover image in page header
    And the element "#page-header #page-mast h1" should not have clipped content
    And the "title" attribute of "#page-mast h1" "css_element" should contain "Advanced Systematic Methodologies"
