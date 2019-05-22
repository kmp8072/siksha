@totara @totara_reportbuilder @javascript
Feature: Check global report restrictions default settings
  In order to use Global report restrictions
  As a user
  I need to confirm the correct behavior

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable report restrictions | 1 |
    And I navigate to "Manage reports" node in "Site administration > Reports > Report builder"

  Scenario: Check default embeded report status
    And I should see "No" in the "Alerts (View)" "table_row"
    And I should see "No" in the "Appraisal Detail (View)" "table_row"
    And I should see "No" in the "Appraisal Status (View)" "table_row"
    And I should see "No" in the "Audience Admin Screen (View)" "table_row"
    And I should see "No" in the "Audience Orphaned Users (View)" "table_row"
    And I should see "No" in the "Audience members (View)" "table_row"
    And I should see "No" in the "Completion import: Certification status (View)" "table_row"
    And I should see "No" in the "Completion import: Course status (View)" "table_row"
    And I should see "No" in the "Seminars: Declared interest (View)" "table_row"
    And I should see "No" in the "Goal Status (View)" "table_row"
    And I should see "No" in the "Goal Status History (View)" "table_row"
    And I should see "No" in the "Goal Summary (View)" "table_row"
    And I should see "No" in the "Team Members (View)" "table_row"

  Scenario: Check default created report status
    Given I set the following fields to these values:
      | Report Name | Audience report  |
      | Source      | Audience Members |
    And I press "Create report"
    And I switch to "Content" tab
    Then the field "Global report restrictions" matches value "1"
    When I follow "All Reports"
    Then I should see "Yes" in the "Audience report (View)" "table_row"
    When I follow "Audience report"
    And I switch to "Content" tab
    And I set the field "Global report restrictions" to "0"
    And I press "Save changes"
    And I follow "All Reports"
    Then I should see "No" in the "Audience report (View)" "table_row"
