@totara @totara_plan @javascript
Feature: Create plan from template.
  In order to create a plan from a template
  As a user
  I need to be able to create and use a plan

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I set the following fields to these values:
      | Name              | test template |
      | id_enddate_month  | December      |
      | id_enddate_day    | 31            |
      | id_enddate_year   | 2020          |
    And I press "Save changes"

  Scenario: Create plan from template
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I press "Create new learning plan"
    When I set the field "Plan template" to "test template"
    Then the following fields match these values:
      | Plan name         | test template |
      | id_enddate_day    | 31            |
      | id_enddate_month  | December      |
      | id_enddate_year   | 2020          |
