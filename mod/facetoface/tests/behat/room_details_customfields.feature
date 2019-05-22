@mod @mod_facetoface @totara @javascript
Feature: Check room details with all possible custom fields
  In order to test room details page
  As a site manager
  I need to create an event and room, add custom fields, login as admin and check room details page

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name            | course | idnumber |
      | facetoface | Seminar TL-9134 | C1     | seminar  |
    And I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "Room" "link"

    And I click on "Checkbox" "option"
    And I set the following fields to these values:
      | Full name                   | Event checkbox |
      | Short name (must be unique) | checkbox       |
    And I press "Save changes"

    And I click on "Date/time" "option"
    And I set the following fields to these values:
      | Full name                   | Event date/time |
      | Short name (must be unique) | datetime        |
      | Include time?               | 1               |
    And I press "Save changes"

    And I click on "File" "option"
    And I set the following fields to these values:
      | Full name                   | Event file |
      | Short name (must be unique) | file       |
    And I press "Save changes"

    And I click on "Menu of choices" "option"
    And I set the following fields to these values:
      | Full name                   | Event menu of choices |
      | Short name (must be unique) | menuofchoices         |
    And I set the field "Menu options (one per line)" to multiline
      """
      Apple
      Orange
      Banana
      """
    And I press "Save changes"

    And I click on "Multi-select" "option"
    And I set the following fields to these values:
      | Full name                   | Event multi select |
      | Short name (must be unique) | multiselect        |
      | multiselectitem[0][option]  | Tui                |
      | multiselectitem[1][option]  | Moa                |
      | multiselectitem[2][option]  | Tuatara            |
    And I press "Save changes"

    And I click on "Text area" "option"
    And I set the following fields to these values:
      | Full name                   | Event text area |
      | Short name (must be unique) | textarea        |
    And I press "Save changes"

    And I click on "Text input" "option"
    And I set the following fields to these values:
      | Full name                   | Event text input |
      | Short name (must be unique) | textinput        |
    And I press "Save changes"

    And I click on "URL" "option"
    And I set the following fields to these values:
      | Full name                   | Event address |
      | Short name (must be unique) | url           |
    And I press "Save changes"

    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 1          |
      | Maximum bookings  | 10              |
      | Building          | Building 123    |
      | Address           | 123 Tory street |
      | Event checkbox    | 1               |
      | Event menu of choices | Orange      |
      | Event text area       | Lorem ipsum dolor sit amet, consectetur adipisicing elit |
      | Event text input      | Duis aute irure dolor in reprehenderit in voluptate      |
      | customfield_datetime[day]     | 2    |
      | customfield_datetime[month]   | 3    |
      | customfield_datetime[year]    | 2020 |
      | customfield_datetime[hour]    | 10   |
      | customfield_datetime[minute]  | 30   |
      | customfield_datetime[enabled] | 1    |
      | customfield_multiselect[2]    | 1    |
      | customfield_url[url]          | http://totaralearning.com |
      | customfield_url[text]         | Totara LMS                |
    And I upload "mod/facetoface/tests/fixtures/test.jpg" file to "Event file" filemanager
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Select room" "link"
    And I click on "Room 1, Building 123, 123 Tory street (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"

  Scenario: Login as manager and view room details page and check all custom fields are properly displayed.
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "Room details" "link"
    When I switch to "popup" window
    Then I should see "View room"
    And I should see "Room 1"
    # "Yes" for checkbox
    And I should see "Yes"
    And I should see "Monday, 2 March 2020, 10:30 AM"
    And I should see "test.jpg"
    And I should see "Orange"
    And I should see "Tuatara"
    And I should see "Lorem ipsum dolor sit amet, consectetur adipisicing elit"
    And I should see "Duis aute irure dolor in reprehenderit in voluptate"
    And I should see "Totara LMS"
    And I should see "Upcoming sessions in this room"
    And I should see "Seminar TL-9134"
