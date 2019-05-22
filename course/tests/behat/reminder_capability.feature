@totara @totara_course
Feature: Verify course reminder capability.
Background:
    Given I am on a totara site
    And the following "users" exist:
        | username | firstname | lastname | email                |
        | student1 | Student1  | Student1 | student1@example.com |
        | student2 | Student2  | Student2 | student2@example.com |
        | manager1 | Manager1  | Manager1 | manager1@example.com |
    And the following "courses" exist:
        | fullname | shortname | format |
        | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
        | user      | course | role    |
        | student1  | C1     | student |
        | student2  | C1     | student |
    And the following "system role assigns" exist:
        | user     | role    |
        | manager1 | manager |
    And I log in as "admin"
    And I navigate to "Manage activities" node in "Site administration > Plugins > Activity modules"
    And I click on "Show Feedback" "link"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Feedback" to section "1" and I fill the form with:
        | Name        | Test Feedback             |
        | Description | Test Feedback description |
    And I log out

@javascript
Scenario: Verify an admin user can access Reminders.

    Given I log in as "admin"
    When I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I navigate to "Reminders" node in "Course administration"
    Then I should see "Edit course reminders"
    And I log out

@javascript
Scenario: Verify a Site Manager can access Reminders.

    Given I log in as "manager1"
    When I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I navigate to "Reminders" node in "Course administration"
    Then I should see "Edit course reminders"
    And I log out

@javascript
Scenario: Verify a Site Manager cannot access Reminders when access is removed.

  Given I log in as "manager1"
  When I set the following system permissions of "Site Manager" role:
    | capability                    | permission |
    | moodle/course:managereminders | Prevent    |
  And I click on "Find Learning" in the totara menu
  And I follow "Course 1"
  Then I should not see "Reminders"
  And I log out
