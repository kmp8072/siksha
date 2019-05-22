@totara @totara_core @core_tag @javascript
Feature: Create and edit activity pages handle tags correctly
  In order to tag an activity properly
  As a user
  I need to introduce the tags while editing

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Manage tags" node in "Site administration > Appearance"
    And I set the field "otagsadd" to "Superb, Supreme, Superfluous"
    And I press "Add official tags"
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |


  Scenario: Verify activity tags work as expected
    Given I click on "Find Learning" in the totara menu
    And I click on "Course 1" "link"
    And I press "Turn editing on"
    And I wait until the page is ready
    And I click on "Add an activity or resource" "link"
    And I click on "module_assign" "radio"
    And I press "Add"
    And I set the following fields to these values:
      | Assignment name | Assignment Example                            |
      | Description     | Assignment Description                        |
      | Tags            | Superb, Superfluous, Salacious, Sanctimonious |
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I expand all fieldsets
    Then I should see "Superb" in the "#fitem_id_tags" "css_element"
    And I should see "Superfluous" in the "#fitem_id_tags" "css_element"
    And I should see "Salacious" in the "#fitem_id_tags" "css_element"
    And I should see "Sanctimonious" in the "#fitem_id_tags" "css_element"
    And I should not see "Supreme" in the "#fitem_id_tags" "css_element"

    When I set the following fields to these values:
      | Tags | Newtag |
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I expand all fieldsets
    Then I should see "Superb" in the "#fitem_id_tags" "css_element"
    And I should see "Superfluous" in the "#fitem_id_tags" "css_element"
    And I should see "Salacious" in the "#fitem_id_tags" "css_element"
    And I should see "Sanctimonious" in the "#fitem_id_tags" "css_element"
    And I should see "Newtag" in the "#fitem_id_tags" "css_element"
    And I should not see "Supreme" in the "#fitem_id_tags" "css_element"

