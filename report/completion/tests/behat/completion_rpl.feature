@report @report_completion @totara
Feature: Completion report rpl
  If cousrse completion via RPL is set or removed, the course status needs to be adjusted accordingly

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |  enablecompletion |
      | Course 1 | C1        |  1                |
    And the following "activities" exist:
      | activity   | name              | intro         | course               | idnumber    | completion   |
      | label      | label1            | label1        | C1                   | label1      | 1            |
      | label      | label2            | label2        | C1                   | label2      | 1            |
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "criteria_activity_value[1]" "checkbox"
    And I click on "criteria_activity_value[2]" "checkbox"
    And I press "Save changes"

  @javascript
  Scenario: Course status is set correctly when RPL is set then deleted with course tracking on enrolment and with no learner activities completed
    Given I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I click on "completionstartonenrol" "checkbox"
    And I press "Save and display"
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |

    # Set course completion via RPL
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I complete the course via rpl for "Student 1" with text "Test 1"
    And I log out

    # Check student completion status
    When I log in as "student1"
    And I click on "Record of Learning" in the totara menu
    # Click on the course progress bar to see the completion progress details.
    And I follow "Complete via rpl"
    Then I should see "Complete via rpl"
    And I log out

    # Delete RPL
    When I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I navigate to "Course completion" node in "Course administration > Reports"
    Then I delete the course rpl for "Student 1"
    And I log out

    # Check student completion status
    When I log in as "student1"
    And I click on "Record of Learning" in the totara menu
    # Click on the course progress bar to see the completion progress details.
    And I follow "In progress"
    Then I should see "Not completed"
    And I log out

  @javascript
  Scenario: Course status is set correctly when RPL is set then deleted with course tracking on enrolment and with one learner activity completed
    Given I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I click on "completionstartonenrol" "checkbox"
    And I press "Save and display"
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I log out

    # As a student, complete one activity
    When I log in as "student1"
    And I follow "Course 1"
    Then I click on "Mark as complete: label1" "button"
    And I log out

    # Set course completion via RPL
    When I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I navigate to "Course completion" node in "Course administration > Reports"
    Then I complete the course via rpl for "Student 1" with text "Test 1"
    And I log out

    # Check student completion status
    When I log in as "student1"
    And I click on "Record of Learning" in the totara menu
    And I follow "Complete via rpl"
    Then I should see "Complete via rpl"
    And I log out

    # Delete RPL
    When I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I navigate to "Course completion" node in "Course administration > Reports"
    Then I delete the course rpl for "Student 1"
    And I log out

    # Check student completion status
    When I log in as "student1"
    And I click on "Record of Learning" in the totara menu
    And I follow "In progress"
    Then I should see "Not completed"
    And I log out

  @javascript
  Scenario: Course status is set correctly when RPL is set then deleted with course tracking not set on enrolment and with no learner activities completed
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |

    # Set course completion via RPL
    When I navigate to "Course completion" node in "Course administration > Reports"
    Then I complete the course via rpl for "Student 1" with text "Test 1"
    And I log out

    # Check student completion status
    When I log in as "student1"
    And I click on "Record of Learning" in the totara menu
    And I follow "Complete via rpl"
    Then I should see "Complete via rpl"
    And I log out

    # Delete RPL
    When I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I navigate to "Course completion" node in "Course administration > Reports"
    Then I delete the course rpl for "Student 1"
    And I log out

    # Check student completion status
    When I log in as "student1"
    And I click on "Record of Learning" in the totara menu
    And I follow "In progress"
    Then I should see "Not completed"
    And I log out

  @javascript
  Scenario: Course status is set correctly when RPL is set then deleted with course tracking not set on enrolment and with one learner activity completed
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I log out

    # As a student, complete one activity
    When I log in as "student1"
    And I follow "Course 1"
    Then I click on "Mark as complete: label1" "button"
    And I log out

    # Set course completion via RPL
    When I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I navigate to "Course completion" node in "Course administration > Reports"
    Then I complete the course via rpl for "Student 1" with text "Test 1"
    And I log out

    # Check student completion status
    When I log in as "student1"
    And I click on "Record of Learning" in the totara menu
    And I follow "Complete via rpl"
    Then I should see "Complete via rpl"
    And I log out

    # Delete RPL
    When I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I navigate to "Course completion" node in "Course administration > Reports"
    Then I delete the course rpl for "Student 1"
    And I log out

    # Check student completion status
    When I log in as "student1"
    And I click on "Record of Learning" in the totara menu
    And I follow "In progress"
    Then I should see "Not completed"
    And I log out
