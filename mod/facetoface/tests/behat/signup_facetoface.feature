@mod @mod_facetoface @totara @totara_reportbuilder @javascript
Feature: Sign up to a seminar
  In order to attend a seminar
  As a student
  I need to sign up to a seminar session

  # This background requires JS as such it has been added to the Feature tags.
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
      | student1 | Sam1      | Student1 | student1@moodle.com |
      | student2 | Sam2      | Student2 | student2@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Label" to section "1" and I fill the form with:
      | Label text | Course view page |
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit date" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I set the following fields to these values:
      | capacity              | 1    |
    And I press "Save changes"
    And I log out

  Scenario: Sign up to a session and unable to sign up to a full session from the course page
    When I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I press "Sign-up"
    And I should see "Your booking has been completed."
    # Check the user is back on the course page.
    And I should see "Course view page"
    And I should not see "All events in Test seminar name"
    And I log out
    And I log in as "student2"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I should not see "Sign-up"

  Scenario: Sign up to a session and unable to sign up to a full session for within the activity
    When I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I should see "Test seminar name"
    And I follow "Test seminar name"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I press "Sign-up"
    And I should see "Your booking has been completed."
    # Check the user is back on the all events page.
    And I should not see "Course view page"
    And I should see "All events in Test seminar name"
    And I log out
    And I log in as "student2"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I should not see "Sign-up"

  Scenario: Sign up with note and manage it by Editing Teacher
    When I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I set the following fields to these values:
     | Requests for session organiser | My test |
    And I press "Sign-up"
    And I should see "Your booking has been completed."
    And I log out

    And I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Attendees"
    When I click on "Edit" "link" in the "Sam1" "table_row"
    Then I should see "Sam1 Student1 - update note"

  Scenario: Sign up with note and ensure that other reports do not have manage button
    When I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I set the following fields to these values:
     | Requests for session organiser | My test |
    And I press "Sign-up"
    And I should see "Your booking has been completed."
    And I log out

    And I log in as "admin"
    And I navigate to "Manage reports" node in "Site administration > Reports > Report builder"
    And I set the following fields to these values:
      | Report Name | Other sign-ups   |
      | Source      | Seminar Sign-ups |
    And I press "Create report"
    And I click on "Columns" "link"
    And I set the field "newcolumns" to "All sign up custom fields"
    And I press "Add"
    And I press "Save changes"
    And I click on "Reports" in the totara menu
    When I click on "Other sign-ups" "link"
    Then I should not see "edit" in the "Sam1 Student1" "table_row"