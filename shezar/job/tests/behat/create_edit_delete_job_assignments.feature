@totara @totara_job
Feature: Test job assignments can be created, edited, and deleted
  In order to test that job assignments can be sorted
  As an admin
  I create several job assignments and change their sort order

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                   |
      | user1    | User      | One      | user1@example.com       |

  @javascript
  Scenario: Create, edit, and delete job assignments
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "User One" "link" in the "User One" "table_row"
    Then I should see "User One"
    And I should see "Job assignments"
    And there should be "0" totara job assignments
    And I should see "This user has no job assignments"
    And I should see "Add job assignment"

    When I follow "Add job assignment"
    And I set the following fields to these values:
      | Full name    | Assignment 1  |
      | Short name   | Assign 1      |
      | ID Number    | A1            |
    And I press "Add job assignment"
    Then I should see "Job assignments"
    And there should be "1" totara job assignments
    And I should not see "This user has no job assignments"
    And I should see "Add job assignment"

    When I follow "Add job assignment"
    And I set the following fields to these values:
      | Full name   | Assignment 2  |
      | Short name  | Assign 2      |
      | ID Number   | A2            |
    And I press "Add job assignment"
    Then I should see "Job assignments"
    And there should be "2" totara job assignments
    And I should see "Add job assignment"

    When I follow "Add job assignment"
    And I set the following fields to these values:
      | Full name   | Health & Safety lead |
      | Short name  | H&S lead             |
      | ID Number   | A4                   |
    And I press "Add job assignment"
    Then I should see "Job assignments"
    And there should be "3" totara job assignments
    And job assignment at position "1" should be "Assignment 1"
    And job assignment at position "2" should be "Assignment 2"
    And job assignment at position "3" should be "Health & Safety lead"
    And I should be able to sort the "Assignment 2" totara job assignment
    And I should be able to delete the "Assignment 2" totara job assignment

    When I follow "Assignment 2"
    And I set the following fields to these values:
      | Full name   | Developer 1 |
      | Short name  | Dev 1       |
      | ID Number   | D1          |
    And I press "Update job assignment"
    Then I should see "Job assignments"
    And there should be "3" totara job assignments
    And job assignment at position "1" should be "Assignment 1"
    And job assignment at position "2" should be "Developer 1"
    And job assignment at position "3" should be "Health & Safety lead"

    When I follow "Health & Safety lead"
    And I set the following fields to these values:
      | Full name   | Developer 2 |
      | Short name  | Dev 2       |
      | ID Number   | D2          |
    And I press "Update job assignment"
    Then I should see "Job assignments"
    And there should be "3" totara job assignments
    And job assignment at position "1" should be "Assignment 1"
    And job assignment at position "2" should be "Developer 1"
    And job assignment at position "3" should be "Developer 2"

    When I move job assignment "Developer 1" down
    Then there should be "3" totara job assignments
    And job assignment at position "1" should be "Assignment 1"
    And job assignment at position "2" should be "Developer 2"
    And job assignment at position "3" should be "Developer 1"

    When I follow "Developer 2"
    And I set the following fields to these values:
      | Full name   | Assignment 2 |
      | Short name  | Assign 2     |
      | ID Number   | A2           |
    And I press "Update job assignment"
    Then I should see "Job assignments"
    And there should be "3" totara job assignments
    And job assignment at position "1" should be "Assignment 1"
    And job assignment at position "2" should be "Assignment 2"
    And job assignment at position "3" should be "Developer 1"

    When I click the delete icon for the "Assignment 2" job assignment
    And I click on "Delete" "button"
    Then there should be "2" totara job assignments
    And job assignment at position "1" should be "Assignment 1"
    And job assignment at position "2" should be "Developer 1"

    When I click the delete icon for the "Assignment 1" job assignment
    And I click on "Delete" "button"
    Then there should be "1" totara job assignments
    And job assignment at position "1" should be "Developer 1"

    When I click the delete icon for the "Developer 1" job assignment
    And I click on "Delete" "button"
    Then there should be "0" totara job assignments
    And I should see "This user has no job assignments"

    @javascript
    Scenario: A learner cannot edit sort or delete their job assignments
      When I log in as "admin"
      And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
      And I click on "User One" "link" in the "User One" "table_row"
      Then I should see "User One"
      And I should see "Job assignments"
      And there should be "0" totara job assignments
      And I should see "This user has no job assignments"
      And I should see "Add job assignment"

      When I follow "Add job assignment"
      And I set the following fields to these values:
        | Full name    | Assignment 1  |
        | Short name   | Assign 1      |
        | ID Number    | A1            |
      And I press "Add job assignment"
      Then I should see "Job assignments"
      And there should be "1" totara job assignments
      And I should not see "This user has no job assignments"
      And I should see "Add job assignment"

      When I follow "Add job assignment"
      And I set the following fields to these values:
        | Full name   | Assignment 2  |
        | Short name  | Assign 2      |
        | ID Number   | A2            |
      And I press "Add job assignment"
      Then I should see "Job assignments"
      And there should be "2" totara job assignments
      And I should see "Add job assignment"

      When I follow "Add job assignment"
      And I set the following fields to these values:
        | ID Number   | H&SLEAD |
      And I press "Add job assignment"
      Then I should see "Job assignments"
      And there should be "3" totara job assignments
      And job assignment at position "1" should be "Assignment 1"
      And job assignment at position "2" should be "Assignment 2"
      And job assignment at position "3" should be "Unnamed job assignment (ID: H&SLEAD)"

      When I log out
      And I log in as "user1"
      And I follow "Profile" in the user menu
      And I should not be able to sort the "Assignment 2" totara job assignment
      And I should not be able to delete the "Assignment 2" totara job assignment
