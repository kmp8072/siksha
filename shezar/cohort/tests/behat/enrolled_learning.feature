@totara_cohort @totara
Feature: Assign enrolled learning to cohort
  In order to efficiently control enrolments to learning items
  As an admin
  I need to assign courses, programs and certifications to an audience

  Background:
    Given I am on a totara site
    And the following "cohorts" exist:
        | name | idnumber |
        | Cohort 1 | ASD |
        | Cohort 2 | DSA |
      And the following "courses" exist:
        | fullname | shortname | category |
        | Course 1 | C1 | 0 |
        | Course 2 | C2 | 0 |
      And I log in as "admin"

  @javascript
  Scenario: Assign courses as enrolled learning to a cohort
    Given I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Cohort 1"
    And I follow "Enrolled learning"
    And I press "Add courses"
    And I follow "Miscellaneous"
    And I follow "Course 1"
    And I press "Save"
    Then I should see "Course 1" in the "td.associations_nameiconlink" "css_element"
    And I should not see "Course 2" in the "td.associations_nameiconlink" "css_element"

  @javascript
  Scenario: Search for courses to assign to cohort
    Given I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Cohort 1"
    And I follow "Enrolled learning"
    And I press "Add courses"
    And I click on "Search" "link" in the "ul.ui-tabs-nav" "css_element"
    And I set the field "id_query" to "Course 2"
    And I click on "Search" "button" in the "#learningitemcourses" "css_element"
    And I should see "Course 2" in the "Add Courses to Enrolled Learning" "totaradialogue"
    And I should not see "No results found" in the "Add Courses to Enrolled Learning" "totaradialogue"
    And I should not see "Course 1" in the "Add Courses to Enrolled Learning" "totaradialogue"

  @javascript
  Scenario: Edit course visibility for a particular course
    Given I am on homepage
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Edit settings"
    And I click on "Add enrolled audiences" "button"
    And I follow "Cohort 1"
    And I click on "OK" "link_or_button" in the "div[aria-describedby='course-cohorts-enrolled-dialog']" "css_element"
    Then I should see "Cohort 1" in the "course-cohorts-table-enrolled" "table"
    And I should not see "Cohort 2" in the "course-cohorts-table-enrolled" "table"
