@totara @totara_program @javascript
Feature: Specific permissions allow users to manage programs
  As a user with the permissions for managing a program
  I should be able to use the program management tabs
  With permissions in the program context

  Background:
    Given I am on a totara site
    And the following "programs" exist in "totara_program" plugin:
      | fullname    | shortname | idnumber |
      | Program One | prog1     | prog1    |
      | Program Two | prog2     | prog2    |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion |
      | Course One | course1   | topics | 1                |
    And the following "users" exist:
      | username | firstname     | lastname | email                |
      | authuser | Authenticated | User     | authuser@example.com |
      | progman  | Program       | Manager  | progman@example.com  |
    And the following "roles" exist:
      | shortname   |
      | progmanager |
    And the following "role assigns" exist:
      | user    | role        | contextlevel  | reference |
      | progman | progmanager | Program       | prog1     |

  Scenario: An authenticated user without any program management permissions can not edit program details
    Given I log in as "authuser"
    When I click on "Programs" in the totara menu
    And I follow "Program One"
    Then "Edit program details" "button" should not exist
    When I click on "Programs" in the totara menu
    And I follow "Program Two"
    Then "Edit program details" "button" should not exist

  Scenario: totara/program:configuredetails allows a user to edit program details
    Given the following "permission overrides" exist:
      | capability                       | permission | role          | contextlevel | reference |
      | totara/program:configuredetails  | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I click on "Programs" in the totara menu
    And I follow "Program One"
    Then "Edit program details" "button" should be visible
    When I press "Edit program details"
    Then I should not see "Edit program content"
    And I should not see "Edit program assignments"
    And I should not see "Edit program messages"
    When I press "Edit program details"
    And I set the following fields to these values:
      | Full name | Program One New Name |
    And I press "Save changes"
    Then I should see "Program details saved successfully"
    And I should see "Program One New Name"
    When I click on "Programs" in the totara menu
    And I follow "Program Two"
    Then "Edit program details" "button" should not exist

  Scenario: totara/program:configurecontent allows a user to edit program content
    # Users must have configure details to get to the content tab in the interface.
    Given the following "permission overrides" exist:
      | capability                       | permission | role          | contextlevel | reference |
      | totara/program:configuredetails  | Allow      | progmanager   | Program      | prog1     |
      | totara/program:configurecontent  | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I click on "Programs" in the totara menu
    And I follow "Program One"
    And I press "Edit program details"
    Then "Edit program assignments" "button" should not exist
    Then "Edit program messages" "button" should not exist
    When I press "Edit program content"
    And I press "Add"
    And I click on "Miscellaneous" "link" in the "Add course set" "totaradialogue"
    And I click on "Course One" "link" in the "Add course set" "totaradialogue"
    And I click on "Ok" "button" in the "Add course set" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I press "Save all changes"
    Then I should see "Program content saved successfully"
    And I should see "Course One"

  Scenario: totara/program:configuremessages allows a user to edit program messages
  # Users must have configure details to get to the messages tab in the interface.
    Given the following "permission overrides" exist:
      | capability                           | permission | role          | contextlevel | reference |
      | totara/program:configuredetails      | Allow      | progmanager   | Program      | prog1     |
      | totara/program:configuremessages     | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I click on "Programs" in the totara menu
    And I follow "Program One"
    And I press "Edit program details"
    Then "Edit program assignments" "button" should not exist
    Then "Edit program content" "button" should not exist
    When I press "Edit program messages"
    And I set the following fields to these values:
      | Subject | New subject line for Program One |
    And I press "Save changes"
    And I press "Save all changes"
    Then I should see "Program messages saved"
    And the following fields match these values:
      | Subject | New subject line for Program One |

  Scenario: totara/program:configureassignments allows a user to edit program assignments
  # Users must have configure details to get to the assignments tab in the interface.
    Given the following "permission overrides" exist:
      | capability                           | permission | role          | contextlevel | reference |
      | totara/program:configuredetails      | Allow      | progmanager   | Program      | prog1     |
      | totara/program:configureassignments  | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I click on "Programs" in the totara menu
    And I follow "Program One"
    And I press "Edit program details"
    Then "Edit program content" "button" should not exist
    Then "Edit program messages" "button" should not exist
    When I press "Edit program assignments"
    And I set the following fields to these values:
      | Add a new | Individuals |
    And I press "Add"
    And I wait "1" seconds
    And I press "Add individuals to program"
    And I click on "Authenticated User" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I press "Save all changes"
    Then I should see "Program assignments saved successfully"
    And I should see "Authenticated User"

  Scenario: totara/program:configureassignments allows a user to set completion time based on course completion
  # Users must have configure details to get to the assignments tab in the interface.
    Given the following "permission overrides" exist:
      | capability                           | permission | role          | contextlevel | reference |
      | totara/program:configuredetails      | Allow      | progmanager   | Program      | prog1     |
      | totara/program:configureassignments  | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I click on "Programs" in the totara menu
    And I follow "Program One"
    And I press "Edit program details"
    When I press "Edit program assignments"
    And I set the following fields to these values:
      | Add a new | Individuals |
    And I press "Add"
    And I wait "1" seconds
    And I press "Add individuals to program"
    And I click on "Authenticated User" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link"
    And I set the following fields to these values:
      | eventtype | Course completion |
    And I click on "Miscellaneous" "link" in the "Choose item" "totaradialogue"
    And I click on "Course One" "link" in the "Choose item" "totaradialogue"
    And I click on "Ok" "button" in the "Choose item" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I press "Save all changes"
    Then I should see "Program assignments saved successfully"
    And I should see "Authenticated User"
    And I should see "Complete within 1 Day(s) of completion of course 'Course One'"
