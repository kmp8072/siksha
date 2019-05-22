@totara_hierarchy @totara
Feature: The generators create the expected position framework

  Scenario: A position item can be updated
    Given I am on a totara site
    And the following "position" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test position framework   | FW001    | Framework description |
    And the following "position" hierarchy exists:
      | framework | fullname          | idnumber |
      | FW001     | First position    | POS001   |
    When I log in as "admin"
    And I navigate to "Manage positions" node in "Site administration > Hierarchies > Positions"
    And I follow "Test position framework"
    And I click on "Edit" "link" in the "First position" "table_row"
    Then the following fields match these values:
      | Name               | First position          |
      | Position ID number | POS001                  |
    And I set the following fields to these values:
      | Name               | Second position           |
      | Position ID number | POS002                    |
      | Description        | This is a second position |
    And I click on "Save changes" "button"
    And I should see "This is a second position" in the ".dl-horizontal" "css_element"


