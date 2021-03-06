@mod @mod_quiz
Feature: Settings form fields disabled if not required
  In to create quizzes as simply as possible
  As a teacher
  I don't need to to use certain form fields.

  #Totara: the select options must be exact match and the field-sets must be expanded!

  Background:
    Given the following "users" exist:
      | username | firstname |
      | teacher  | Teach     |
      | student1 | Student1  |
      | student2 | Student2  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And I log in as "teacher"
    And I follow "Course 1"
    And I turn editing mode on

  @javascript
  Scenario: Depending on the number of attempts, different form fields are disabled.
    When I add a "Quiz" to section "1"
    And I expand all fieldsets
    And I set the field "Name" to "Test quiz"
    And I set the field "Attempts allowed" to "1"
    Then the "Grading method" "field" should be disabled
    And the "Each attempt builds on the last" "field" should be disabled
    And the "id_delay1_enabled" "field" should be disabled
    And the "id_delay2_enabled" "field" should be disabled

    When I set the field "Attempts allowed" to "2"
    Then the "Grading method" "field" should be enabled
    And the "Each attempt builds on the last" "field" should be enabled
    And the "id_delay1_enabled" "field" should be enabled
    And the "id_delay2_enabled" "field" should be disabled

    When I set the field "Attempts allowed" to "3"
    Then the "Grading method" "field" should be enabled
    And the "Each attempt builds on the last" "field" should be enabled
    And the "id_delay1_enabled" "field" should be enabled
    And the "id_delay2_enabled" "field" should be enabled

    When I set the field "Attempts allowed" to "Unlimited"
    Then the "Grading method" "field" should be enabled
    And the "Each attempt builds on the last" "field" should be enabled
    # And the "id_delay1_enabled" "field" should be enabled
    # And the "id_delay2_enabled" "field" should be enabled

    When I press "Save and display"
    And I navigate to "User overrides" node in "Quiz administration"
    And I press "Add user override"
    And I set the following fields to these values:
        | Override user    | Student1 Lastname2, student1@example.com |
        | Attempts allowed | 3        |
    And I press "Save"
    And I navigate to "Edit settings" node in "Quiz administration"
    And I expand all fieldsets
    And I set the field "Attempts allowed" to "1"
    Then the "Grading method" "field" should be enabled
    And the "Each attempt builds on the last" "field" should be enabled
    And the "id_delay1_enabled" "field" should be enabled
    And the "id_delay2_enabled" "field" should be enabled

    When I press "Save and display"
    And I navigate to "User overrides" node in "Quiz administration"
    And I follow "Edit"
    And I set the field "Attempts allowed" to "2"
    And I press "Save"
    And I navigate to "Edit settings" node in "Quiz administration"
    And I expand all fieldsets
    And I set the field "Attempts allowed" to "1"
    Then the "Grading method" "field" should be enabled
    And the "Each attempt builds on the last" "field" should be enabled
    And the "id_delay1_enabled" "field" should be enabled
    And the "id_delay2_enabled" "field" should be disabled

    When I press "Save and display"
    And I navigate to "User overrides" node in "Quiz administration"
    And I press "Add user override"
    And I set the following fields to these values:
        | Override user    | Student2 Lastname3, student2@example.com |
        | Attempts allowed | Unlimited |
    And I press "Save"
    And I navigate to "Edit settings" node in "Quiz administration"
    And I expand all fieldsets
    And I set the field "Attempts allowed" to "1"
    Then the "Grading method" "field" should be enabled
    And the "Each attempt builds on the last" "field" should be enabled
    And the "id_delay1_enabled" "field" should be enabled
    And the "id_delay2_enabled" "field" should be enabled
