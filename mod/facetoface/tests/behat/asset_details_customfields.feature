@mod @mod_facetoface @totara @javascript
Feature: Check asset details with all possible custom fields
  In order to test asset details page
  As a site manager
  I need to create an event and asset, add custom fields, login as admin and check asset details page

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "Asset" "link"

    And I click on "Checkbox" "option"
    And I set the following fields to these values:
      | Full name                   | Asset checkbox |
      | Short name (must be unique) | checkbox       |
    And I press "Save changes"

    And I click on "Date/time" "option"
    And I set the following fields to these values:
      | Full name                   | Asset date/time |
      | Short name (must be unique) | datetime        |
      | Include time?               | 1               |
    And I press "Save changes"

    And I click on "File" "option"
    And I set the following fields to these values:
      | Full name                   | Asset file |
      | Short name (must be unique) | file       |
    And I press "Save changes"

    And I click on "Location" "option"
    And I set the following fields to these values:
      | Full name                   | Asset location |
      | Short name (must be unique) | location       |
    And I press "Save changes"

    And I click on "Menu of choices" "option"
    And I set the following fields to these values:
      | Full name                   | Asset menu of choices |
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
      | Full name                   | Asset multi select |
      | Short name (must be unique) | multiselect        |
      | multiselectitem[0][option]  | Tui                |
      | multiselectitem[1][option]  | Moa                |
      | multiselectitem[2][option]  | Tuatara            |
    And I press "Save changes"

    And I click on "Text area" "option"
    And I set the following fields to these values:
      | Full name                   | Asset text area |
      | Short name (must be unique) | textarea        |
    And I press "Save changes"

    And I click on "Text input" "option"
    And I set the following fields to these values:
      | Full name                   | Asset text input |
      | Short name (must be unique) | textinput        |
    And I press "Save changes"

    And I click on "URL" "option"
    And I set the following fields to these values:
      | Full name                   | Asset address |
      | Short name (must be unique) | url           |
    And I press "Save changes"

    And I navigate to "Assets" node in "Site administration > Seminars"
    And I press "Add a new asset"
    And I set the following fields to these values:
      | Asset name        | Asset 1         |
      | Asset checkbox    | 1               |
      | Asset menu of choices | Orange      |
      | Asset text area       | Lorem ipsum dolor sit amet, consectetur adipisicing elit |
      | Asset text input      | Duis aute irure dolor in reprehenderit in voluptate      |
      | customfield_datetime[day]     | 2    |
      | customfield_datetime[month]   | 3    |
      | customfield_datetime[year]    | 2020 |
      | customfield_datetime[hour]    | 10   |
      | customfield_datetime[minute]  | 30   |
      | customfield_datetime[enabled] | 1    |
      | customfield_multiselect[2]    | 1    |
      | customfield_url[url]          | http://totaralearning.com |
      | customfield_url[text]         | Totara LMS                |
    And I set the field "Address" to multiline
      """
      Level 8, Totara
      Catalyst House
      150 Willis street
      Te Aro
      Wellington 6011
      """
    And I upload "mod/facetoface/tests/fixtures/test.jpg" file to "Asset file" filemanager
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_both" "css_element"
    And I press "Add an asset"

  Scenario: View asset details page and check all custom fields are properly displayed.
    When I click on "Details" "link"
    Then I should see "View asset"
    And I should see "Asset 1"
    And I should see "150 Willis street"
    # "Yes" for checkbox
    And I should see "Yes"
    And I should see "Monday, 2 March 2020, 10:30 AM"
    And I should see "test.jpg"
    And I should see "Orange"
    And I should see "Tuatara"
    And I should see "Lorem ipsum dolor sit amet, consectetur adipisicing elit"
    And I should see "Duis aute irure dolor in reprehenderit in voluptate"
    And I should see "Totara LMS"
