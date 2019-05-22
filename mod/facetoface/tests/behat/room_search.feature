@mod @mod_facetoface @totara @javascript
Feature: Search pre-defined rooms in seminar
  In order to test seminar room search
  As a site manager
  I need to create the rooms and search in the room search dialog box

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 1          |
      | Building          | That house      |
      | Address           | 123 here street |
      | Maximum bookings  | 5               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 2          |
      | Building          | Your house      |
      | Address           | 123 near street |
      | Maximum bookings  | 6               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

  Scenario: Try and search a room in seminar
    Given I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Select room" "link"
    And I click on "Search" "link" in the "Choose a room" "totaradialogue"

    And I set the field "id_query" to "Room 1"
    When I click on "Search" "button" in the "Choose a room" "totaradialogue"
    Then I should see "Room 1 (Capacity: 5)"
    And I should not see "Room 2 (Capacity: 6)"

    And I set the field "id_query" to "Room 2"
    When I click on "Search" "button" in the "Choose a room" "totaradialogue"
    Then I should see "Room 2 (Capacity: 6)"
    And I should not see "Room 1 (Capacity: 5)"

    And I set the field "id_query" to "Room"
    When I click on "Search" "button" in the "Choose a room" "totaradialogue"
    Then I should see "Room 1 (Capacity: 5)"
    And I should see "Room 2 (Capacity: 6)"

    And I click on "Room 1 (Capacity: 5)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"
