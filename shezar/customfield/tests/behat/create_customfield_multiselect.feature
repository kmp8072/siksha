@totara @totara_customfield
Feature: Administrators can add a custom multi-select field to complete during course creation
  In order for the custom field to appear during course creation
  As admin
  I need to select the multi-select custom field and add the relevant settings

  @javascript
  Scenario: Create a custom multi-select
    Given I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Multi-select"
    Then I should see "Editing custom field: Multi-select"
    And "multiselectitem[3][option]" "field" should not be visible

    When I click on "Add another option" "link"
    Then "multiselectitem[3][option]" "field" should be visible

    When I set the following fields to these values:
      | fullname                    | Custom Multi-Select Field |
      | shortname                   | custom_multiselect        |
      | multiselectitem[0][option]  | Option One                |
      | multiselectitem[1][option]  | Option Two                |
      | multiselectitem[2][option]  | Option Three              |
      | multiselectitem[3][option]  | Option Four               |
    And I click on "Make selected by default" "link" in the "#fgroup_id_multiselectitem_2" "css_element"
    And I click on "Delete" "link" in the "#fgroup_id_multiselectitem_1" "css_element"
    And I press "Save changes"
    Then I should see "Custom Multi-Select Field"

    When I go to the courses management page
    And I click on "Create new course" "link"
    Then I should see "Add a new course"

    When I expand all fieldsets
    Then I should see "Custom Multi-Select Field"
    And I should see "Option One"
    And I should not see "Option Two"
    And I should see "Option Three"
    And I should see "Option Four"
    And the following fields match these values:
      | customfield_custommultiselect[0] | 0 |
      | customfield_custommultiselect[1] | 1 |
      | customfield_custommultiselect[2] | 0 |

    When I set the following fields to these values:
      | fullname                          | Course One |
      | shortname                         | course1    |
      | customfield_custommultiselect[0]  | 1          |
    And I press "Save and display"
    Then I should see "Course One" in the page title

    When I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the following fields match these values:
      | customfield_custommultiselect[0] | 1 |
      | customfield_custommultiselect[1] | 1 |
      | customfield_custommultiselect[2] | 0 |

    When I set the following fields to these values:
      | customfield_custommultiselect[0] | 0 |
      | customfield_custommultiselect[1] | 0 |
      | customfield_custommultiselect[2] | 1 |
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the following fields match these values:
      | customfield_custommultiselect[0] | 0 |
      | customfield_custommultiselect[1] | 0 |
      | customfield_custommultiselect[2] | 1 |
