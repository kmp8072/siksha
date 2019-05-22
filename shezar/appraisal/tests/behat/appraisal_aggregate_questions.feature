@totara @totara_appraisal @javascript
Feature: Perform basic actions for aggregate questions
  In order to view aggregate questions
  As a user
  I need to answer some ratings questions

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | appraiser | Sally     | Sal      | appraiser@example.com |
      | manager   | Terry     | Ter      | manager@example.com   |
      | jimmy     | Jimmy     | Jim      | jimmy@example.com     |
      | bobby     | Bobby     | Bob      | bobby@example.com     |
      | dobby     | Dobby     | Dob      | dobby@example.com     |
    And the following job assignments exist:
      | user  | fullname      | idnumber | manager | appraiser |
      | jimmy | jimmy Day Job | l1ja     | manager | appraiser |
      | bobby | bobby Day Job | l2ja     | manager | appraiser |
      | dobby | dobby Day Job | l3ja     | manager | appraiser |
    And the following "cohorts" exist:
      | name                | idnumber | description            | contextlevel | reference |
      | Appraisals Audience | AppAud   | Appraisals Assignments | System       | 0         |
    And the following "cohort members" exist:
      | user  | cohort |
      | jimmy | AppAud |
      | bobby | AppAud |
      | dobby | AppAud |
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name            |
      | Aggregate Tests |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal       | name   | timedue                 |
      | Aggregate Tests | Stage1 | 1 January 2020 23:59:59 |
      | Aggregate Tests | Stage2 | 1 January 2030 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal       | stage  | name              |
      | Aggregate Tests | Stage1 | Stage1-Ratings    |
      | Aggregate Tests | Stage1 | Stage1-Aggregates |
      | Aggregate Tests | Stage2 | Stage2-Ratings    |
      | Aggregate Tests | Stage2 | Stage2-Aggregates |
     And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal       | stage  | page              | name              | type          | default | ExtraInfo                          |
      | Aggregate Tests | Stage1 | Stage1-Ratings    | S1-desc           | text          |         |                                    |
      | Aggregate Tests | Stage1 | Stage1-Ratings    | S1-Rating_Numeric | ratingnumeric | 5       | Range:1-10,Display:slider          |
      | Aggregate Tests | Stage1 | Stage1-Ratings    | S1-Rating_Custom  | ratingcustom  | choice1 |                                    |
      | Aggregate Tests | Stage1 | Stage1-Aggregates | S1-Rating_Extra   | ratingnumeric | 7       | Range:2-8,Display:slider           |
      | Aggregate Tests | Stage1 | Stage1-Aggregates | S1-Aggregate      | aggregate     |         | S1-Rating_Numeric,S1-Rating_Custom |
      | Aggregate Tests | Stage2 | Stage2-Ratings    | S2-Rating_Numeric | ratingnumeric | 4       | Range:1-10,Display:slider          |
      | Aggregate Tests | Stage2 | Stage2-Ratings    | S2-Rating_Custom  | ratingcustom  | choice2 |                                    |
      | Aggregate Tests | Stage2 | Stage2-Aggregates | S2-Aggregate      | aggregate     |         | S2-Rating_Numeric,S2-Rating_Custom |
      | Aggregate Tests | Stage2 | Stage2-Aggregates | Total Aggregate   | aggregate     |         | *                                  |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal       | type     | id     |
      | Aggregate Tests | audience | AppAud |

    Scenario: Check available questions in the aggregate settings page.
      When I log in as "admin"
      And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
      And I click on "Aggregate Tests" "link"
      And I click on "Content" "link" in the ".tabtree" "css_element"
      And I click on "Stage1" "link" in the ".appraisal-stages" "css_element"
      And I click on "Stage1-Aggregates" "link" in the ".appraisal-page-container" "css_element"
      And I click on "Settings" "link" in the "S1-Aggregate" "list_item"
      Then I should not see "S1-desc" in the ".aggregateselector" "css_element"
      And I should see "S1-Rating_Numeric" in the ".aggregateselector" "css_element"
      And I should see "S1-Rating_Custom" in the ".aggregateselector" "css_element"
      And I should not see "S1-Rating_Extra" in the ".aggregateselector" "css_element"
      And I should not see "S2-Rating_Numeric" in the ".aggregateselector" "css_element"
      And I should not see "S2-Rating_Custom" in the ".aggregateselector" "css_element"
      And I click on "Close" "button" in the ".moodle-dialogue-hd" "css_element"
      When I click on "Stage2" "link" in the ".appraisal-stages" "css_element"
      And I click on "Stage2-Aggregates" "link" in the ".appraisal-page-container" "css_element"
      And I click on "Settings" "link" in the "S2-Aggregate" "list_item"
      Then I should not see "S1-desc" in the ".aggregateselector" "css_element"
      And I should see "S1-Rating_Numeric" in the ".aggregateselector" "css_element"
      And I should see "S1-Rating_Custom" in the ".aggregateselector" "css_element"
      And I should see "S1-Rating_Extra" in the ".aggregateselector" "css_element"
      And I should see "S2-Rating_Numeric" in the ".aggregateselector" "css_element"
      And I should see "S2-Rating_Custom" in the ".aggregateselector" "css_element"

    Scenario: Answer ratings questions and view aggregate question
      When I log in as "admin"
      And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
      And I click on "Aggregate Tests" "link"
      And I click on "Activate now" "link"
      And I press "Activate"
      And I log out

      When I log in as "jimmy"
      And I click on "All Appraisals" in the totara menu
      And I click on "Aggregate Tests" "link" in the "Aggregate Tests" "table_row"
      And I press "Start"
      And I click on "choice3" "radio"
      And I click on "Next" "button"

      Then I should see "Average score: 5.5"
      And I should see "Median score: 5.5"

      When I click on "Complete Stage" "button" in the "#fitem_id_submitbutton" "css_element"
      And I log out
      And I log in as "manager"
      And I click on "All Appraisals" in the totara menu
      And I click on "Aggregate Tests" "link" in the "Jimmy Jim" "table_row"
      And I press "Start"
      And I click on "choice1" "radio"
      And I click on "Next" "button"

      Then I should see "Average score: 3.5"
      And I should see "Median score: 3.5"
      And I should see "Average score: 5.5"
      And I should see "Median score: 5.5"

      When I click on "Complete Stage" "button" in the "#fitem_id_submitbutton" "css_element"
      And I log out
      And I log in as "appraiser"
      And I click on "All Appraisals" in the totara menu
      And I click on "Aggregate Tests" "link" in the "Jimmy Jim" "table_row"
      And I press "Start"
      And I click on "choice1" "radio"
      And I click on "Next" "button"

      Then I should see "Average score: 3.5"
      And I should see "Median score: 3.5"
      And I should see "Average score: 5.5"
      And I should see "Median score: 5.5"

      When I click on "Complete Stage" "button" in the "#fitem_id_submitbutton" "css_element"
      And I log out
      And I log in as "jimmy"
      And I click on "All Appraisals" in the totara menu
      And I click on "Aggregate Tests" "link" in the "Aggregate Tests" "table_row"
      And I press "Start"
      And I click on "choice4" "radio"
      And I click on "Next" "button"

      Then I should see "Average score: 6"
      And I should see "Median score: 6"

      When I click on "Complete Stage" "button" in the "#fitem_id_submitbutton" "css_element"
      And I log out
      And I log in as "manager"
      And I click on "All Appraisals" in the totara menu
      And I click on "Aggregate Tests" "link" in the "Jimmy Jim" "table_row"
      And I press "Start"
      And I click on "choice1" "radio"
      And I click on "Next" "button"

      Then I should see "Average score: 3"
      And I should see "Median score: 3"
      And I should see "Average score: 4"
      And I should see "Median score: 4"
      And I should see "Average score: 6"
      And I should see "Median score: 6"

      When I click on "Complete Stage" "button" in the "#fitem_id_submitbutton" "css_element"
      And I log out
      And I log in as "appraiser"
      And I click on "All Appraisals" in the totara menu
      And I click on "Aggregate Tests" "link" in the "Jimmy Jim" "table_row"
      And I press "Start"
      And I click on "choice1" "radio"
      And I click on "Next" "button"

      Then I should see "Average score: 3"
      And I should see "Median score: 3"
      And I should see "Average score: 4"
      And I should see "Median score: 4"
      And I should see "Average score: 6"
      And I should see "Median score: 6"

      When I click on "Complete Stage" "button" in the "#fitem_id_submitbutton" "css_element"

      Then I should see "This appraisal was completed"
