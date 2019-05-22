@javascript @mod @mod_facetoface @totara
Feature: Manage custom assets by non-admin user
  In order to test that non-admin user
  As a editing teacher
  I need to create and edit custom assets

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  Scenario: Add edit seminar custom asset as editing teacher
    And I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Select assets" "link"
    And I click on "Create new asset" "link"
    And I should see "Create new asset" in the "Create new asset" "totaradialogue"
    And I set the following fields to these values:
      | Asset name                    | Asset 1 |
      | Allow asset booking conflicts | 1       |
      | Asset description | Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. |
    And I should not see "Publish for reuse"
    When I click on "OK" "button" in the "Create new asset" "totaradialogue"
    Then I should see "Asset 1"

    # Edit
    And I click on "Edit asset" "link"
    And I should see "Edit asset" in the "Edit asset" "totaradialogue"
    And I set the following fields to these values:
      | Asset name | Asset updated |
    And I should not see "Publish for reuse"
    When I click on "OK" "button" in the "Edit asset" "totaradialogue"
    Then I should see "Asset updated"