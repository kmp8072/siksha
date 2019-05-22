@totara @totara_hierarchy @javascript
Feature: Company goal asignments
  In order to user personal goal types
  As a user
  I need to be able create personal goals with types

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "goal frameworks" exist in "totara_hierarchy" plugin:
      | fullname       | idnumber |
      | Goal Framework | gframe   |
    And the following "goals" exist in "totara_hierarchy" plugin:
      | fullname | idnumber | goal_framework |
      | Goal One | goal1    | gframe         |
      | Goal Two | goal2    | gframe         |
    When I log in as "admin"
    And I navigate to "Manage goals" node in "Site administration > Hierarchies > Goals"
    Then I should see "Goal Framework"

    When I click on "Goal Framework" "link" in the "#frameworkstable" "css_element"
    Then I should see "Goal One"
    And I should see "Goal Two"

  Scenario: Assign company goals to users via cohort
    Given the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH1    |
      | user2 | CH1    |
    And I follow "Goal One"
    When I set the field "groupselector" to "Add audience(s)"
    And I click on "Cohort 1" "link" in the "Assign group of users" "totaradialogue"
    And I click on "Save" "button" in the "Assign group of users" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Cohort 1" in the "#assignedgroups" "css_element"
    And I log out

    When I log in as "user1"
    And I click on "Goals" in the totara menu
    Then I should see "Goal One" in the "#company_goals_table" "css_element"
    And I log out

    When I log in as "user3"
    And I click on "Goals" in the totara menu
    Then I should not see "Goal One" in the "#company_goals_table" "css_element"