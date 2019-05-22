@totara @totara_plan
Feature: Learner creates learning plan with competencies

Background:
  Given I am on a totara site
  And the following "users" exist:
    | username | firstname  | lastname  | email                |
    | learner1 | firstname1 | lastname1 | learner1@example.com |
    | manager2 | firstname2 | lastname2 | manager2@example.com |
  And the following job assignments exist:
    | user     | fullname       | manager  |
    | learner1 | jobassignment1 | manager2 |
  And the following "competency" frameworks exist:
    | fullname               | idnumber | description           |
    | Competency Framework 1 | CF1      | Framework description |
  And the following "competency" hierarchy exists:
    | framework | fullname     | idnumber | description            |
    | CF1       | Competency 1 | C1       | Competency description |
    | CF1       | Competency 2 | C2       | Competency description |
    | CF1       | Competency 3 | C3       | Competency description |
  And the following "plans" exist in "totara_plan" plugin:
    | user     | name                   |
    | learner1 | learner1 Learning Plan |

  @javascript
  Scenario: Test the learner can add and remove competencies from their learning plan prior to approval.

  # Login as the learner and navigate to the learning plan.
  Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
  And I click on "learner1 Learning Plan" "link"

  # Add some competencies to the plan.
  And I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
  And I press "Add competencies"
  And I click on "Competency 1" "link"
  And I click on "Competency 2" "link"
  And I click on "Competency 3" "link"

  # Check the selected competency appear in the plan.
  When I click on "Continue" "button" in the "Add competencies" "totaradialogue"
  Then I should see "Competency 1" in the ".dp-plan-component-items" "css_element"
  And I should see "Competency 2" in the ".dp-plan-component-items" "css_element"
  And I should see "Competency 3" in the ".dp-plan-component-items" "css_element"

  # Delete a competency to make sure it's removed properly.
  When I click on "Delete" "link" in the "#competencylist_r2_c5" "css_element"
  Then I should see "Are you sure you want to remove this item?"
  When I press "Continue"
  Then I should not see "Competency 3" in the "#dp-component-update-table" "css_element"

  # Send the plan to the manager for approval.
  When I press "Send approval request"
  Then I should see "Approval request sent for plan \"learner1 Learning Plan\""
  And I should see "This plan has not yet been approved (Approval Requested)"
  And I log out

  # As the manager, access the learners plans.
  When I log in as "manager2"
  And I click on "Team" in the totara menu
  And I click on "Plans" "link" in the "firstname1 lastname1" "table_row"

  # Access the learners plans and verify it hasn't been approved.
  And I click on "learner1 Learning Plan" "link"
  Then I should see "You are viewing firstname1 lastname1's plan"
  And I should see "This plan has not yet been approved"

  # Approve the plan.
  When I set the field "reasonfordecision" to "Nice plan!"
  And I press "Approve"
  Then I should see "You are viewing firstname1 lastname1's plan"
  And I should see "Plan \"learner1 Learning Plan\" has been approved"

  # Make sure the ajax competency update request works
  When I click on "Team" in the totara menu
  And I click on "Records" "link" in the "firstname1 lastname1" "table_row"
  And the field "competencyevidencestatus1" matches value "Not Set"
  And I set the field "competencyevidencestatus1" to "Competent"
  And I click on "Other Evidence" "link" in the ".tabtree" "css_element"
  And I click on "Competencies" "link" in the ".tabtree" "css_element"
  Then the field "competencyevidencestatus1" matches value "Competent"
