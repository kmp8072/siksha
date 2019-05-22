@totara @block @block_current_learning
Feature: Test Current Learning block

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | firstname1 | lastname1 | learner1@example.com |

  Scenario: Learner has Current Learning block on Dashboard by default
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    Then I should see "You do not have any current learning. For previously completed learning see your Record of Learning"

  @javascript
  Scenario: Learner can remove and readd Current Learning block on Dashboard
    Given the following "programs" exist in "totara_program" plugin:
      | fullname                | shortname |
      | Test Program 1          | program1  |
    And the following "program assignments" exist in "totara_program" plugin:
      | user      | program  |
      | learner1  | program1 |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | course1   | 1                |
      | Course 2 | course2   | 1                |
      | Course 3 | course3   | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | learner1 | course1| student        |
    And I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Test Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Content" "link"
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I log out

    And I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    When I click on "Actions" "link" in the "Current Learning" "block"
    And I follow "Delete Current Learning block"
    When I press "Yes"
    And I add the "Current Learning" block
    And I configure the "Current Learning" block
    And I expand all fieldsets
    And I set the following fields to these values:
      | Default region | content |
      | Default weight | -10     |
    And I press "Save changes"
    Then I should see "Course 1" in the "Current Learning" "block"
    And I should see "Test Program 1" in the "Current Learning" "block"

  @javascript
  Scenario: Learner expands accordian for a program within the Current Learning block
    Given the following "programs" exist in "totara_program" plugin:
      | fullname                | shortname |
      | Test Program 1          | program1  |
    And the following "program assignments" exist in "totara_program" plugin:
      | user  | program  |
      | learner1 | program1 |

