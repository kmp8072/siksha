@totara @totara_program
Feature: Set due date for program assignments
  In order to create a due date for users
  As an admin
  I must be able to add a due date to program assignments

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             | timezone         |
      | user1    | John      | Smith    | user1@example.com | Europe/Rome      |
      | user2    | Mary      | Jones    | user2@example.com | America/New_York |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                | shortname    |
      | Set Due Date Tests      | duedatetest  |
    And the following "cohorts" exist:
      | name      | idnumber | contextlevel | reference |
      | Audience1 | aud1     | System       |           |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | aud1   |
      | user2 | aud1   |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname           | idnumber  |
      | Position Framework | pframe    |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | fullname     | idnumber  | pos_framework |
      | Position One | pos1      | pframe        |
    And I log in as "admin"
    # Get back the removed dashboard item for now.
    And I navigate to "Main menu" node in "Site administration > Appearance"
    And I click on "Edit" "link" in the "Required Learning" "table_row"
    And I set the field "Parent item" to "Top"
    And I press "Save changes"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "Edit" "link" in the "Admin User" "table_row"
    And I select "Europe/Rome" from the "Timezone" singleselect
    And I press "Update profile"
    And I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Set Due Date Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Edit program assignments" "button"

  @javascript
  Scenario: Fixed due dates can be set for individuals
    Given I select "Individuals" from the "category_select_dropdown" singleselect
    And I click on "Add" "button" in the "#category_select" "css_element"
    And I click on "Add individuals to program" "button"
    And I click on "John Smith (user1@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Mary Jones (user2@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "John Smith" "table_row"
    And I set the following fields to these values:
      | completiontime       | 10/12/2015 |
      | completiontimehour   | 15         |
      | completiontimeminute | 45         |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "Mary Jones" "table_row"
    And I set the following fields to these values:
      | completiontime       | 12/12/2015 |
      | completiontimehour   | 02         |
      | completiontimeminute | 20         |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Complete by 10 Dec 2015 at 15:45" in the "John Smith" "table_row"
    And I should see "Complete by 12 Dec 2015 at 02:20" in the "Mary Jones" "table_row"
    When I press "Save changes"
    And I click on "Save all changes" "button" in the "Confirm assignment changes" "totaradialogue"
    Then I should see "10 Dec 2015 at 15:45" in the "John Smith" "table_row"
    And I should see "12 Dec 2015 at 02:20" in the "Mary Jones" "table_row"
    When I click on "Exception Report (2)" "link"
    And I select "All learners" from the "selectiontype" singleselect
    And I select "Override and add program" from the "selectionaction" singleselect
    And I press "Proceed with this action"
    And I click on "OK" "button" in the "Confirm issue resolution" "totaradialogue"
    And I log out
    And I log in as "user1"
    And I click on "Required Learning" in the totara menu
    Then I should see "Due date: 10 December 2015, 3:45 PM"
    When I log out
    And I log in as "user2"
    And I click on "Required Learning" in the totara menu
    Then I should see "Due date: 11 December 2015, 8:20 PM"

  @javascript
  Scenario: Fixed due dates can be set for audiences
    Given I select "Audiences" from the "category_select_dropdown" singleselect
    And I click on "Add" "button" in the "#category_select" "css_element"
    And I click on "Add audiences to program" "button"
    And I click on "Audience1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "Audience1" "table_row"
    And I set the following fields to these values:
      | completiontime       | 09/12/2015 |
      | completiontimehour   | 14         |
      | completiontimeminute | 30         |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Complete by 9 Dec 2015 at 14:30" in the "Audience1" "table_row"
    When I press "Save changes"
    And I click on "Save all changes" "button" in the "Confirm assignment changes" "totaradialogue"
    When I click on "Complete by 9 Dec 2015 at 14:30" "link" in the "Audience1" "table_row"
    Then the following fields match these values:
      | completiontime       | 09/12/2015 |
      | completiontimehour   | 14         |
      | completiontimeminute | 30         |
    And I click on "Cancel" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    When I click on "Exception Report (2)" "link"
    And I select "All learners" from the "selectiontype" singleselect
    And I select "Override and add program" from the "selectionaction" singleselect
    And I press "Proceed with this action"
    And I click on "OK" "button" in the "Confirm issue resolution" "totaradialogue"
    And I log out
    And I log in as "user1"
    And I click on "Programs" in the totara menu
    And I click on "Set Due Date Tests" "link"
    Then I should see "Due date: 09 December 2015, 2:30 PM"
    When I log out
    And I log in as "user2"
    And I click on "Programs" in the totara menu
    And I click on "Set Due Date Tests" "link"
    Then I should see "Due date: 09 December 2015, 8:30 AM"

  @javascript
  Scenario: Relative due dates can be set for individuals
    Given I select "Individuals" from the "category_select_dropdown" singleselect
    And I click on "Add" "button" in the "#category_select" "css_element"
    And I click on "Add individuals to program" "button"
    And I click on "John Smith (user1@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Mary Jones (user2@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "John Smith" "table_row"
    And I set the following fields to these values:
      | timeamount | 4           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "Mary Jones" "table_row"
    And I set the following fields to these values:
      | timeamount | 6                       |
      | timeperiod | Month(s)                |
      | eventtype  | Program enrollment date |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Complete within 4 Week(s) of First login" in the "John Smith" "table_row"
    And I should see "Complete within 6 Month(s) of Program enrollment date" in the "Mary Jones" "table_row"
    And I press "Save changes"
    And I click on "Save all changes" "button" in the "Confirm assignment changes" "totaradialogue"

  @javascript
  Scenario: Relative due dates can be set for audiences
    Given I select "Audiences" from the "category_select_dropdown" singleselect
    And I click on "Add" "button" in the "#category_select" "css_element"
    And I click on "Add audiences to program" "button"
    And I click on "Audience1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "Audience1" "table_row"
    And I set the following fields to these values:
      | timeamount | 2                      |
      | timeperiod | Year(s)                |
      | eventtype  | Position assigned date |
    And I click on "Position One" "link" in the "Choose item" "totaradialogue"
    And I click on "Ok" "button" in the "Choose item" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Complete within 2 Year(s) of being assigned position 'Position One'" in the "Audience1" "table_row"
    And I press "Save changes"
    And I click on "Save all changes" "button" in the "Confirm assignment changes" "totaradialogue"
