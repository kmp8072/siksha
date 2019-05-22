@totara @totara_appraisal
Feature: Perform basic appraisals adminstration
  In order to ensure the appraisals works as expected
  As an admin
  I need to create calendar data

  @javascript
  Scenario: Create Appraisal
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I press "Create appraisal"
    And I set the following fields to these values:
      | Name | Behat Test Appraisal |
      | Description | This is the behat description|
    And I press "Create appraisal"
    And I press "Add stage"
    And I set the following fields to these values:
      | Name | Behat Appraisal stage |
      | Description | Behat stage description|
    And I press "id_submitbutton"
    And I should see "Behat Appraisal stage" in the ".appraisal-stages" "css_element"
    And I click on "Behat Appraisal stage" "link" in the ".appraisal-stages" "css_element"
    # AJAX updated
    And I click on "Add new page" "link" in the ".appraisal-page-pane" "css_element"
    # AJAX form load
    And I set the following fields to these values:
      | Name | test page |
# TODO: fix the submission of YUI forms somehow
#    And I click on "Add new page" "button" in the ".moodle-dialogue-content" "css_element"
#    And I should see "test page" in the "#appraisal-page-list" "css_element"
