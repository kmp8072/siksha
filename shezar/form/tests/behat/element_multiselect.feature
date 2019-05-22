@totara @totara_form
Feature: Totara form multiselect element tests
  In order to test the multiselect element
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  @javascript
  Scenario: Test basic multiselect elements in Totara forms
    When I select "Basic multiselect element [totara_form\form\testform\element_multiselect]" from the "Test form" singleselect
    Then I should see "Form: Basic multiselect element"

    When I set the following Totara form fields to these values:
      | Required basic multiselect | Yes |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "multiselect_basic" row "Value" column of "form_results" table should contain ""
    And "multiselect_required" row "Value" column of "form_results" table should contain "1"
    And "multiselect_with_current_data" row "Value" column of "form_results" table should contain "yes"
    And "multiselect_frozen_empty" row "Value" column of "form_results" table should contain "--null--"
    And "multiselect_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "multiselect_frozen_with_current_data" row "Value" column of "form_results" table should contain "true"
    And "multiselect_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain ""
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain ""
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain ""
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain ""
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain ""
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain ""
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain ""
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain ""
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain ""
    And "form_select" row "Value" column of "form_results" table should contain "totara_form\form\testform\element_multiselect"
    And "submitbutton" row "Value" column of "form_results" table should contain "1"

    When I press "Reset"
    Then I should see "Form: Basic multiselect element"

    When I click on "Expand all" "link"
    And I set the following Totara form fields to these values:
      | Basic multiselect | No |
      | Required basic multiselect | No |
      | multiselect with current data | Yeah? |
      | Empty frozen multiselect | No |
      | Frozen multiselect with current data | 0 |
      | Hidden if reference | Charlie |
      | A is visible when test is selected | Yes |
      | C is visible when test is not selected | 0 |
      | D is visible when test is selected | UK |
      | F is visible when test is selected | y |
      | G is visible when required multiselect is not selected | No |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "multiselect_basic" row "Value" column of "form_results" table should contain "3"
    And "multiselect_required" row "Value" column of "form_results" table should contain "3"
    And "multiselect_with_current_data" row "Value" column of "form_results" table should contain "whatever"
    And "multiselect_frozen_empty" row "Value" column of "form_results" table should contain "--null--"
    And "multiselect_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "multiselect_frozen_with_current_data" row "Value" column of "form_results" table should contain "true"
    And "multiselect_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "c"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "1"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain ""
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "0"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "United Kingdom"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain ""
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "Y"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "3"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain ""
    And "form_select" row "Value" column of "form_results" table should contain "totara_form\form\testform\element_multiselect"
    And "submitbutton" row "Value" column of "form_results" table should contain "1"

  Scenario: Test required multiselect elements in Totara forms without JavaScript
    When I select "Basic multiselect element [totara_form\form\testform\element_multiselect]" from the "Test form" singleselect
    Then I should see "Form: Basic multiselect element"
    When I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"

  @javascript
  Scenario: Test required multiselect elements in Totara forms with JavaScript
    When I select "Basic multiselect element [totara_form\form\testform\element_multiselect]" from the "Test form" singleselect
    Then I should see "Form: Basic multiselect element"
    When I start watching to see if a new page loads
    And I press "Save changes"
    Then a new page should not have loaded since I started watching
    And I should not see "The form has been submit"

  @javascript
  Scenario: Test hidden if on multiselect elements in Totara forms
    When I select "Basic multiselect element [totara_form\form\testform\element_multiselect]" from the "Test form" singleselect
    Then I should see "Form: Basic multiselect element"
    And I click on "Expand all" "link"

    And I should see "B is visible when test is not selected"
    And I should see "C is visible when test is not selected"
    And I should see "E is visible when test is not selected"
    And I should not see "A is visible when test is selected"
    And I should not see "D is visible when test is selected"
    And I should not see "F is visible when test is selected"
    And I should not see "G is visible when required multiselect is not selected"
    And I should see "H is visible when required multiselect is selected"

    When I set the following Totara form fields to these values:
      | B is visible when test is not selected | 1 |
      | C is visible when test is not selected | 1 |
      | E is visible when test is not selected | Yes |
      | H is visible when required multiselect is selected | Yes |
      | Required basic multiselect  | Yes |
    When I set the following Totara form fields to these values:
      | Hidden if reference | Charlie |
    Then I should see "Form: Basic multiselect element"
    And I should see "A is visible when test is selected"
    And I should see "C is visible when test is not selected"
    And I should see "D is visible when test is selected"
    And I should see "F is visible when test is selected"
    And I should not see "B is visible when test is not selected"
    And I should not see "E is visible when test is not selected"
    And I should see "G is visible when required multiselect is not selected"
    And I should not see "H is visible when required multiselect is selected"

    When I set the following Totara form fields to these values:
      | Basic multiselect | Yes |
      | multiselect with current data | Never! |
      | D is visible when test is selected | New Zealand |
      | G is visible when required multiselect is not selected | Yes |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "multiselect_basic" row "Value" column of "form_results" table should contain "[ '1' ]"
    And "multiselect_required" row "Value" column of "form_results" table should contain "[ '1' ]"
    And "multiselect_with_current_data" row "Value" column of "form_results" table should contain "[ 'nah' ]"
    And "multiselect_frozen_empty" row "Value" column of "form_results" table should contain "--null--"
    And "multiselect_frozen_with_current_data" row "Value" column of "form_results" table should contain "[ 'true' ]"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "[ 'c' ]"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "[ ]"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "[ 'true' ]"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "[ '0' ]"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "[ 'New Zealand' ]"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "[ '0' ]"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "[ ]"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "[ '1' ]"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "[ '1' ]"
    And "form_select" row "Value" column of "form_results" table should contain "totara_form\form\testform\element_multiselect"
    And "submitbutton" row "Value" column of "form_results" table should contain "1"
